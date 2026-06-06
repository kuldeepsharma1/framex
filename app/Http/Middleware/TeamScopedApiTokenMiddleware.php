<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamScopedApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $token = $user->currentAccessToken();

        // Check if token has team_id
        if ($token && isset($token->team_id)) {
            $team = \App\Models\Team::find($token->team_id);
            if (!$team || !$user->belongsToTeam($team)) {
                return response()->json([
                    'message' => 'Forbidden. Token is not authorized for this team.'
                ], 403);
            }

            // Set current team context in memory
            $user->forceFill(['current_team_id' => $team->id]);
            $user->setRelation('currentTeam', $team);
        } else {
            return response()->json([
                'message' => 'Forbidden. Token lacks team scope.'
            ], 403);
        }

        return $next($request);
    }
}
