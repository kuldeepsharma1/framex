<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\ActivityLog;
use App\Enums\TeamRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamApiController extends ApiController
{
    /**
     * Display the authenticated team details.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeScope('teams:read');

        $team = $request->user()->currentTeam;

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_personal' => $team->is_personal,
            'created_at' => $team->created_at,
        ]);
    }

    /**
     * Update the team details.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorizeScope('teams:update');

        $team = $request->user()->currentTeam;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($team, $validated) {
            $teamObj = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $teamObj->update(['name' => $validated['name']]);
        });

        return response()->json([
            'message' => 'Team updated successfully.',
            'team' => $team->fresh(),
        ]);
    }

    /**
     * Display listing of team members and invitations.
     */
    public function members(Request $request): JsonResponse
    {
        $this->authorizeScope('members:read');

        $team = $request->user()->currentTeam;

        $members = $team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role->label(),
        ]);

        $invitations = $team->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'created_at' => $invitation->created_at,
            ]);

        return response()->json([
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Invite a user to the team.
     */
    public function invite(Request $request): JsonResponse
    {
        $this->authorizeScope('members:invite');

        $team = $request->user()->currentTeam;

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        // Verify if user is already a member
        $existingMember = $team->members()->where('email', $validated['email'])->exists();
        if ($existingMember) {
            return response()->json([
                'message' => 'User is already a member of this team.'
            ], 422);
        }

        // Verify duplicate pending invitation
        $existingInvitation = $team->invitations()
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->exists();
        if ($existingInvitation) {
            return response()->json([
                'message' => 'A pending invitation for this email already exists.'
            ], 422);
        }

        $invitation = $team->invitations()->create([
            'email' => $validated['email'],
            'role' => TeamRole::from($validated['role']),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(3),
        ]);

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'invitation' => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at,
            ]
        ], 201);
    }

    /**
     * Display team activity logs.
     */
    public function activity(Request $request): JsonResponse
    {
        $this->authorizeScope('activity:read');

        $team = $request->user()->currentTeam;

        $logs = ActivityLog::where('team_id', $team->id)
            ->with('user')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at,
            ]);

        return response()->json($logs);
    }
}
