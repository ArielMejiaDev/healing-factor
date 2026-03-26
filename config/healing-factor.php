<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Error\OutOfMemoryError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    | Master switch. Set to false to disable all Healing-Factor processing.
    */
    'enabled' => env('HEALING_FACTOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    | Healing-Factor will only process exceptions in these environments.
    | Empty array = all environments allowed.
    */
    'environments' => ['production', 'staging'],

    /*
    |--------------------------------------------------------------------------
    | Dry Run
    |--------------------------------------------------------------------------
    | When true, Healing-Factor logs what it would do but does not execute the CLI tool.
    */
    'dry_run' => env('HEALING_FACTOR_DRY_RUN', false),

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    | Default resolution driver: 'cli' or 'api' (Anthropic API with tool_use)
    */
    'driver' => env('HEALING_FACTOR_DRIVER', 'cli'),

    /*
    |--------------------------------------------------------------------------
    | CLI Tool
    |--------------------------------------------------------------------------
    | Default CLI tool: 'claude' or 'opencode'
    */
    'cli_tool' => env('HEALING_FACTOR_CLI_TOOL', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | Default AI model to use. null = let the tool decide.
    */
    'model' => env('HEALING_FACTOR_MODEL', null),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    | NEVER commit these to version control. Use .env exclusively.
    */
    'api_keys' => [
        'anthropic' => env('ANTHROPIC_API_KEY'),
        'github_pat' => env('HEALING_FACTOR_GITHUB_PAT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Driver
    |--------------------------------------------------------------------------
    | Used when driver is 'api'. Calls the Anthropic Messages API directly
    | with tool_use, so no CLI tool installation is required on the server.
    */
    'api' => [
        'model' => env('HEALING_FACTOR_API_MODEL', 'claude-sonnet-4-6'),
        'models' => [
            'claude-sonnet-4-6',
            'claude-opus-4-6',
            'claude-haiku-4-5',
        ],
        'max_tokens' => (int) env('HEALING_FACTOR_API_MAX_TOKENS', 8192),
        'max_turns' => (int) env('HEALING_FACTOR_API_MAX_TURNS', 25),
        'allowed_commands' => [
            'git',
            'php artisan test',
            './vendor/bin/pest',
            './vendor/bin/phpunit',
            'composer dump-autoload',
            'gh pr create',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('HEALING_FACTOR_QUEUE_CONNECTION', null),
        'name' => env('HEALING_FACTOR_QUEUE_NAME', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Process
    |--------------------------------------------------------------------------
    */
    'process' => [
        'timeout' => (int) env('HEALING_FACTOR_PROCESS_TIMEOUT', 3600),
        'max_turns' => (int) env('HEALING_FACTOR_MAX_TURNS', 25),
        'working_directory' => null, // null = base_path()
    ],

    /*
    |--------------------------------------------------------------------------
    | PR Configuration
    |--------------------------------------------------------------------------
    */
    'pr' => [
        'draft' => true,
        'labels' => ['healing-factor', 'auto-fix'],
        'reviewers' => [],
        'branch_prefix' => 'healing-factor/fix',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitor Strategy
    |--------------------------------------------------------------------------
    | One active at a time. Supported: 'nightwatch', 'bugsnag', 'exception_listener'
    */
    'monitor' => env('HEALING_FACTOR_MONITOR', 'nightwatch'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'path' => env('HEALING_FACTOR_WEBHOOK_PATH', 'healing-factor/webhook'),
        'secret' => env('HEALING_FACTOR_WEBHOOK_SECRET'),
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debounce
    |--------------------------------------------------------------------------
    | Minimum minutes between processing the same exception fingerprint.
    */
    'debounce_minutes' => (int) env('HEALING_FACTOR_DEBOUNCE_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    | Number of days to keep resolved/failed issues before pruning.
    */
    'retention_days' => (int) env('HEALING_FACTOR_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('HEALING_FACTOR_LOG_CHANNEL', 'healing-factor'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'enabled' => env('HEALING_FACTOR_DASHBOARD_ENABLED', true),
        'path' => env('HEALING_FACTOR_DASHBOARD_PATH', 'healing-factor'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Categories
    |--------------------------------------------------------------------------
    | Each category can override: cli_tool, model, timeout, max_turns, prompt.
    | Exceptions not in any category use the top-level defaults (failover).
    */
    'categories' => [

        'quick_fixes' => [
            'cli_tool' => 'claude',
            'model' => null,
            'timeout' => 1800,
            'max_turns' => 15,
            'prompt' => null,
            'exceptions' => [
                ErrorException::class,
                TypeError::class,
                ArgumentCountError::class,
                BadMethodCallException::class,
                InvalidArgumentException::class,
                QueryException::class,
                ModelNotFoundException::class,
                NotFoundHttpException::class,
                MethodNotAllowedHttpException::class,
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
            ],
        ],

        'complex_fixes' => [
            'cli_tool' => 'claude',
            'model' => null,
            'timeout' => 3600,
            'max_turns' => 30,
            'prompt' => null,
            'exceptions' => [
                LogicException::class,
                RuntimeException::class,
                DomainException::class,
                RangeException::class,
                UnexpectedValueException::class,
                OverflowException::class,
                UnderflowException::class,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    | Exceptions that should NEVER be sent to Healing-Factor (infrastructure/unfixable).
    */
    'ignored_exceptions' => [
        OutOfMemoryError::class,
        ThrottleRequestsException::class,
        HttpException::class,
        TokenMismatchException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Prompt (Fallback / Failover)
    |--------------------------------------------------------------------------
    | Used when no category matches. Set to null for the built-in default.
    */
    'prompt' => null,
];
