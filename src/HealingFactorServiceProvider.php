<?php

namespace ArielMejiaDev\HealingFactor;

use ArielMejiaDev\HealingFactor\Commands\HealingFactorInstallCommand;
use ArielMejiaDev\HealingFactor\Commands\HealingFactorPruneCommand;
use ArielMejiaDev\HealingFactor\Commands\HealingFactorRecoverStaleCommand;
use ArielMejiaDev\HealingFactor\Commands\HealingFactorRetryCommand;
use ArielMejiaDev\HealingFactor\Commands\HealingFactorStatusCommand;
use ArielMejiaDev\HealingFactor\Commands\HealingFactorTestCommand;
use ArielMejiaDev\HealingFactor\Contracts\MonitorContract;
use ArielMejiaDev\HealingFactor\Contracts\PromptBuilderContract;
use ArielMejiaDev\HealingFactor\Enums\MonitorTool;
use ArielMejiaDev\HealingFactor\Listeners\ExceptionListener;
use ArielMejiaDev\HealingFactor\Monitors\BugsnagMonitor;
use ArielMejiaDev\HealingFactor\Monitors\NightwatchMonitor;
use ArielMejiaDev\HealingFactor\Prompts\PromptBuilder;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HealingFactorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('healing-factor')
            ->hasConfigFile()
            ->hasMigration('create_healing_factor_issues_table')
            ->hasViews()
            ->hasRoute('webhooks')
            ->hasRoute('dashboard')
            ->hasCommands([
                HealingFactorInstallCommand::class,
                HealingFactorStatusCommand::class,
                HealingFactorRetryCommand::class,
                HealingFactorPruneCommand::class,
                HealingFactorRecoverStaleCommand::class,
                HealingFactorTestCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(HealingFactorManager::class, fn ($app) => new HealingFactorManager($app));

        $this->app->singleton(HealingFactor::class, fn ($app) => new HealingFactor(
            $app->make(HealingFactorManager::class),
        ));

        $this->app->bind(MonitorContract::class, function () {
            $monitor = MonitorTool::from(config('healing-factor.monitor', 'nightwatch'));

            return match ($monitor) {
                MonitorTool::Nightwatch => new NightwatchMonitor,
                MonitorTool::Bugsnag => new BugsnagMonitor,
                MonitorTool::ExceptionListener => new NightwatchMonitor,
            };
        });

        $this->app->bind(PromptBuilderContract::class, PromptBuilder::class);
    }

    public function packageBooted(): void
    {
        $this->configureLogChannel();

        if (config('healing-factor.monitor') === 'exception_listener') {
            Event::listen(MessageLogged::class, [ExceptionListener::class, 'handleMessageLogged']);
            Event::listen(JobExceptionOccurred::class, [ExceptionListener::class, 'handleJobException']);
        }
    }

    protected function configureLogChannel(): void
    {
        $channelName = config('healing-factor.log_channel', 'healing-factor');

        if (! config("logging.channels.{$channelName}")) {
            config(["logging.channels.{$channelName}" => [
                'driver' => 'daily',
                'path' => storage_path('logs/healing-factor.log'),
                'level' => 'debug',
                'days' => 14,
            ]]);
        }
    }
}
