<?php

namespace ArielMejiaDev\HealingFactor;

use ArielMejiaDev\HealingFactor\Contracts\DriverContract;
use ArielMejiaDev\HealingFactor\Drivers\ApiDriver;
use ArielMejiaDev\HealingFactor\Drivers\CLIDriver;
use ArielMejiaDev\HealingFactor\Enums\CliTool;
use Illuminate\Support\Manager;

class HealingFactorManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('healing-factor.driver', 'cli');
    }

    protected function createCliDriver(): CLIDriver
    {
        return new CLIDriver(
            tool: CliTool::from($this->config->get('healing-factor.cli_tool', 'claude')),
            model: $this->config->get('healing-factor.model'),
            timeout: $this->config->get('healing-factor.process.timeout', 3600),
            maxTurns: $this->config->get('healing-factor.process.max_turns', 25),
            workingDirectory: $this->config->get('healing-factor.process.working_directory'),
        );
    }

    protected function createApiDriver(): ApiDriver
    {
        return new ApiDriver(
            model: $this->config->get('healing-factor.api.model', 'claude-sonnet-4-6'),
            maxTokens: $this->config->get('healing-factor.api.max_tokens', 8192),
            maxTurns: $this->config->get('healing-factor.api.max_turns', 25),
            timeout: $this->config->get('healing-factor.process.timeout', 3600),
            workingDirectory: $this->config->get('healing-factor.process.working_directory'),
        );
    }

    public function driverForCategory(?string $category): DriverContract
    {
        if ($category === null) {
            return $this->driver();
        }

        $categoryConfig = $this->config->get("healing-factor.categories.{$category}", []);
        $driver = $this->config->get('healing-factor.driver', 'cli');

        if ($driver === 'api') {
            return new ApiDriver(
                model: $categoryConfig['model'] ?? $this->config->get('healing-factor.api.model', 'claude-sonnet-4-6'),
                maxTokens: $categoryConfig['max_tokens'] ?? $this->config->get('healing-factor.api.max_tokens', 8192),
                maxTurns: $categoryConfig['max_turns'] ?? $this->config->get('healing-factor.api.max_turns', 25),
                timeout: $categoryConfig['timeout'] ?? $this->config->get('healing-factor.process.timeout', 3600),
                workingDirectory: $this->config->get('healing-factor.process.working_directory'),
            );
        }

        return new CLIDriver(
            tool: CliTool::from($categoryConfig['cli_tool'] ?? $this->config->get('healing-factor.cli_tool', 'claude')),
            model: $categoryConfig['model'] ?? $this->config->get('healing-factor.model'),
            timeout: $categoryConfig['timeout'] ?? $this->config->get('healing-factor.process.timeout', 3600),
            maxTurns: $categoryConfig['max_turns'] ?? $this->config->get('healing-factor.process.max_turns', 25),
            workingDirectory: $this->config->get('healing-factor.process.working_directory'),
        );
    }
}
