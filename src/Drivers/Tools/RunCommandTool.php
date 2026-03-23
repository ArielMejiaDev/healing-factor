<?php

namespace ArielMejiaDev\XFactor\Drivers\Tools;

use Illuminate\Support\Facades\Process;

class RunCommandTool implements ToolContract
{
    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'run_command';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Run a shell command in the project directory. Only allowlisted commands are permitted (git, php artisan test, pest, phpunit, composer dump-autoload, gh pr create).',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'command' => [
                        'type' => 'string',
                        'description' => 'The shell command to run.',
                    ],
                ],
                'required' => ['command'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $command = $input['command'] ?? '';

        if (! $this->isAllowed($command)) {
            return "Error: Command not allowed. Only these commands are permitted: ".implode(', ', $this->allowedCommands());
        }

        $result = Process::path($this->worktreePath)
            ->timeout(120)
            ->run($command);

        $output = $result->output().$result->errorOutput();

        if (trim($output) === '') {
            return $result->successful() ? '(command completed successfully with no output)' : '(command failed with no output)';
        }

        return $output;
    }

    protected function isAllowed(string $command): bool
    {
        $command = trim($command);

        foreach ($this->allowedCommands() as $allowed) {
            if ($command === $allowed || str_starts_with($command, $allowed.' ')) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    protected function allowedCommands(): array
    {
        return config('x-factor.api.allowed_commands', [
            'git',
            'php artisan test',
            './vendor/bin/pest',
            './vendor/bin/phpunit',
            'composer dump-autoload',
            'gh pr create',
        ]);
    }
}
