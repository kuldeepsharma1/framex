<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case CreateBlog = 'blog:create';
    case UpdateBlog = 'blog:update';
    case DeleteBlog = 'blog:delete';
    case ManageBlogs = 'blog:manage';
}
