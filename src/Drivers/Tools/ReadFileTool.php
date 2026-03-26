<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

class ReadFileTool implements ToolContract
{
    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'read_file';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Read the contents of a file. Returns the full file content as text.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path to the file from the project root.',
                    ],
                ],
                'required' => ['path'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $path = ToolExecutor::validatePath($input['path'] ?? '', $this->worktreePath);

        if (! is_file($path)) {
            return "Error: File not found: {$input['path']}";
        }

        return file_get_contents($path);
    }
}
