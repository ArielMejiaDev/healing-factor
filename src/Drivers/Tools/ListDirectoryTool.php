<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

class ListDirectoryTool implements ToolContract
{
    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'list_directory';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'List the contents of a directory. Returns file and subdirectory names (directories end with /).',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path to the directory from the project root. Use "." for the root.',
                    ],
                ],
                'required' => ['path'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $path = ToolExecutor::validatePath($input['path'] ?? '.', $this->worktreePath);

        if (! is_dir($path)) {
            return "Error: Directory not found: {$input['path']}";
        }

        $entries = scandir($path);
        $lines = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $lines[] = is_dir($path.'/'.$entry) ? $entry.'/' : $entry;
        }

        if (empty($lines)) {
            return '(empty directory)';
        }

        return implode("\n", $lines);
    }
}
