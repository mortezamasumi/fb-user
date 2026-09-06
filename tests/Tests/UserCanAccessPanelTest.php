<?php

use Filament\Facades\Filament;
use Mortezamasumi\FbUser\Tests\Services\User;

it('denies panel access to inactive users', function () {
    $user = User::factory()->create(['active' => false]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('denies panel access to expired users', function () {
    $user = User::factory()->create(['expiration_date' => now()->subDay()]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('grants panel access to active users without an expiration date', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('grants panel access to active users with a future expiration date', function () {
    $user = User::factory()->create(['expiration_date' => now()->addMonth()]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});
