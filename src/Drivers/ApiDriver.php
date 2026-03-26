<?php

namespace ArielMejiaDev\HealingFactor\Drivers;

use Anthropic\Client;
use ArielMejiaDev\HealingFactor\Contracts\DriverContract;
use ArielMejiaDev\HealingFactor\Contracts\DriverResult;
use ArielMejiaDev\HealingFactor\Drivers\Concerns\ManagesWorktrees;
use ArielMejiaDev\HealingFactor\Drivers\Tools\ToolExecutor;
use ArielMejiaDev\HealingFactor\Drivers\Tools\ToolRegistry;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;

class ApiDriver implements DriverContract
{
    use ManagesWorktrees;

    public function __construct(
        protected ?string $model,
        protected int $maxTokens,
        protected int $maxTurns,
        protected int $timeout,
        protected ?string $workingDirectory,
    ) {}

    public function resolve(Issue $issue, string $prompt): DriverResult
    {
        if (! $issue->branch_name) {
            return new DriverResult(
                success: false,
                output: '',
                errorOutput: 'Cannot resolve issue: branch_name is not set. Refusing to run without worktree isolation.',
                exitCode: 1,
            );
        }

        $basePath = $this->workingDirectory ?? base_path();
        $worktreePath = $this->createWorktree($basePath, $issue->branch_name);

        if (! $worktreePath) {
            return new DriverResult(
                success: false,
                output: '',
                errorOutput: "Failed to create git worktree for branch: {$issue->branch_name}",
                exitCode: 1,
            );
        }

        try {
            return $this->runAgenticLoop($worktreePath, $issue, $prompt);
        } finally {
            $this->removeWorktree($basePath, $worktreePath);
        }
    }

    protected function runAgenticLoop(string $worktreePath, Issue $issue, string $prompt): DriverResult
    {
        $client = new Client(apiKey: config('healing-factor.api_keys.anthropic'));

        $toolDefinitions = ToolRegistry::definitions($worktreePath);
        $executor = new ToolExecutor($worktreePath);

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $collectedOutput = [];
        $model = $this->model ?? config('healing-factor.api.model', 'claude-sonnet-4-6');

        for ($turn = 1; $turn <= $this->maxTurns; $turn++) {
            try {
                $response = $client->messages->create(
                    maxTokens: $this->maxTokens,
                    messages: $messages,
                    model: $model,
                    system: $this->buildSystemPrompt($issue),
                    tools: $toolDefinitions,
                );
            } catch (\Throwable $e) {
                HealingFactorLogger::error("API driver: Anthropic API error on turn {$turn}: {$e->getMessage()}");

                return new DriverResult(
                    success: false,
                    output: implode("\n", $collectedOutput),
                    errorOutput: "Anthropic API error: {$e->getMessage()}",
                    exitCode: 1,
                );
            }

            // Collect text output and tool calls from response
            $toolCalls = [];
            $toolNames = [];

            foreach ($response->content as $block) {
                if ($block->type === 'text') {
                    $collectedOutput[] = $block->text;
                } elseif ($block->type === 'tool_use') {
                    $toolCalls[] = $block;
                    $toolNames[] = $block->name;
                }
            }

            HealingFactorLogger::info("API driver: turn {$turn}/{$this->maxTurns}", [
                'stop_reason' => $response->stopReason,
                'tools_called' => implode(', ', $toolNames) ?: 'none',
            ]);

            // Append assistant response to conversation
            $messages[] = [
                'role' => 'assistant',
                'content' => $this->serializeContentBlocks($response->content),
            ];

            // If the model stopped without calling tools, we're done
            if ($response->stopReason !== 'tool_use') {
                break;
            }

            // Execute tool calls and send results back
            $toolResults = [];
            foreach ($toolCalls as $toolCall) {
                $result = $executor->execute($toolCall->name, $toolCall->input);

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolCall->id,
                    'content' => $result->output,
                    'is_error' => $result->isError,
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $toolResults,
            ];
        }

        $fullOutput = implode("\n", $collectedOutput);
        $exhaustedTurns = isset($response) && $response->stopReason === 'tool_use';

        if ($exhaustedTurns) {
            HealingFactorLogger::warning("API driver: exhausted all {$this->maxTurns} turns without completing.");

            return new DriverResult(
                success: false,
                output: $fullOutput,
                errorOutput: "Resolution incomplete: exhausted all {$this->maxTurns} turns without finishing. The fix may be partially applied but no PR was created.",
                exitCode: 1,
            );
        }

        return new DriverResult(
            success: true,
            output: $fullOutput,
            errorOutput: '',
            exitCode: 0,
        );
    }

    protected function buildSystemPrompt(Issue $issue): string
    {
        $branchName = $issue->branch_name;
        $prDraft = config('healing-factor.pr.draft', true) ? '--draft' : '';
        $prLabels = config('healing-factor.pr.labels', []);
        $labelFlags = implode(' ', array_map(fn (string $l) => "--label \"{$l}\"", $prLabels));
        $reviewers = config('healing-factor.pr.reviewers', []);
        $reviewerFlags = implode(' ', array_map(fn (string $r) => "--reviewer \"{$r}\"", $reviewers));

        $ghFlags = trim(implode(' ', array_filter([$prDraft, $labelFlags, $reviewerFlags])));

        return <<<PROMPT
        You are an expert Laravel/PHP developer. You have access to tools for reading, editing, searching, and listing files, and running shell commands.

        Your task is to fix a bug described by the user. You are working on branch "{$branchName}" inside a git worktree.

        ## Rules

        1. Analyze the exception and stack trace carefully before making changes.
        2. Use the tools to explore the codebase, understand the context, then apply the minimal safe fix.
        3. After fixing, run the test suite with `run_command` to verify your fix doesn't break anything.
        4. Commit your changes: `git add -A && git commit -m "fix: <concise description>"`
        5. Push the branch: `git push origin {$branchName}`
        6. Create a pull request: `gh pr create --title "fix: <title>" --body "<description>" --head {$branchName} {$ghFlags}`
        7. Do NOT make unrelated changes. Only fix the reported issue.
        8. Do NOT modify tests unless the test itself is the source of the bug.

        ## Safety

        - Never run destructive commands (rm -rf, DROP TABLE, etc.)
        - Stay on branch "{$branchName}" — do not checkout other branches
        - Only use allowed commands via run_command
        PROMPT;
    }

    /**
     * Serialize content blocks for the conversation history.
     *
     * @param  list<object>  $blocks
     * @return list<array<string, mixed>>
     */
    protected function serializeContentBlocks(array $blocks): array
    {
        $serialized = [];

        foreach ($blocks as $block) {
            if ($block->type === 'text') {
                $serialized[] = [
                    'type' => 'text',
                    'text' => $block->text,
                ];
            } elseif ($block->type === 'tool_use') {
                $serialized[] = [
                    'type' => 'tool_use',
                    'id' => $block->id,
                    'name' => $block->name,
                    'input' => $block->input,
                ];
            }
        }

        return $serialized;
    }
}
