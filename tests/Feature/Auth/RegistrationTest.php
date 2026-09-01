<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration provisions personal and business entities with chart of accounts', function () {
    $this->post(route('register.store'), [
        'name' => 'SaaS User',
        'email' => 'saas@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'business_name' => 'Toko SaaS',
    ])->assertRedirect();

    $user = User::query()->where('email', 'saas@example.com')->firstOrFail();

    expect($user->entities)->toHaveCount(2);

    $personal = $user->entities()->where('entities.type', 'personal')->first();
    $business = $user->entities()->where('entities.type', 'business')->first();

    expect($personal)->not->toBeNull()
        ->and($personal->name)->toBe('Personal')
        ->and($business)->not->toBeNull()
        ->and($business->name)->toBe('Toko SaaS')
        ->and($user->isOwnerOf($personal))->toBeTrue()
        ->and($user->isOwnerOf($business))->toBeTrue()
        ->and($personal->accounts()->count())->toBe(11)
        ->and($business->accounts()->count())->toBe(11);

    expect(session('active_entity_id'))->toBe($personal->id);
});

test('registration uses default business name when omitted', function () {
    $this->post(route('register.store'), [
        'name' => 'Default Biz User',
        'email' => 'default@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'default@example.com')->firstOrFail();
    $business = $user->entities()->where('entities.type', 'business')->first();

    expect($business?->name)->toBe('Bisnis Saya');
});

test('registered user can access dashboard with active entity', function () {
    $this->post(route('register.store'), [
        'name' => 'Dashboard User',
        'email' => 'dash@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('activeEntity.type', 'personal')
            ->where('entityType', 'personal'));
});
