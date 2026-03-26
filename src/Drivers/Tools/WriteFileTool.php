<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

class WriteFileTool implements ToolContract
{
    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'write_file';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Write content to a file. Creates the file and any missing parent directories. Overwrites existing content.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path to the file from the project root.',
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'The full content to write to the file.',
                    ],
                ],
                'required' => ['path', 'content'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $path = ToolExecutor::validatePath($input['path'] ?? '', $this->worktreePath);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, recursive: true);
        }

        file_put_contents($path, $input['content'] ?? '');

        return "File written successfully: {$input['path']}";
    }
}
