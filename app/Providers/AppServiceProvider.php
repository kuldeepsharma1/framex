<?php

namespace App\Providers;

use App\Events\TeamActivityRecorded;
use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Subscription;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerActivityLogListeners();
        
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
    }

    /**
     * Register listeners for third-party models to record activities.
     */
    protected function registerActivityLogListeners(): void
    {
        // 1. API Token creation and deletion
        if (class_exists(PersonalAccessToken::class)) {
            PersonalAccessToken::created(function ($token) {
                try {
                    if (Auth::check()) {
                        $user = Auth::user();
                        $activity = ActivityLog::create([
                            'team_id' => $user->current_team_id,
                            'user_id' => $user->id,
                            'event' => 'api_token.created',
                            'description' => "API token '{$token->name}' was created.",
                            'metadata' => [
                                'token_id' => $token->id,
                            ],
                        ]);
                        TeamActivityRecorded::dispatch($activity);
                    }
                } catch (Throwable $e) {
                    Log::warning('Failed to log API token creation: ' . $e->getMessage());
                }
            });

           PersonalAccessToken::deleted(function ($token) {
                try {
                    if (Auth::check()) {
                        $user = Auth::user();
                        $activity = ActivityLog::create([
                            'team_id' => $user->current_team_id,
                            'user_id' => $user->id,
                            'event' => 'api_token.deleted',
                            'description' => "API token '{$token->name}' was deleted.",
                            'metadata' => [
                                'token_id' => $token->id,
                            ],
                        ]);
                        TeamActivityRecorded::dispatch($activity);
                    }
                } catch (Throwable $e) {
                    Log::warning('Failed to log API token deletion: ' . $e->getMessage());
                }
            });
        }

        // 2. Stripe Subscriptions
        if (class_exists(Subscription::class)) {
            Subscription::created(function ($subscription) {
                try {
                    $user = $subscription->user;
                    if ($user) {
                        $activity = ActivityLog::create([
                            'team_id' => $user->current_team_id,
                            'user_id' => $user->id,
                            'event' => 'subscription.created',
                            'description' => 'Subscribed to plan.',
                            'metadata' => [
                                'subscription_id' => $subscription->id,
                                'stripe_price' => $subscription->stripe_price,
                            ],
                        ]);
                        TeamActivityRecorded::dispatch($activity);
                    }
                } catch (Throwable $e) {
                   Log::warning('Failed to log subscription creation: ' . $e->getMessage());
                }
            });

            Subscription::updated(function ($subscription) {
                try {
                    $user = $subscription->user;
                    if ($user && $subscription->wasChanged('stripe_status')) {
                        $activity = ActivityLog::create([
                            'team_id' => $user->current_team_id,
                            'user_id' => $user->id,
                            'event' => 'subscription.updated',
                            'description' => "Subscription status changed to {$subscription->stripe_status}.",
                            'metadata' => [
                                'subscription_id' => $subscription->id,
                                'stripe_status' => $subscription->stripe_status,
                            ],
                        ]);
                        TeamActivityRecorded::dispatch($activity);
                    }
                } catch (Throwable $e) {
                   Log::warning('Failed to log subscription update: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
