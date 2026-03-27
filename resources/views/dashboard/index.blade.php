@extends('healing-factor::dashboard.layout')

@section('content')
    <div class="space-y-6">
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

        {{-- Search --}}
        <form method="GET" action="{{ route('healing-factor.dashboard.index') }}" class="flex gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search issues..."
                class="flex-1 placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
            >
            <button type="submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2 shrink-0">
                Search
            </button>
            @if (request('search'))
                <a href="{{ route('healing-factor.dashboard.index', array_filter(['status' => request('status')])) }}" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-4 py-2 shrink-0">
                    Clear
                </a>
            @endif
        </form>

        {{-- Status filter tabs --}}
        @php
            $currentStatus = request('status');
            $total = $statusCounts->sum();
            $tabs = [
                '' => ['label' => 'All', 'count' => $total],
                'pending' => ['label' => 'Pending', 'count' => $statusCounts->get('pending', 0)],
                'resolving' => ['label' => 'Resolving', 'count' => $statusCounts->get('resolving', 0)],
                'resolved' => ['label' => 'Resolved', 'count' => $statusCounts->get('resolved', 0)],
                'failed' => ['label' => 'Failed', 'count' => $statusCounts->get('failed', 0)],
            ];
        @endphp
        <nav class="flex gap-1 flex-wrap" aria-label="Status filters">
            @foreach ($tabs as $status => $tab)
                <a
                    href="{{ route('healing-factor.dashboard.index', array_filter(['status' => $status, 'search' => request('search')])) }}"
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] hover:bg-accent hover:text-accent-foreground dark:hover:bg-accent/50 h-9 px-4 py-2
                        {{ $currentStatus === ($status ?: null) ? 'bg-muted' : '' }}"
                >
                    {{ $tab['label'] }}
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs rounded-md text-muted-foreground">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- Debounce callout --}}
        <div class="flex items-start gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>Duplicate exceptions are debounced — the same issue won't appear twice within <strong class="text-foreground">{{ config('healing-factor.debounce_minutes', 5) }} {{ Str::plural('minute', config('healing-factor.debounce_minutes', 5)) }}</strong>, even if triggered by multiple environments simultaneously.</p>
        </div>

        {{-- Issues table --}}
        <div class="rounded-lg border border-border">
            <table class="w-full text-sm">
                <thead class="bg-muted text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Issue</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Source</th>
                        <th class="px-4 py-3 font-medium">Attempts</th>
                        <th class="px-4 py-3 font-medium">PR</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($issues as $issue)
                        @php $isActiveResolving = $loop->first && $issue->status->value === 'resolving'; @endphp
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-4 py-3 tabular-nums {{ $isActiveResolving ? 'animate-pulse' : '' }}">{{ $issue->id }}</td>
                            <td class="px-4 py-3 {{ $isActiveResolving ? 'animate-pulse' : '' }}">
                                <a href="{{ route('healing-factor.dashboard.show', $issue) }}" class="hover:underline">
                                    <div class="font-medium">{{ $issue->exception_class }}</div>
                                    <div class="text-sm text-muted-foreground truncate max-w-xs">{{ $issue->exception_message }}</div>
                                </a>
                            </td>
                            <td class="px-4 py-3 {{ $isActiveResolving ? 'animate-pulse' : '' }}">
                                @include('healing-factor::dashboard.partials.status-badge', ['issue' => $issue])
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground {{ $isActiveResolving ? 'animate-pulse' : '' }}">{{ $issue->source }}</td>
                            <td class="px-4 py-3 tabular-nums {{ $isActiveResolving ? 'animate-pulse' : '' }}">{{ $issue->attempts }}</td>
                            <td class="px-4 py-3 {{ $isActiveResolving ? 'animate-pulse' : '' }}">
                                @if ($issue->pr_url)
                                    <a href="{{ $issue->pr_url }}" target="_blank" rel="noopener" class="text-muted-foreground hover:text-foreground transition-colors" title="View PR">
                                        <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-sm text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap {{ $isActiveResolving ? 'animate-pulse' : '' }}">{{ $issue->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button
                                        onclick="this.closest('[x-data]').dataset.open = this.closest('[x-data]').dataset.open === 'true' ? 'false' : 'true'; this.closest('[x-data]').querySelector('[role=menu]').classList.toggle('hidden')"
                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] hover:bg-accent hover:text-accent-foreground h-8 w-8"
                                    >
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="1.5"/>
                                            <circle cx="12" cy="12" r="1.5"/>
                                            <circle cx="12" cy="19" r="1.5"/>
                                        </svg>
                                    </button>
                                    <div role="menu" class="hidden absolute right-0 z-50 mt-1 w-44 origin-top-right rounded-md border border-border bg-background shadow-lg">
                                        <div class="py-1">
                                            <a href="{{ route('healing-factor.dashboard.show', $issue) }}" role="menuitem" class="block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                                                View details
                                            </a>
                                            @if ($issue->status->value === 'failed')
                                                <form method="POST" action="{{ route('healing-factor.dashboard.retry', $issue) }}">
                                                    @csrf
                                                    <input type="hidden" name="model" value="{{ config('healing-factor.api.model', 'claude-sonnet-4-6') }}">
                                                    <input type="hidden" name="max_turns" value="{{ config('healing-factor.api.max_turns', 25) }}">
                                                    <button type="submit" role="menuitem" class="w-full text-left px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                                                        Retry
                                                    </button>
                                                </form>
                                            @endif
                                            @if (in_array($issue->status->value, ['pending', 'resolving']))
                                                <form method="POST" action="{{ route('healing-factor.dashboard.mark-failed', $issue) }}">
                                                    @csrf
                                                    <button type="submit" role="menuitem" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-accent hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                                        Mark as failed
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                No issues found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $issues->links() }}
        </div>
    </div>
@endsection
