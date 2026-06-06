<?php

use App\Models\User;
use App\Models\Team;
use App\Models\Blog;
use App\Models\Category;
use App\Models\TokenActivityLog;
use App\Models\ActivityLog;
use Illuminate\Support\Str;

test('tokens can be created with scopes, team, and expiration', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);

    $response = $this
        ->actingAs($user)
        ->post(route('api-tokens.store'), [
            'name' => 'Test Token',
            'team_id' => $team->id,
            'abilities' => ['blogs:read', 'categories:read'],
            'expires_at' => 30,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'Test Token',
        'team_id' => $team->id,
    ]);

    $token = $user->tokens()->first();
    expect($token->abilities)->toEqual(['blogs:read', 'categories:read']);
    expect($token->expires_at)->not->toBeNull();
});

test('api endpoints enforce token scopes', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);

    // Create a read-only token
    $plainToken = 'framex_live_' . Str::random(40);
    $token = $user->tokens()->create([
        'name' => 'Read Only Token',
        'team_id' => $team->id,
        'token' => hash('sha256', $plainToken),
        'abilities' => ['blogs:read'],
    ]);

    // Test GET /api/v1/blogs is authorized
    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $plainToken)
        ->getJson('/api/v1/blogs');

    $response->assertStatus(200);

    // Test POST /api/v1/blogs is forbidden (lacks blogs:create scope)
    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $plainToken)
        ->postJson('/api/v1/blogs', [
            'title' => 'Unauthorised Post',
        ]);

    $response->assertStatus(403);
});

test('api endpoints enforce team isolation', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    
    $user->teams()->attach($teamA, ['role' => 'admin']);
    $user->teams()->attach($teamB, ['role' => 'admin']);

    // Create blog posts in both teams
    $blogA = Blog::factory()->create([
        'user_id' => $user->id,
        'team_id' => $teamA->id,
        'title' => 'Blog A',
        'slug' => 'blog-a',
    ]);
    
    $blogB = Blog::factory()->create([
        'user_id' => $user->id,
        'team_id' => $teamB->id,
        'title' => 'Blog B',
        'slug' => 'blog-b',
    ]);

    // Create token for Team A
    $plainToken = 'framex_live_' . Str::random(40);
    $token = $user->tokens()->create([
        'name' => 'Team A Token',
        'team_id' => $teamA->id,
        'token' => hash('sha256', $plainToken),
        'abilities' => ['blogs:read'],
    ]);

    // Query blogs via Team A token
    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $plainToken)
        ->getJson('/api/v1/blogs');

    $response->assertStatus(200);
    
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0]['title'])->toBe('Blog A');

    // Attempting to query Blog B directly (belongs to Team B) should fail
    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $plainToken)
        ->getJson('/api/v1/blogs/blog-b');

    $response->assertStatus(404);
});

test('api requests are logged and record first-use audits', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);

    $plainToken = 'framex_live_' . Str::random(40);
    $token = $user->tokens()->create([
        'name' => 'Logging Token',
        'team_id' => $team->id,
        'token' => hash('sha256', $plainToken),
        'abilities' => ['teams:read'],
    ]);

    // Verify no activity logs yet
    expect(TokenActivityLog::count())->toBe(0);

    // Call API
    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $plainToken)
        ->getJson('/api/v1/teams');

    $response->assertStatus(200);

    // Verify activity log is created
    expect(TokenActivityLog::count())->toBe(1);
    
    $log = TokenActivityLog::first();
    expect($log->token_id)->toBe($token->id);
    expect($log->endpoint)->toBe('api/v1/teams');
    expect($log->response_code)->toBe(200);

    // Verify token metadata updated
    $token = $token->fresh();
    expect($token->last_used_at)->not->toBeNull();
    expect($token->last_ip_address)->not->toBeNull();

    // Verify audit log registered for first use
    $this->assertDatabaseHas('activity_logs', [
        'team_id' => $team->id,
        'event' => 'api_token.used',
    ]);
});

test('api tokens can be rotated', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);

    $plainToken = 'framex_live_' . Str::random(40);
    $originalHash = hash('sha256', $plainToken);
    
    $token = $user->tokens()->create([
        'name' => 'Rotation Token',
        'team_id' => $team->id,
        'token' => $originalHash,
        'abilities' => ['teams:read'],
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('api-tokens.rotate', $token->id));

    $response->assertRedirect();
    $response->assertSessionHas('plain_text_token');

    $newToken = $response->session()->get('plain_text_token');
    expect($newToken)->not->toBe($plainToken);

    $token = $token->fresh();
    expect($token->token)->toBe(hash('sha256', $newToken));

    // Verify audit log registered for rotation
    $this->assertDatabaseHas('activity_logs', [
        'team_id' => $team->id,
        'event' => 'api_token.rotated',
    ]);
});
