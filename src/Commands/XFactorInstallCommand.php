<?php

namespace ArielMejiaDev\XFactor\Commands;

use ArielMejiaDev\XFactor\Support\XFactorBanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class XFactorInstallCommand extends Command
{
    protected $signature = 'x-factor:install';

    protected $description = 'Install X-Factor: publish config, run migrations, and verify setup.';

    public function handle(): int
    {
        $this->newLine();
        foreach (XFactorBanner::render() as $line) {
            $this->output->writeln($line);
        }
        $this->newLine();

        $this->info('Installing X-Factor...');
        $this->newLine();

        // 1. Publish config
        $this->call('vendor:publish', [
            '--tag' => 'x-factor-config',
        ]);
        $this->info('Config published.');

        // 2. Publish migration
        $this->call('vendor:publish', [
            '--tag' => 'x-factor-migrations',
        ]);
        $this->info('Migration published.');

        // 3. Run migration
        if ($this->confirm('Run the migration now?', true)) {
            $this->call('migrate');
            $this->info('Migration completed.');
        }

        // 4. Check CLI tool availability
        $this->newLine();
        $this->info('Checking setup...');

        $cliTool = config('x-factor.cli_tool', 'claude');
        $result = Process::run(['which', $cliTool]);

        if ($result->successful()) {
            $this->info("  [OK] CLI tool '{$cliTool}' found at: ".trim($result->output()));
        } else {
            $this->warn("  [WARN] CLI tool '{$cliTool}' not found. Install it before using X-Factor.");
        }

        // 5. Check ANTHROPIC_API_KEY
        if (config('x-factor.api_keys.anthropic')) {
            $this->info('  [OK] ANTHROPIC_API_KEY is set.');
        } else {
            $this->warn('  [WARN] ANTHROPIC_API_KEY is not set. Add it to your .env file.');
        }

        $this->newLine();
        $this->info('X-Factor installation complete!');

        return self::SUCCESS;
    }
}
