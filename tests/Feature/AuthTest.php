<?php
declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

it('registers an owner successfully', function (): void {
    $response = $this->postJson('/api/auth/register-owner', [
        'gym_name' => 'Fit Studio',
        'gym_email' => 'owner@gym.com',
        'gym_phone' => '+1234567890',
        'gym_address' => '123 Fitness Way',
        'owner_name' => 'Owner Name',
        'owner_email' => 'owner@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true, 'message' => 'Owner registered successfully.']);

    expect($response->json('data.token'))->toBeString();
    expect($response->json('data.user.email'))->toBe('owner@example.com');

    Notification::assertSentTo(User::where('email', 'owner@example.com')->first(), VerifyEmailNotification::class);
});

it('returns validation errors when owner registration fails', function (): void {
    $response = $this->postJson('/api/auth/register-owner', [
        'gym_name' => '',
        'gym_email' => 'invalid-email',
        'owner_name' => '',
        'owner_email' => 'not-an-email',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Validation failed.'])
        ->assertJsonStructure(['success', 'message', 'errors' => ['gym_name', 'gym_email', 'gym_phone', 'gym_address', 'owner_name', 'owner_email', 'password']]);
});

it('allows a verified user to login successfully', function (): void {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => bcrypt('secure-password'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'secure-password',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Login successful.'])
        ->assertJsonStructure(['success', 'message', 'data' => ['token', 'token_type', 'user']]);
});

it('rejects login with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => bcrypt('secure-password'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'message' => 'Invalid credentials.']);
});

it('rejects login when email is not verified', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => bcrypt('secure-password'),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'secure-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'message' => 'Your email address is not verified.']);
});

it('logs out the current token only', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $first = $user->createToken('first-token');
    $second = $user->createToken('second-token');

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(1);
});

it('logs out from all devices', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $user->createToken('first-token');
    $user->createToken('second-token');

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/auth/logout-all');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Logged out from all devices successfully.']);

    expect($user->tokens()->count())->toBe(0);
});

it('returns authenticated user information', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['success', 'message', 'data' => ['id', 'name', 'email', 'role', 'gym']]);
});

it('verifies email successfully via signed url', function (): void {
    $user = User::factory()->unverified()->create();

    Notification::assertNothingSent();

    $user->notify(new VerifyEmailNotification());

    Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification, $channels) use ($user, &$url) {
        $mailMessage = $notification->toMail($user);
        $url = $mailMessage->actionUrl;

        return true;
    });

    $response = $this->getJson($url);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Email verified successfully.']);
});

it('sends a password reset email without revealing account existence', function (): void {
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $response = $this->postJson('/api/auth/forgot-password', ['email' => 'owner@example.com']);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('resets the password successfully', function (): void {
    $user = User::factory()->create(['email' => 'owner@example.com']);
    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'owner@example.com',
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Password has been reset successfully.']);
});

it('changes password for authenticated users', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('current-password'),
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/auth/change-password', [
        'current_password' => 'current-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Password changed successfully.']);
});
