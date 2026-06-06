<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserApiController extends ApiController
{
    /**
     * Display the authenticated user details.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'created_at' => $user->created_at,
            'current_team' => $user->currentTeam ? [
                'id' => $user->currentTeam->id,
                'name' => $user->currentTeam->name,
                'slug' => $user->currentTeam->slug,
            ] : null,
            'token' => [
                'name' => $token->name,
                'scopes' => $token->abilities,
                'expires_at' => $token->expires_at,
            ],
        ]);
    }
}
