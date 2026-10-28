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

test('registration provisions only a personal entity', function () {
    $this->post(route('register.store'), [
        'name' => 'SaaS User',
        'email' => 'saas@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'saas@example.com')->firstOrFail();

    expect($user->entities)->toHaveCount(1);

    $personal = $user->entities()->where('entities.type', 'personal')->first();

    expect($personal)->not->toBeNull()
        ->and($personal->name)->toBe('Personal')
        ->and($user->entities()->where('entities.type', 'business')->exists())->toBeFalse()
        ->and($user->isOwnerOf($personal))->toBeTrue()
        ->and($personal->accounts()->count())->toBe(16);

    expect(session('active_entity_id'))->toBe($personal->id);
});

test('registered user can create a business entity later', function () {
    $this->post(route('register.store'), [
        'name' => 'Later Biz User',
        'email' => 'later@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->post(route('entities.store'), [
        'name' => 'Toko SaaS',
        'type' => 'business',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'later@example.com')->firstOrFail();
    $business = $user->entities()->where('entities.type', 'business')->first();

    expect($business)->not->toBeNull()
        ->and($business->name)->toBe('Toko SaaS')
        ->and($user->isOwnerOf($business))->toBeTrue()
        ->and($business->accounts()->count())->toBe(16);
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
