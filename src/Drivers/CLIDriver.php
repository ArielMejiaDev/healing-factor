<?php

namespace ArielMejiaDev\XFactor\Drivers;

use ArielMejiaDev\XFactor\Contracts\DriverContract;
use ArielMejiaDev\XFactor\Contracts\DriverResult;
use ArielMejiaDev\XFactor\Drivers\Concerns\ManagesWorktrees;
use ArielMejiaDev\XFactor\Enums\CliTool;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Support\Facades\Process;

class CLIDriver implements DriverContract
{
    use ManagesWorktrees;

    public function __construct(
        protected CliTool $tool,
        protected ?string $model,
        protected int $timeout,
        protected ?int $maxTurns,
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

        $command = $this->tool->buildCommand($prompt, $this->model, $this->maxTurns);

        $env = [];
        $anthropicKey = config('x-factor.api_keys.anthropic');
        if ($anthropicKey) {
            $env['ANTHROPIC_API_KEY'] = $anthropicKey;
        }
        $githubToken = config('x-factor.api_keys.github_pat');
        if ($githubToken) {
            $env['GITHUB_TOKEN'] = $githubToken;
        }

        $basePath = $this->workingDirectory ?? base_path();

        // Create a git worktree so the AI works on an isolated branch,
        // never touching the production working directory.
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
            $pendingProcess = Process::path($worktreePath)->timeout($this->timeout);

            if (! empty($env)) {
                $pendingProcess = $pendingProcess->env($env);
            }

            $result = $pendingProcess->run($command);

            return new DriverResult(
                success: $result->successful(),
                output: $this->parseOutput($result->output()),
                errorOutput: $result->errorOutput(),
                exitCode: $result->exitCode(),
            );
        } finally {
            $this->removeWorktree($basePath, $worktreePath);
        }
    }

    /**
     * Parse the JSON output from the CLI tool and extract the text result.
     *
     * Both `claude --output-format json` and `opencode --format json` return
     * a JSON object with a `result` field containing the model's text response.
     */
    protected function parseOutput(string $rawOutput): string
    {
        $decoded = json_decode($rawOutput, true);

        if (! is_array($decoded) || ! isset($decoded['result'])) {
            return $rawOutput;
        }

        return $decoded['result'];
    }
}
