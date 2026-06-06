<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Team;
use App\Http\Controllers\ApiTokenController;
use Illuminate\Support\Str;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'framex:token 
                            {--user= : User ID or email} 
                            {--team= : Team ID or slug} 
                            {--name= : Token name} 
                            {--scopes= : Comma-separated token scopes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a team-scoped personal access token with scopes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Resolve User
        $userIdentifier = $this->option('user') ?: $this->ask('Enter User ID or Email');
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User '{$userIdentifier}' not found.");
            return 1;
        }

        // 2. Resolve Team
        $teamIdentifier = $this->option('team') ?: $this->ask("Enter Team ID or Slug for User '{$user->email}'");
        $team = is_numeric($teamIdentifier)
            ? Team::find($teamIdentifier)
            : Team::where('slug', $teamIdentifier)->first();

        if (!$team) {
            $this->error("Team '{$teamIdentifier}' not found.");
            return 1;
        }

        if (!$user->belongsToTeam($team)) {
            $this->error("User '{$user->name}' does not belong to team '{$team->name}'.");
            return 1;
        }

        // 3. Resolve Name
        $name = $this->option('name') ?: $this->ask('Enter token name', 'Artisan Generated Token');

        // 4. Resolve Scopes
        $availableScopes = array_keys(ApiTokenController::$scopes);
        $scopesInput = $this->option('scopes');
        
        if ($scopesInput) {
            $scopes = array_filter(explode(',', $scopesInput));
        } else {
            $scopes = [];
            if ($this->confirm('Assign all scopes to this token?', true)) {
                $scopes = $availableScopes;
            } else {
                foreach ($availableScopes as $scope) {
                    if ($this->confirm("Assign scope '{$scope}'?", false)) {
                        $scopes[] = $scope;
                    }
                }
            }
        }

        // Validate scopes
        $invalidScopes = array_diff($scopes, $availableScopes);
        if (!empty($invalidScopes)) {
            $this->error("Invalid scopes specified: " . implode(', ', $invalidScopes));
            $this->info("Available scopes: " . implode(', ', $availableScopes));
            return 1;
        }

        // 5. Generate Token
        $plainTextToken = 'framex_live_' . Str::random(40);
        $hashedToken = hash('sha256', $plainTextToken);

        $token = $user->tokens()->create([
            'name' => $name,
            'team_id' => $team->id,
            'token' => $hashedToken,
            'abilities' => $scopes,
            'expires_at' => null,
        ]);

        $this->info("API Token generated successfully!");
        $this->line("--------------------------------------------------");
        $this->info("Token Name:  {$token->name}");
        $this->info("Team:        {$team->name}");
        $this->info("User:        {$user->name} ({$user->email})");
        $this->info("Scopes:      " . implode(', ', $scopes));
        $this->line("--------------------------------------------------");
        $this->warn("Secret Key (shown ONLY once):");
        $this->line($plainTextToken);
        $this->line("--------------------------------------------------");
        $this->warn("Save this token now. It will never be shown again.");

        return 0;
    }
}
