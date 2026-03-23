<?php

use ArielMejiaDev\XFactor\Enums\IssueStatus;

it('has the correct cases', function () {
    expect(IssueStatus::cases())->toHaveCount(4);
    expect(IssueStatus::Pending->value)->toBe('pending');
    expect(IssueStatus::Resolving->value)->toBe('resolving');
    expect(IssueStatus::Resolved->value)->toBe('resolved');
    expect(IssueStatus::Failed->value)->toBe('failed');
});

it('identifies terminal statuses correctly', function () {
    expect(IssueStatus::Pending->isTerminal())->toBeFalse();
    expect(IssueStatus::Resolving->isTerminal())->toBeFalse();
    expect(IssueStatus::Resolved->isTerminal())->toBeTrue();
    expect(IssueStatus::Failed->isTerminal())->toBeTrue();
});

it('can be created from string value', function () {
    expect(IssueStatus::from('pending'))->toBe(IssueStatus::Pending);
    expect(IssueStatus::from('resolving'))->toBe(IssueStatus::Resolving);
    expect(IssueStatus::from('resolved'))->toBe(IssueStatus::Resolved);
    expect(IssueStatus::from('failed'))->toBe(IssueStatus::Failed);
});

it('throws for invalid value', function () {
    IssueStatus::from('invalid');
})->throws(ValueError::class);
