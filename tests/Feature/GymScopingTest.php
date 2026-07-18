<?php
declare(strict_types=1);

use App\Exceptions\NoAuthenticatedUserException;
use App\Exceptions\NoGymAssignedException;
use App\Scopes\GymScope;
use App\Services\Tenant\GymContext;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('returns authenticated user gym_id from gym context', function (): void {
    $gym = Gym::create([
        'name' => 'Gym A',
        'email' => 'a@gym.com',
        'phone' => '+1000000000',
        'address' => '100 Main St',
    ]);

    $user = User::factory()->create(['gym_id' => $gym->id]);

    Auth::login($user);

    $gymContext = app(GymContext::class);

    expect($gymContext->gymId())->toBe($gym->id);
    expect($gymContext->gym()->id)->toBe($gym->id);
    expect($gymContext->user()->id)->toBe($user->id);
});

it('throws exception when gym context has no authenticated user', function (): void {
    expect(fn () => app(GymContext::class)->user())->toThrow(NoAuthenticatedUserException::class);
});

it('throws exception when authenticated user has no gym assigned', function (): void {
    $user = User::factory()->create(['gym_id' => null]);

    Auth::login($user);

    expect(fn () => app(GymContext::class)->gymId())->toThrow(NoGymAssignedException::class);
});

it('only returns current gym records with global scope', function (): void {
    $gymA = Gym::create([
        'name' => 'Gym A',
        'email' => 'a@gym.com',
        'phone' => '+1000000000',
        'address' => '100 Main St',
    ]);

    $gymB = Gym::create([
        'name' => 'Gym B',
        'email' => 'b@gym.com',
        'phone' => '+2000000000',
        'address' => '200 Main St',
    ]);

    $user = User::factory()->create(['gym_id' => $gymA->id]);
    Auth::login($user);

    Member::create([
        'gym_id' => $gymA->id,
        'name' => 'A1',
        'email' => 'a1@gym.com',
        'phone' => '+1111111111',
        'gender' => 'male',
        'date_of_birth' => now()->subYears(25)->toDateString(),
        'status' => 'active',
    ]);
    Member::create([
        'gym_id' => $gymA->id,
        'name' => 'A2',
        'email' => 'a2@gym.com',
        'phone' => '+1222222222',
        'gender' => 'female',
        'date_of_birth' => now()->subYears(26)->toDateString(),
        'status' => 'active',
    ]);
    Member::withoutEvents(function () use ($gymB) {
        return Member::withoutGlobalScope(GymScope::class)->create([
            'gym_id' => $gymB->id,
            'name' => 'B1',
            'email' => 'b1@gym.com',
            'phone' => '+1333333333',
            'gender' => 'other',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'status' => 'active',
        ]);
    });

    expect(Member::count())->toBe(2);
    expect(Member::where('name', 'A1')->count())->toBe(1);
});

it('returns all records when withoutGlobalScope is used', function (): void {
    $gymA = Gym::create([
        'name' => 'Gym A',
        'email' => 'a@gym.com',
        'phone' => '+1000000000',
        'address' => '100 Main St',
    ]);

    $gymB = Gym::create([
        'name' => 'Gym B',
        'email' => 'b@gym.com',
        'phone' => '+2000000000',
        'address' => '200 Main St',
    ]);

    $user = User::factory()->create(['gym_id' => $gymA->id]);
    Auth::login($user);

    Member::create([
        'gym_id' => $gymA->id,
        'name' => 'A1',
        'email' => 'a1@gym.com',
        'phone' => '+1111111111',
        'gender' => 'male',
        'date_of_birth' => now()->subYears(25)->toDateString(),
        'status' => 'active',
    ]);
    Member::withoutEvents(function () use ($gymB) {
        return Member::withoutGlobalScope(GymScope::class)->create([
            'gym_id' => $gymB->id,
            'name' => 'B1',
            'email' => 'b1@gym.com',
            'phone' => '+1333333333',
            'gender' => 'other',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'status' => 'active',
        ]);
    });

    expect(Member::withoutGlobalScope(GymScope::class)->count())->toBe(2);
    expect(Member::withoutGlobalScope(GymScope::class)->where('name', 'B1')->count())->toBe(1);
});

it('ignores gym_id from request and always assigns authenticated gym_id', function (): void {
    $gymA = Gym::create([
        'name' => 'Gym A',
        'email' => 'a@gym.com',
        'phone' => '+1000000000',
        'address' => '100 Main St',
    ]);

    $gymB = Gym::create([
        'name' => 'Gym B',
        'email' => 'b@gym.com',
        'phone' => '+2000000000',
        'address' => '200 Main St',
    ]);

    $user = User::factory()->create(['gym_id' => $gymA->id]);
    Auth::login($user);

    $member = Member::create([
        'gym_id' => $gymB->id,
        'name' => 'Secure Member',
        'email' => 'secure@example.com',
        'phone' => '+1234567890',
        'gender' => 'male',
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'status' => 'active',
    ]);

    expect($member->gym_id)->toBe($gymA->id);
});

it('keeps queries chainable with gym scope', function (): void {
    $gym = Gym::create([
        'name' => 'Gym A',
        'email' => 'a@gym.com',
        'phone' => '+1000000000',
        'address' => '100 Main St',
    ]);

    $user = User::factory()->create(['gym_id' => $gym->id]);
    Auth::login($user);

    $member = Member::create([
        'gym_id' => $gym->id,
        'name' => 'Member One',
        'email' => 'member1@gym.com',
        'phone' => '+1555550001',
        'gender' => 'male',
        'date_of_birth' => now()->subYears(28)->toDateString(),
        'status' => 'active',
    ]);

    Subscription::create([
        'gym_id' => $gym->id,
        'member_id' => $member->id,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'status' => 'active',
        'plan_name' => 'Basic',
        'price' => 99.99,
    ]);
    Subscription::create([
        'gym_id' => $gym->id,
        'member_id' => $member->id,
        'start_date' => now()->subMonths(2)->toDateString(),
        'end_date' => now()->subMonth()->toDateString(),
        'status' => 'expired',
        'plan_name' => 'Pro',
        'price' => 149.99,
    ]);

    $subscriptionCount = Subscription::where('plan_name', 'Basic')->count();

    expect($subscriptionCount)->toBe(1);
});
