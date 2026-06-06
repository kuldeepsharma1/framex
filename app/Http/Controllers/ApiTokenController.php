<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TokenActivityLog;
use App\Models\ActivityLog;
use App\Models\PersonalAccessToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ApiTokenController extends Controller
{
    /**
     * Central repository of granular permission scopes.
     */
    public static array $scopes = [
        'blogs:read' => 'Read blogs list and specific blog posts',
        'blogs:create' => 'Create new blog posts',
        'blogs:update' => 'Modify existing blog posts',
        'blogs:delete' => 'Permanently delete blog posts',
        'categories:read' => 'Read categories list and details',
        'categories:create' => 'Create new categories',
        'categories:update' => 'Modify existing categories',
        'categories:delete' => 'Permanently delete categories',
        'tags:read' => 'Read tags list and details',
        'tags:create' => 'Create new tags',
        'tags:update' => 'Modify existing tags',
        'tags:delete' => 'Permanently delete tags',
        'teams:read' => 'Read team settings and details',
        'teams:update' => 'Modify team settings',
        'members:read' => 'Read team members and pending invitations',
        'members:invite' => 'Send email invitations to join the team',
        'activity:read' => 'Access team audit logs programmatically',
    ];

    /**
     * Display listing of API tokens and developer dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Fetch personal access tokens
        $tokens = $user->tokens()
            ->with('team')
            ->latest()
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'team' => $token->team ? [
                    'id' => $token->team->id,
                    'name' => $token->team->name,
                ] : null,
                'abilities' => $token->abilities,
                'last_ip_address' => $token->last_ip_address,
                'last_user_agent' => $token->last_user_agent ? Str::limit($token->last_user_agent, 40) : null,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'expires_at' => $token->expires_at?->toISOString(),
                'created_at' => $token->created_at->diffForHumans(),
                'is_expired' => $token->expires_at ? $token->expires_at->isPast() : false,
            ]);

        // 2. Fetch User's teams
        $teams = $user->teams()
            ->get()
            ->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
            ]);

        // 3. Usage monitoring dashboard stats (Phase 6)
        $tokenIds = $user->tokens()->pluck('id');
        
        $totalRequestsToday = TokenActivityLog::whereIn('token_id', $tokenIds)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $topEndpoints = TokenActivityLog::whereIn('token_id', $tokenIds)
            ->select('endpoint', 'method', \DB::raw('count(*) as count'))
            ->groupBy('endpoint', 'method')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $recentLogs = TokenActivityLog::whereIn('token_id', $tokenIds)
            ->with('token')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'token_name' => $log->token?->name ?? 'Unknown Token',
                'endpoint' => $log->endpoint,
                'method' => $log->method,
                'response_code' => $log->response_code,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('api-tokens/index', [
            'tokens' => $tokens,
            'teams' => $teams,
            'availableScopes' => self::$scopes,
            'plainTextToken' => $request->session()->pull('plain_text_token'),
            'usageStats' => [
                'totalRequestsToday' => $totalRequestsToday,
                'topEndpoints' => $topEndpoints,
                'recentLogs' => $recentLogs,
            ]
        ]);
    }

    /**
     * Store a newly created API token in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'team_id' => ['required', 'exists:teams,id'],
            'abilities' => ['required', 'array'],
            'abilities.*' => ['string', 'in:' . implode(',', array_keys(self::$scopes))],
            'expires_at' => ['nullable', 'integer', 'in:7,30,90,365'],
        ]);

        // Check if user belongs to the selected team
        $team = Team::findOrFail($validated['team_id']);
        if (!$request->user()->belongsToTeam($team)) {
            abort(403, 'Unauthorized team.');
        }

        // Generate live token secret
        $plainTextToken = 'framex_live_' . Str::random(40);
        $hashedToken = hash('sha256', $plainTextToken);

        $expiresAt = $validated['expires_at']
            ? now()->addDays((int) $validated['expires_at'])
            : null;

        $token = $request->user()->tokens()->create([
            'name' => $validated['name'],
            'team_id' => $team->id,
            'token' => $hashedToken,
            'abilities' => $validated['abilities'],
            'expires_at' => $expiresAt,
        ]);

        return back()->with('plain_text_token', $plainTextToken);
    }

    /**
     * Rotate a token's secret key.
     */
    public function rotate(Request $request, int $tokenId): RedirectResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->firstOrFail();

        // Generate new key
        $plainTextToken = 'framex_live_' . Str::random(40);
        $hashedToken = hash('sha256', $plainTextToken);

        $token->update([
            'token' => $hashedToken,
        ]);

        // Audit Log
        if (isset($token->team_id)) {
            try {
                $activity = ActivityLog::create([
                    'team_id' => $token->team_id,
                    'user_id' => $request->user()->id,
                    'event' => 'api_token.rotated',
                    'description' => "API token '{$token->name}' was rotated.",
                    'metadata' => [
                        'token_id' => $token->id,
                    ],
                ]);
                \App\Events\TeamActivityRecorded::dispatch($activity);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return back()->with('plain_text_token', $plainTextToken);
    }

    /**
     * Revoke the specified API token.
     */
    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back();
    }
}
