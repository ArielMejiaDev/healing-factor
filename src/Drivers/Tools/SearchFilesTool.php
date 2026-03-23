<?php

namespace ArielMejiaDev\XFactor\Drivers\Tools;

use Illuminate\Support\Facades\Process;

class SearchFilesTool implements ToolContract
{
    private const MAX_OUTPUT_LENGTH = 10000;

    public function __construct(protected string $worktreePath) {}

    public function name(): string
    {
        return 'search_files';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Search for a pattern in files using grep. Returns matching lines with file paths and line numbers.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'pattern' => [
                        'type' => 'string',
                        'description' => 'The search pattern (basic regex).',
                    ],
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative directory or file path to search in. Defaults to "." (project root).',
                    ],
                    'include' => [
                        'type' => 'string',
                        'description' => 'Glob pattern to filter files, e.g. "*.php". Optional.',
                    ],
                ],
                'required' => ['pattern'],
            ],
        ];
    }

    public function execute(array $input): string
    {
        $searchPath = ToolExecutor::validatePath($input['path'] ?? '.', $this->worktreePath);

        $command = ['grep', '-rn', '--color=never'];

        if (! empty($input['include'])) {
            $command[] = '--include='.$input['include'];
        }

        $command[] = $input['pattern'] ?? '';
        $command[] = $searchPath;

        $result = Process::path($this->worktreePath)
            ->timeout(30)
            ->run($command);

        $output = $result->output();

        // Replace absolute worktree paths with relative paths for cleaner output
        $output = str_replace($this->worktreePath.'/', '', $output);

        if (mb_strlen($output) > self::MAX_OUTPUT_LENGTH) {
            $output = mb_substr($output, 0, self::MAX_OUTPUT_LENGTH)."\n... (output truncated)";
        }

        if (trim($output) === '') {
            return 'No matches found.';
        }

        return $output;
    }
}
