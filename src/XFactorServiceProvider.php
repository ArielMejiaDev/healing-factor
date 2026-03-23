<?php

namespace ArielMejiaDev\XFactor;

use ArielMejiaDev\XFactor\Commands\XFactorInstallCommand;
use ArielMejiaDev\XFactor\Commands\XFactorPruneCommand;
use ArielMejiaDev\XFactor\Commands\XFactorRecoverStaleCommand;
use ArielMejiaDev\XFactor\Commands\XFactorRetryCommand;
use ArielMejiaDev\XFactor\Commands\XFactorStatusCommand;
use ArielMejiaDev\XFactor\Commands\XFactorTestCommand;
use ArielMejiaDev\XFactor\Contracts\MonitorContract;
use ArielMejiaDev\XFactor\Contracts\PromptBuilderContract;
use ArielMejiaDev\XFactor\Enums\MonitorTool;
use ArielMejiaDev\XFactor\Listeners\ExceptionListener;
use ArielMejiaDev\XFactor\Monitors\BugsnagMonitor;
use ArielMejiaDev\XFactor\Monitors\NightwatchMonitor;
use ArielMejiaDev\XFactor\Prompts\PromptBuilder;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class XFactorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('x-factor')
            ->hasConfigFile()
            ->hasMigration('create_x_factor_issues_table')
            ->hasViews()
            ->hasRoute('webhooks')
            ->hasRoute('dashboard')
            ->hasCommands([
                XFactorInstallCommand::class,
                XFactorStatusCommand::class,
                XFactorRetryCommand::class,
                XFactorPruneCommand::class,
                XFactorRecoverStaleCommand::class,
                XFactorTestCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(XFactorManager::class, fn ($app) => new XFactorManager($app));

        $this->app->singleton(XFactor::class, fn ($app) => new XFactor(
            $app->make(XFactorManager::class),
        ));

        $this->app->bind(MonitorContract::class, function () {
            $monitor = MonitorTool::from(config('x-factor.monitor', 'nightwatch'));

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

        if (config('x-factor.monitor') === 'exception_listener') {
            Event::listen(MessageLogged::class, [ExceptionListener::class, 'handleMessageLogged']);
            Event::listen(JobExceptionOccurred::class, [ExceptionListener::class, 'handleJobException']);
        }
    }

    protected function configureLogChannel(): void
    {
        $channelName = config('x-factor.log_channel', 'x-factor');

        if (! config("logging.channels.{$channelName}")) {
            config(["logging.channels.{$channelName}" => [
                'driver' => 'daily',
                'path' => storage_path('logs/x-factor.log'),
                'level' => 'debug',
                'days' => 14,
            ]]);
        }
    }
}
