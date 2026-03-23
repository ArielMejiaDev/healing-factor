<?php

namespace ArielMejiaDev\XFactor;

use ArielMejiaDev\XFactor\Contracts\DriverContract;
use ArielMejiaDev\XFactor\Drivers\ApiDriver;
use ArielMejiaDev\XFactor\Drivers\CLIDriver;
use ArielMejiaDev\XFactor\Enums\CliTool;
use Illuminate\Support\Manager;

class XFactorManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('x-factor.driver', 'cli');
    }

    protected function createCliDriver(): CLIDriver
    {
        return new CLIDriver(
            tool: CliTool::from($this->config->get('x-factor.cli_tool', 'claude')),
            model: $this->config->get('x-factor.model'),
            timeout: $this->config->get('x-factor.process.timeout', 3600),
            maxTurns: $this->config->get('x-factor.process.max_turns', 25),
            workingDirectory: $this->config->get('x-factor.process.working_directory'),
        );
    }

    protected function createApiDriver(): ApiDriver
    {
        return new ApiDriver(
            model: $this->config->get('x-factor.api.model', 'claude-sonnet-4-6'),
            maxTokens: $this->config->get('x-factor.api.max_tokens', 8192),
            maxTurns: $this->config->get('x-factor.api.max_turns', 25),
            timeout: $this->config->get('x-factor.process.timeout', 3600),
            workingDirectory: $this->config->get('x-factor.process.working_directory'),
        );
    }

    public function driverForCategory(?string $category): DriverContract
    {
        if ($category === null) {
            return $this->driver();
        }

        $categoryConfig = $this->config->get("x-factor.categories.{$category}", []);
        $driver = $this->config->get('x-factor.driver', 'cli');

        if ($driver === 'api') {
            return new ApiDriver(
                model: $categoryConfig['model'] ?? $this->config->get('x-factor.api.model', 'claude-sonnet-4-6'),
                maxTokens: $categoryConfig['max_tokens'] ?? $this->config->get('x-factor.api.max_tokens', 8192),
                maxTurns: $categoryConfig['max_turns'] ?? $this->config->get('x-factor.api.max_turns', 25),
                timeout: $categoryConfig['timeout'] ?? $this->config->get('x-factor.process.timeout', 3600),
                workingDirectory: $this->config->get('x-factor.process.working_directory'),
            );
        }

        return new CLIDriver(
            tool: CliTool::from($categoryConfig['cli_tool'] ?? $this->config->get('x-factor.cli_tool', 'claude')),
            model: $categoryConfig['model'] ?? $this->config->get('x-factor.model'),
            timeout: $categoryConfig['timeout'] ?? $this->config->get('x-factor.process.timeout', 3600),
            maxTurns: $categoryConfig['max_turns'] ?? $this->config->get('x-factor.process.max_turns', 25),
            workingDirectory: $this->config->get('x-factor.process.working_directory'),
        );
    }
}
