import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Team, TeamMember } from '@/types';

type ManageablePermission = {
    value: string;
    label: string;
    description: string;
};

type PermissionGroup = {
    name: string;
    description: string;
    actions: { key: string; label: string }[];
};

// Organized module groupings for a professional matrix layout
const permissionGroups: PermissionGroup[] = [
    {
        name: 'Blog Management',
        description: 'Determine access levels for creating and editing blog posts.',
        actions: [
            { key: 'blog:manage', label: 'All Actions' },
            { key: 'blog:create', label: 'Create' },
            { key: 'blog:update', label: 'Update' },
            { key: 'blog:delete', label: 'Delete' },
        ],
    },
    {
        name: 'Team Settings',
        description: 'Grant privileges to modify team name and view profile details.',
        actions: [
            { key: 'team:update', label: 'Update Settings' },
        ],
    },
    {
        name: 'Invitations & Invites',
        description: 'Authorize members to send or cancel workspace invitations.',
        actions: [
            { key: 'invitation:create', label: 'Invite Members' },
            { key: 'invitation:cancel', label: 'Cancel Invitations' },
        ],
    },
    {
        name: 'Direct Member Add',
        description: 'Allow bypass of invites to directly add users to the workspace.',
        actions: [
            { key: 'member:add', label: 'Add Directly' },
        ],
    },
];

type Props = {
    team: Team;
    member: TeamMember | null;
    manageablePermissions: ManageablePermission[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function MemberPermissionsModal({
    team,
    member,
    open,
    onOpenChange,
}: Props) {
    const [processing, setProcessing] = useState(false);

    // Initialize state directly from member's current permissions, preventing ESLint useEffect warnings
    const [permissions, setPermissions] = useState<Record<string, boolean>>(() => {
        const initial: Record<string, boolean> = {};
        permissionGroups.forEach((group) => {
            group.actions.forEach((action) => {
                initial[action.key] = member?.permissions?.[action.key] ?? false;
            });
        });
        
        return initial;
    });

    const handleToggle = (key: string, value: boolean) => {
        setPermissions((prev) => ({
            ...prev,
            [key]: value,
        }));
    };

    const savePermissions = () => {
        if (!member) {
            return;
        }

        router.put(
            `/settings/teams/${team.slug}/members/${member.id}/permissions`,
            { permissions },
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => onOpenChange(false),
                preserveScroll: true,
            }
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-xl md:max-w-2xl">
                <DialogHeader className="space-y-1">
                    <DialogTitle className="text-xl font-bold">Workspace Permissions</DialogTitle>
                    <DialogDescription className="text-sm">
                        Manage granular module access for <strong className="text-foreground font-semibold">{member?.name}</strong> ({member?.email}).
                    </DialogDescription>
                </DialogHeader>

                <div className="my-6 space-y-6 max-h-[55vh] overflow-y-auto pr-1">
                    {permissionGroups.map((group) => (
                        <div
                            key={group.name}
                            className="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b border-border/50 pb-6 last:border-0 last:pb-0"
                        >
                            <div className="flex-1 space-y-1 md:pr-4">
                                <h4 className="text-sm font-semibold text-foreground">{group.name}</h4>
                                <p className="text-xs text-muted-foreground leading-relaxed">{group.description}</p>
                            </div>
                            <div className="flex flex-wrap gap-2.5 items-center justify-start md:justify-end shrink-0 md:max-w-85">
                                {group.actions.map((action) => {
                                    const isChecked = permissions[action.key] ?? false;

                                    return (
                                        <label
                                            key={action.key}
                                            className={`flex items-center gap-2 cursor-pointer border rounded-xl px-3 py-2 transition-all text-xs font-semibold select-none shadow-2xs hover:shadow-xs active:scale-[0.98] ${
                                                isChecked
                                                    ? 'bg-primary/5 border-primary/40 text-primary dark:bg-primary/10'
                                                    : 'bg-card border-border/80 text-muted-foreground hover:bg-muted/40 hover:text-foreground'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                className="h-3.5 w-3.5 rounded border-border text-primary focus:ring-primary/20 accent-primary cursor-pointer"
                                                checked={isChecked}
                                                onChange={(e) => handleToggle(action.key, e.target.checked)}
                                                disabled={processing}
                                            />
                                            {action.label}
                                        </label>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>

                <DialogFooter className="gap-2 border-t pt-4">
                    <DialogClose asChild>
                        <Button variant="secondary" disabled={processing}>Cancel</Button>
                    </DialogClose>

                    <Button
                        disabled={processing}
                        onClick={savePermissions}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 font-semibold shadow-md active:scale-[0.98]"
                    >
                        {processing ? 'Saving...' : 'Save Permissions'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
