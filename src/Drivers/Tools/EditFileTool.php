<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

class EditFileTool implements ToolContract
{
    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'edit_file';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Edit a file by replacing an exact string match. The old_string must appear exactly once in the file.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path to the file from the project root.',
                    ],
                    'old_string' => [
                        'type' => 'string',
                        'description' => 'The exact string to find and replace. Must match exactly once.',
                    ],
                    'new_string' => [
                        'type' => 'string',
                        'description' => 'The replacement string.',
                    ],
                ],
                'required' => ['path', 'old_string', 'new_string'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $path = ToolExecutor::validatePath($input['path'] ?? '', $this->worktreePath);

        if (! is_file($path)) {
            return "Error: File not found: {$input['path']}";
        }

        $content = file_get_contents($path);
        $oldString = $input['old_string'] ?? '';
        $newString = $input['new_string'] ?? '';

        $count = substr_count($content, $oldString);

        if ($count === 0) {
            return "Error: old_string not found in {$input['path']}";
        }

        if ($count > 1) {
            return "Error: old_string found {$count} times in {$input['path']}. It must appear exactly once.";
        }

        $updated = str_replace($oldString, $newString, $content);
        file_put_contents($path, $updated);

        return "File edited successfully: {$input['path']}";
    }
}
