<?php

namespace ArielMejiaDev\XFactor\Drivers\Tools;

use ArielMejiaDev\XFactor\Support\XFactorLogger;

class ToolExecutor
{
    /** @var array<string, ToolContract> */
    protected array $tools;

    public function __construct(protected string $worktreePath)
    {
        $this->tools = ToolRegistry::tools($worktreePath);
    }

    /** @param array<string, mixed> $input */
    public function execute(string $toolName, array $input): ToolResult
    {
        $tool = $this->tools[$toolName] ?? null;

        if (! $tool) {
            return new ToolResult("Error: Unknown tool: {$toolName}", isError: true);
        }

        try {
            $output = $tool->execute($input);

            return new ToolResult($output, isError: false);
        } catch (\Throwable $e) {
            XFactorLogger::error("Tool '{$toolName}' threw an exception: {$e->getMessage()}");

            return new ToolResult("Error: {$e->getMessage()}", isError: true);
        }
    }

    /**
     * Validate that a path stays within the worktree directory.
     *
     * @throws \RuntimeException if the path escapes the worktree
     */
    public static function validatePath(string $relativePath, string $worktreePath): string
    {
        // Reject absolute paths and obvious traversal attempts upfront
        if (str_starts_with($relativePath, '/') || str_contains($relativePath, '..')) {
            throw new \RuntimeException("Path traversal detected: {$relativePath}");
        }

        $absolutePath = $worktreePath.'/'.$relativePath;

        // Resolve the path without requiring the file to exist yet.
        // realpath() returns false for non-existent paths, so we resolve
        // the deepest existing ancestor and append the remaining segments.
        $resolved = realpath($absolutePath);

        if ($resolved === false) {
            // File doesn't exist yet — resolve the parent directory
            $parent = $absolutePath;
            $segments = [];

            while (! is_dir($parent)) {
                $segments[] = basename($parent);
                $parent = dirname($parent);
            }

            $resolvedParent = realpath($parent);

            if ($resolvedParent === false || ! str_starts_with($resolvedParent, realpath($worktreePath))) {
                throw new \RuntimeException("Path traversal detected: {$relativePath}");
            }

            $resolved = $resolvedParent.'/'.implode('/', array_reverse($segments));
        } else {
            if (! str_starts_with($resolved, realpath($worktreePath))) {
                throw new \RuntimeException("Path traversal detected: {$relativePath}");
            }
        }

        return $resolved;
    }
}
