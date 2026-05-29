<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['team_id', 'user_id', 'role', 'permissions'])]
class Membership extends Pivot
{
     use LogsActivity;

         public function logActivityEvent(string $action): string
    {
        return match ($action) {
            'created' => 'member.joined',
            'updated' => 'member.updated',
            'deleted' => 'member.removed',
            default => "member.{$action}",
        };
    }

    public function logActivityDescription(string $action): string
    {
        $this->loadMissing('user');
        $userName = $this->user ? $this->user->name : 'A user';
        $roleName = $this->role?->value ?? 'member';

        return match ($action) {
            'created' => "{$userName} joined the team.",
            'updated' => "{$userName}'s role was updated to {$roleName}.",
            'deleted' => "{$userName} was removed from the team.",
            default => "Member action {$action} occurred.",
        };
    } 

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the team that the membership belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'permissions' => 'array',
        ];
    }
}
