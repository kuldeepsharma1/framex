<?php

use App\Models\User;
use Illuminate\Support\Str;

test('a user can mark their own notification as read', function () {
    $user = User::factory()->create();

    $notification = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => [
            'title' => 'Test Title',
            'body' => 'Test Body',
            'action_url' => '/dashboard',
        ],
        'read_at' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('notifications.read', $notification->id));

    $response->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another user\'s notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $notification = $otherUser->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => [
            'title' => 'Other Title',
            'body' => 'Other Body',
        ],
        'read_at' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('notifications.read', $notification->id));

    $response->assertNotFound();
    expect($notification->fresh()->read_at)->toBeNull();
});

test('a user can mark their own notification as read and redirect to target URL', function () {
    $user = User::factory()->create();

    $notification = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => [
            'title' => 'Test Title',
            'body' => 'Test Body',
            'action_url' => '/dashboard',
        ],
        'read_at' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('notifications.read', $notification->id), [
            'redirect_url' => '/dashboard',
        ]);

    $response->assertRedirect('/dashboard');
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user can mark all notifications as read efficiently', function () {
    $user = User::factory()->create();

    $notification1 = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Title 1'],
        'read_at' => null,
    ]);

    $notification2 = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Title 2'],
        'read_at' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('notifications.read-all'));

    $response->assertRedirect();
    expect($notification1->fresh()->read_at)->not->toBeNull();
    expect($notification2->fresh()->read_at)->not->toBeNull();
});
