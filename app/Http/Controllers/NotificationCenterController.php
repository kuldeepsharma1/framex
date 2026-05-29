<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Fetch database notifications
        $dbNotificationsCollection = $user->notifications()
            ->latest()
            ->limit(30)
            ->get();

        $dbNotifications = $dbNotificationsCollection->toBase()->map(fn ($notification) => [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'Notification',
            'body' => $notification->data['body'] ?? '',
            'action_url' => $notification->data['action_url'] ?? null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at->diffForHumans(),
        ]);

        // 2. Fetch pending invitations for the user's email to show dynamically (if not already represented as a db notification)
        $dbNotificationInvitationIds = $dbNotificationsCollection
            ->filter(fn ($n) => $n->type === \App\Notifications\Teams\TeamInvitation::class || isset($n->data['invitation_id']))
            ->map(fn ($n) => $n->data['invitation_id'] ?? null)
            ->filter()
            ->toArray();

        $invitations = \App\Models\TeamInvitation::where('email', $user->email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->whereNotIn('id', $dbNotificationInvitationIds)
            ->with(['team', 'inviter'])
            ->get()
            ->toBase()
            ->map(fn ($invitation) => [
                'id' => 'invitation-' . $invitation->id,
                'title' => __('Workspace Invitation'),
                'body' => __(':inviterName has invited you to join the :teamName team.', [
                    'inviterName' => $invitation->inviter->name,
                    'teamName' => $invitation->team->name,
                ]),
                'action_url' => route('invitations.show', ['invitation' => $invitation->code]),
                'read_at' => null, // Pending invitations are always shown as action items
                'created_at' => $invitation->created_at->diffForHumans(),
            ]);

        // 3. Merge dynamic invitations and database notifications
        $allNotifications = $invitations->merge($dbNotifications);

        return Inertia::render('notifications/index', [
            'notifications' => $allNotifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
