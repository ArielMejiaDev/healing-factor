<?php

namespace ArielMejiaDev\HealingFactor\Models;

use ArielMejiaDev\HealingFactor\Database\Factories\IssueFactory;
use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * @property IssueStatus $status
 */
class Issue extends Model
{
    use HasFactory;

    protected $table = 'healing_factor_issues';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'payload' => 'array',
            'resolved_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // --- Atomic status transitions ---

    public function markResolving(): bool
    {
        return DB::transaction(function () {
            return static::query()
                ->whereKey($this->id)
                ->where('status', IssueStatus::Pending)
                ->update(['status' => IssueStatus::Resolving]) === 1;
        });
    }

    public function markResolved(?string $prUrl = null): bool
    {
        $data = [
            'status' => IssueStatus::Resolved,
            'resolved_at' => now(),
        ];
        if ($prUrl) {
            $data['pr_url'] = $prUrl;
        }

        try {
            return static::query()
                ->whereKey($this->id)
                ->where('status', IssueStatus::Resolving)
                ->update($data) === 1;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function markFailed(string $reason): bool
    {
        return static::query()
            ->whereKey($this->id)
            ->whereIn('status', [IssueStatus::Pending, IssueStatus::Resolving])
            ->update([
                'status' => IssueStatus::Failed,
                'failure_reason' => $reason,
                'failed_at' => now(),
            ]) === 1;
    }

    public function markPending(): bool
    {
        return static::query()
            ->whereKey($this->id)
            ->where('status', IssueStatus::Failed)
            ->update([
                'status' => IssueStatus::Pending,
                'failure_reason' => null,
                'failed_at' => null,
            ]) === 1;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    // --- Scopes ---

    public function scopePending($query)
    {
        return $query->where('status', IssueStatus::Pending);
    }

    public function scopeResolving($query)
    {
        return $query->where('status', IssueStatus::Resolving);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', IssueStatus::Resolved);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', IssueStatus::Failed);
    }

    public function scopeStale($query, int $days = 30)
    {
        return $query->whereIn('status', [IssueStatus::Resolved, IssueStatus::Failed])
            ->where('updated_at', '<', now()->subDays($days));
    }

    public function scopeStaleResolving($query, int $minutes = 0)
    {
        $timeout = $minutes > 0 ? $minutes : (int) ceil(config('healing-factor.process.timeout', 3600) / 60) + 10;

        return $query->where('status', IssueStatus::Resolving)
            ->where('updated_at', '<', now()->subMinutes($timeout));
    }

    // --- Helpers ---

    public function isPending(): bool
    {
        return $this->status === IssueStatus::Pending;
    }

    public function isResolving(): bool
    {
        return $this->status === IssueStatus::Resolving;
    }

    protected static function newFactory()
    {
        return IssueFactory::new();
    }
}
