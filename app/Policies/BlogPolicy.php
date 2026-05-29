<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;
use App\Enums\TeamPermission;

class BlogPolicy
{
    /**
     * Determine whether the user can view the blog.
     */
    public function view(User $user, Blog $blog): bool
    {
        $team = $user->currentTeam;
        return $team && $blog->team_id === $team->id;
    }

    /**
     * Determine whether the user can create blogs.
     */
    public function create(User $user): bool
    {
        $team = $user->currentTeam;

        return $team && ($user->hasTeamPermission($team, TeamPermission::CreateBlog)
            || $user->hasTeamPermission($team, TeamPermission::ManageBlogs));
    }

    /**
     * Determine whether the user can update the blog.
     */
    public function update(User $user, Blog $blog): bool
    {
        $team = $user->currentTeam;

        if (!$team || $blog->team_id !== $team->id) {
            return false;
        }

        return $user->hasTeamPermission($team, TeamPermission::UpdateBlog)
            || $user->hasTeamPermission($team, TeamPermission::ManageBlogs)
            || $blog->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the blog.
     */
    public function delete(User $user, Blog $blog): bool
    {
        $team = $user->currentTeam;

        if (!$team || $blog->team_id !== $team->id) {
            return false;
        }

        return $user->hasTeamPermission($team, TeamPermission::DeleteBlog)
            || $user->hasTeamPermission($team, TeamPermission::ManageBlogs)
            || $blog->user_id === $user->id;
    }
}
