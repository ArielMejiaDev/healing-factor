<?php

namespace ArielMejiaDev\HealingFactor\Commands;

use ArielMejiaDev\HealingFactor\Support\HealingFactorBanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class HealingFactorInstallCommand extends Command
{
    protected $signature = 'healing-factor:install';

    protected $description = 'Install Healing-Factor: publish config, run migrations, and verify setup.';

    public function handle(): int
    {
        $this->newLine();
        foreach (HealingFactorBanner::render() as $line) {
            $this->output->writeln($line);
        }
        $this->newLine();

        $this->info('Installing Healing-Factor...');
        $this->newLine();

        // 1. Publish config
        $this->call('vendor:publish', [
            '--tag' => 'healing-factor-config',
        ]);
        $this->info('Config published.');

        // 2. Publish migration
        $this->call('vendor:publish', [
            '--tag' => 'healing-factor-migrations',
        ]);
        $this->info('Migration published.');

        // 3. Run migration
        if ($this->confirm('Run the migration now?', true)) {
            $this->call('migrate');
            $this->info('Migration completed.');
        }

        // 4. Driver-specific checks
        $this->newLine();
        $this->info('Checking setup...');

        $driver = config('healing-factor.driver', 'cli');
        $this->info("  Driver: {$driver}");

        if ($driver === 'cli') {
            $cliTool = config('healing-factor.cli_tool', 'claude');
            $result = Process::run(['which', $cliTool]);

            if ($result->successful()) {
                $this->info("  [OK] CLI tool '{$cliTool}' found at: ".trim($result->output()));
            } else {
                $this->warn("  [WARN] CLI tool '{$cliTool}' not found. Install it before using Healing-Factor.");
            }
        }

        if ($driver === 'api') {
            if (config('healing-factor.api_keys.anthropic')) {
                $this->info('  [OK] ANTHROPIC_API_KEY is set.');
            } else {
                $this->warn('  [WARN] ANTHROPIC_API_KEY is not set. The API driver requires it. Add it to your .env file.');
            }
        }

        $this->newLine();
        $this->info('Healing-Factor installation complete!');

        return self::SUCCESS;
    }
}
