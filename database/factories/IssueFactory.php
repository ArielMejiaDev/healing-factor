<?php

namespace ArielMejiaDev\HealingFactor\Database\Factories;

use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        return [
            'fingerprint' => hash('sha256', $this->faker->unique()->uuid()),
            'source' => 'nightwatch',
            'title' => $this->faker->sentence(),
            'exception_class' => \ErrorException::class,
            'status' => IssueStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => IssueStatus::Pending]);
    }

    public function resolving(): static
    {
        return $this->state(['status' => IssueStatus::Resolving]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => IssueStatus::Resolved, 'resolved_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(['status' => IssueStatus::Failed, 'failure_reason' => 'Test failure', 'failed_at' => now()]);
    }
}
