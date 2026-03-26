<?php

namespace ArielMejiaDev\HealingFactor\Prompts;

use ArielMejiaDev\HealingFactor\Contracts\PromptBuilderContract;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Services\BranchNameGenerator;

class PromptBuilder implements PromptBuilderContract
{
    public function __construct(
        protected BranchNameGenerator $branchNameGenerator,
    ) {}

    public function build(Issue $issue): string
    {
        // Check for category-specific custom prompt
        if ($issue->category) {
            $customPrompt = config("healing-factor.categories.{$issue->category}.prompt");
            if ($customPrompt) {
                return $this->interpolate($customPrompt, $issue);
            }
        }

        // Check for top-level custom prompt
        $customPrompt = config('healing-factor.prompt');
        if ($customPrompt) {
            return $this->interpolate($customPrompt, $issue);
        }

        return $this->buildDefault($issue);
    }

    protected function buildDefault(Issue $issue): string
    {
        $branchName = $this->branchNameGenerator->generate($issue->title);
        $issue->update(['branch_name' => $branchName]);

        $monitorSection = $this->buildMonitorSection($issue);
        $stacktraceSection = $issue->stacktrace
            ? "\nStacktrace:\n```\n{$issue->stacktrace}\n```\n"
            : '';

        $prConfig = config('healing-factor.pr', []);
        $draftFlag = ($prConfig['draft'] ?? true) ? ' --draft' : '';
        $labels = implode(',', $prConfig['labels'] ?? ['healing-factor', 'auto-fix']);
        $titleShort = mb_substr($issue->title, 0, 60);

        return <<<PROMPT
        You are working inside a Laravel project. You are already on branch "{$branchName}".
        Do NOT switch branches or create a new branch. All changes must stay on this branch.

        {$monitorSection}

        Issue Title: {$issue->title}
        Exception Class: {$issue->exception_class}
        Exception Message: {$issue->exception_message}
        {$stacktraceSection}

        Requirements:
        - Analyze the issue and determine the root cause.
        - Apply the smallest safe fix that resolves the issue. Do not add unrelated changes.
        - Write or update relevant tests if applicable.
        - Commit all changes with a concise message describing the fix.
        - Push the branch with "git push -u origin {$branchName}".
        - Create a pull request using "gh pr create{$draftFlag} --title \"[Healing-Factor] Fix: {$titleShort}\" --body \"Auto-generated fix by Healing-Factor\" --label \"{$labels}\"".
        - Print a short summary of what changed and why.
        PROMPT;
    }

    protected function buildMonitorSection(Issue $issue): string
    {
        if ($issue->source === 'nightwatch') {
            return <<<SECTION
            Use the Laravel Nightwatch MCP tools to locate and diagnose this issue, then implement the fix directly.

            - Organization ID: {$issue->organization_id}
            - Application ID: {$issue->application_id}
            - Environment ID: {$issue->environment_id}

            Use Nightwatch MCP to fetch issue details and stack trace context before making code changes.
            If the issue has already been resolved in Nightwatch, disregard and exit.
            SECTION;
        }

        if ($issue->source === 'bugsnag') {
            return 'This issue was reported by Bugsnag. Use the exception details and stacktrace below to locate and fix the bug.';
        }

        return "This issue was detected by the application's exception listener. Use the exception details below to locate and fix the bug.";
    }

    protected function interpolate(string $template, Issue $issue): string
    {
        $branchName = $issue->branch_name ?? $this->branchNameGenerator->generate($issue->title);
        if (! $issue->branch_name) {
            $issue->update(['branch_name' => $branchName]);
        }

        $prConfig = config('healing-factor.pr', []);
        $draftFlag = ($prConfig['draft'] ?? true) ? ' --draft' : '';
        $labels = implode(',', $prConfig['labels'] ?? ['healing-factor', 'auto-fix']);
        $titleShort = mb_substr($issue->title ?? 'Fix issue', 0, 60);

        $userPrompt = str_replace(
            ['{title}', '{exception_class}', '{exception_message}', '{stacktrace}', '{branch_name}',
                '{organization_id}', '{application_id}', '{environment_id}', '{source}'],
            [$issue->title, $issue->exception_class ?? '', $issue->exception_message ?? '',
                $issue->stacktrace ?? '', $branchName,
                $issue->organization_id ?? '', $issue->application_id ?? '', $issue->environment_id ?? '',
                $issue->source ?? ''],
            $template
        );

        return <<<PROMPT
        IMPORTANT: You are already on branch "{$branchName}".
        Do NOT switch branches or create a new branch. All changes must stay on this branch.

        {$userPrompt}

        After applying the fix you MUST:
        - Commit all changes with a concise message describing the fix.
        - Push the branch with "git push -u origin {$branchName}".
        - Create a pull request using "gh pr create{$draftFlag} --title \"[Healing-Factor] Fix: {$titleShort}\" --body \"Auto-generated fix by Healing-Factor\" --label \"{$labels}\"".
        PROMPT;
    }
}
