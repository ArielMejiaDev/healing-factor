<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

class ToolRegistry
{
    /** @return list<array<string, mixed>> JSON schema definitions for all tools. */
    public static function definitions(string $worktreePath): array
    {
        return array_map(
            fn (ToolContract $tool) => $tool->definition(),
            array_values(static::tools($worktreePath)),
        );
    }

    /** @return array<string, ToolContract> Instantiated tools keyed by name. */
    public static function tools(string $worktreePath): array
    {
        $tools = [
            new ReadFileTool($worktreePath),
            new WriteFileTool($worktreePath),
            new EditFileTool($worktreePath),
            new ListDirectoryTool($worktreePath),
            new SearchFilesTool($worktreePath),
            new RunCommandTool($worktreePath),
        ];

        $keyed = [];
        foreach ($tools as $tool) {
            $keyed[$tool->name()] = $tool;
        }

        return $keyed;
    }
}
