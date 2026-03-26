@extends('healing-factor::dashboard.layout')

@section('content')
    <div class="space-y-6">
        {{-- Back link --}}
        <a href="{{ route('healing-factor.dashboard.index') }}" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to issues
        </a>

        <h1 class="text-xl font-semibold tracking-tight">Issue #{{ $issue->id }}</h1>

        {{-- Resolving callout --}}
        @if (in_array($issue->status->value, ['pending', 'resolving']))
            <div class="flex items-center gap-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/40 p-4">
                <svg class="size-5 shrink-0 text-blue-600 dark:text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Resolution in progress</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">This issue is being processed. Refresh the page to check for updates.</p>
                </div>
            </div>
        @endif

        {{-- Overview --}}
        <div class="rounded-lg border border-border p-6 space-y-4">
            <h2 class="mb-0.5 text-base font-medium">Overview</h2>
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-sm text-muted-foreground">Status</dt>
                    <dd class="mt-1">
                        @include('healing-factor::dashboard.partials.status-badge', ['issue' => $issue])
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Source</dt>
                    <dd class="mt-1">{{ $issue->source }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Category</dt>
                    <dd class="mt-1">{{ $issue->category ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Attempts</dt>
                    <dd class="mt-1">{{ $issue->attempts }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Created</dt>
                    <dd class="mt-1">{{ $issue->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Updated</dt>
                    <dd class="mt-1">{{ $issue->updated_at->format('Y-m-d H:i:s') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Exception --}}
        <div class="rounded-lg border border-border p-6 space-y-4">
            <h2 class="mb-0.5 text-base font-medium">Exception</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-sm text-muted-foreground">Class</dt>
                    <dd class="mt-1 font-mono">{{ $issue->exception_class }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Message</dt>
                    <dd class="mt-1">{{ $issue->exception_message }}</dd>
                </div>
                @if ($issue->stacktrace)
                    <div>
                        <dt class="text-sm text-muted-foreground">Stacktrace</dt>
                        <dd class="mt-1">
                            <pre class="p-4 bg-muted rounded-lg text-xs overflow-x-auto">{{ $issue->stacktrace }}</pre>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Resolution --}}
        <div class="rounded-lg border border-border p-6 space-y-4">
            <h2 class="mb-0.5 text-base font-medium">Resolution</h2>
            <dl class="space-y-3 text-sm">
                @if ($issue->branch_name)
                    <div>
                        <dt class="text-sm text-muted-foreground">Branch</dt>
                        <dd class="mt-1 font-mono">{{ $issue->branch_name }}</dd>
                    </div>
                @endif
                @if ($issue->pr_url)
                    <div>
                        <dt class="text-sm text-muted-foreground">Pull Request</dt>
                        <dd class="mt-1">
                            <a href="{{ $issue->pr_url }}" target="_blank" rel="noopener" class="text-muted-foreground hover:text-foreground hover:underline transition-colors">
                                {{ $issue->pr_url }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if ($issue->cli_output)
                    <div>
                        <dt class="text-sm text-muted-foreground">CLI Output</dt>
                        <dd class="mt-1">
                            <pre class="p-4 bg-muted rounded-lg text-xs overflow-x-auto">{{ $issue->cli_output }}</pre>
                        </dd>
                    </div>
                @endif
                @if ($issue->cli_error_output)
                    <div>
                        <dt class="text-sm text-muted-foreground">CLI Error Output</dt>
                        <dd class="mt-1">
                            <pre class="p-4 bg-muted rounded-lg text-xs overflow-x-auto">{{ $issue->cli_error_output }}</pre>
                        </dd>
                    </div>
                @endif
                @if ($issue->failure_reason)
                    <div>
                        <dt class="text-sm text-muted-foreground">Failure Reason</dt>
                        <dd class="mt-1 text-red-600 dark:text-red-400">{{ $issue->failure_reason }}</dd>
                    </div>
                @endif
                @if (! $issue->branch_name && ! $issue->pr_url && ! $issue->cli_output && ! $issue->failure_reason)
                    <p class="text-sm text-muted-foreground">No resolution data yet.</p>
                @endif
            </dl>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="flex items-center gap-3 w-full rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-foreground">
                <svg class="h-5 w-5 shrink-0 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="flex-1">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center gap-3 w-full rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-foreground">
                <svg class="h-5 w-5 shrink-0 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="flex-1">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Retry --}}
        @if ($issue->status->value === 'failed')
            <div class="rounded-lg border border-border p-6 space-y-4">
                <h2 class="mb-0.5 text-base font-medium">Retry Resolution</h2>
                <p class="text-sm text-muted-foreground">Re-queue this issue with a different model or turn limit.</p>

                <form method="POST" action="{{ route('healing-factor.dashboard.retry', $issue) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="retry-model" class="block text-sm font-medium text-foreground mb-1">Model</label>
                            <select id="retry-model" name="model" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">
                                @foreach (config('healing-factor.api.models', []) as $model)
                                    <option value="{{ $model }}" @selected($model === config('healing-factor.api.model'))>{{ $model }}</option>
                                @endforeach
                            </select>
                            @error('model')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="retry-max-turns" class="block text-sm font-medium text-foreground mb-1">Max Turns</label>
                            <input type="number" id="retry-max-turns" name="max_turns" value="{{ config('healing-factor.api.max_turns', 25) }}" min="5" max="50" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">
                            @error('max_turns')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2">
                        Retry Issue
                    </button>
                </form>
            </div>
        @endif

        {{-- Payload --}}
        @if ($issue->payload)
            <div class="rounded-lg border border-border p-6 space-y-4">
                <h2 class="mb-0.5 text-base font-medium">Payload</h2>
                <pre class="p-4 bg-muted rounded-lg text-xs overflow-x-auto">{{ json_encode($issue->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
@endsection
