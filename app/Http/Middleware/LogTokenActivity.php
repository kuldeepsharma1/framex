<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TokenActivityLog;
use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\Response;

class LogTokenActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();
        
        if ($user && $token = $user->currentAccessToken()) {
            $lastUsedWasNull = ($token->last_used_at === null);

            // Update token metadata
            $token->forceFill([
                'last_ip_address' => $request->ip(),
                'last_user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ])->save();

            // Create API activity log
            TokenActivityLog::create([
                'token_id' => $token->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'response_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Audit Log: First use of the token
            if ($lastUsedWasNull && isset($token->team_id)) {
                try {
                    ActivityLog::create([
                        'team_id' => $token->team_id,
                        'user_id' => $user->id,
                        'event' => 'api_token.used',
                        'description' => "API token '{$token->name}' was used for the first time.",
                        'metadata' => [
                            'token_id' => $token->id,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // Fail silently
                }
            }
        }
    }
}
