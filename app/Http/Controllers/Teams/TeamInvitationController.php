<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Notifications\Teams\TeamInvitationAccepted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => TeamRole::from($request->validated('role')),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(3),
        ]);

        $existingUser = User::where('email', $invitation->email)->first();
        $sendEmail = $request->boolean('send_email');

        if ($existingUser) {
            $existingUser->notify(new TeamInvitationNotification($invitation, $sendEmail));
        } else {
            if ($sendEmail) {
                Notification::route('mail', $invitation->email)
                    ->notify(new TeamInvitationNotification($invitation, true));
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Show the invitation page.
     */
    public function show(Request $request, TeamInvitation $invitation): Response
    {
        $invitation->load(['team', 'inviter']);

        if ($request->user()) {
            $request->user()->unreadNotifications()
                ->where('type', TeamInvitationNotification::class)
                ->where('data->invitation_id', $invitation->id)
                ->get()
                ->markAsRead();
        }

        return Inertia::render('teams/show-invitation', [
            'invitation' => [
                'id' => $invitation->id,
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at->toISOString(),
                'accepted_at' => $invitation->accepted_at?->toISOString(),
                'team' => [
                    'id' => $invitation->team->id,
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
                'inviter' => [
                    'id' => $invitation->inviter->id,
                    'name' => $invitation->inviter->name,
                    'email' => $invitation->inviter->email,
                ],
            ]
        ]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->team_id === $team->id, 404);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $invitation) {
            $team = $invitation->team;

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $user->switchTeam($team);

            // Notify the inviter
            $invitation->inviter->notify(new TeamInvitationAccepted($team, $user));
        });

        // Mark matching notifications as read
        $user->unreadNotifications()
            ->where('type', TeamInvitationNotification::class)
            ->where('data->invitation_id', $invitation->id)
            ->get()
            ->markAsRead();

        return to_route('dashboard');
    }

    /**
     * Decline the invitation.
     */
    public function decline(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        // Mark matching notifications as read
        $user->unreadNotifications()
            ->where('type', TeamInvitationNotification::class)
            ->where('data->invitation_id', $invitation->id)
            ->get()
            ->markAsRead();

        // Log decline activity and delete the invitation
        $invitation->declined = true;
        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return to_route('dashboard');
    }
}
