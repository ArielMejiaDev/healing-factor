<?php

namespace ArielMejiaDev\HealingFactor\Http\Controllers;

use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Issue::query()->latest();

        if ($status = $request->query('status')) {
            $scope = $status;
            if (method_exists(Issue::class, 'scope'.ucfirst($scope))) {
                $query->{$scope}();
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('exception_class', 'LIKE', "%{$search}%")
                    ->orWhere('exception_message', 'LIKE', "%{$search}%");
            });
        }

        $statusCounts = Issue::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $issues = $query->paginate(25)->appends($request->query());

        return view('healing-factor::dashboard.index', compact('issues', 'statusCounts'));
    }

    public function show(Issue $issue)
    {
        return view('healing-factor::dashboard.show', compact('issue'));
    }

    public function retry(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'model' => ['required', Rule::in(config('healing-factor.api.models', []))],
            'max_turns' => ['required', 'integer', 'min:5', 'max:50'],
        ]);

        if (! $issue->markPending()) {
            return back()->with('error', 'Issue is not in a failed state.');
        }

        ResolveIssue::dispatch($issue, $validated);

        return redirect()->route('healing-factor.dashboard.show', $issue)
            ->with('success', 'Issue queued for retry.');
    }

    public function markFailed(Issue $issue)
    {
        if (! $issue->markFailed('Manually marked as failed from the dashboard.')) {
            return back()->with('error', 'Issue cannot be marked as failed (already resolved or failed).');
        }

        return back()->with('success', 'Issue marked as failed.');
    }
}
