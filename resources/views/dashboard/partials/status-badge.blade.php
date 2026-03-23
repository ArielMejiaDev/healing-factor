@php
    $statusValue = $issue->status->value;
@endphp
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border border-border bg-muted/50 text-foreground">
    @if ($statusValue === 'pending')
        <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path stroke-linecap="round" d="M12 6v6l4 2"/>
        </svg>
    @elseif ($statusValue === 'resolving')
        <svg class="w-3 h-3 text-muted-foreground animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
        </svg>
    @elseif ($statusValue === 'resolved')
        <svg class="w-3 h-3 text-green-500" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="12" r="6"/>
        </svg>
    @elseif ($statusValue === 'failed')
        <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="12" r="6"/>
        </svg>
    @endif
    {{ ucfirst($statusValue) }}
</span>
