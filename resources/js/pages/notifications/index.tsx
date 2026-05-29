import { Head, Link, router } from '@inertiajs/react';
import { Bell, BellOff, Check, UserPlus } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { FadeIn } from '@/components/shared/motion';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Notification = {
    id: string;
    title: string;
    body: string;
    read_at: string | null;
    created_at: string;
    action_url?: string | null;
};

type Props = { notifications: Notification[] };

export default function NotificationsIndex({ notifications }: Props) {
    const unread = notifications.filter((n) => !n.read_at);

    return (
        <>
            <Head title="Notifications" />
            <div className="space-y-6 p-6">
                <FadeIn>
                    <PageHeader title="Notifications" description={`${unread.length} unread`}>
                        {unread.length > 0 && (
                            <Button variant="outline" size="sm" onClick={() => router.post('/notifications/read')}>
                                <Check className="mr-2 h-4 w-4" />Mark all read
                            </Button>
                        )}
                    </PageHeader>
                </FadeIn>
                <FadeIn delay={0.15}>
                    {notifications.length > 0 ? (
                        <div className="space-y-2">
                            {notifications.map((n) => {
                                const isInvitation = n.title.toLowerCase().includes('invitation');
                                
                                return (
                                    <div
                                        key={n.id}
                                        className={cn(
                                            'flex items-start gap-4 rounded-xl border p-4 transition-all duration-200 hover:shadow-xs',
                                            n.read_at
                                                ? 'bg-card border-border/50 opacity-60 dark:opacity-50'
                                                : (isInvitation
                                                    ? 'border-amber-500/30 bg-amber-500/5 dark:bg-amber-500/10'
                                                    : 'border-primary/20 bg-primary/5 dark:bg-primary/10')
                                        )}
                                    >
                                        <div className={cn(
                                            'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                            isInvitation
                                                ? (n.read_at ? 'bg-amber-100/30 dark:bg-amber-950/10 text-amber-600/60 dark:text-amber-500/50' : 'bg-amber-500/10 text-amber-500')
                                                : (n.read_at ? 'bg-muted text-muted-foreground/60' : 'bg-primary/10 text-primary')
                                        )}>
                                            {isInvitation ? (
                                                <UserPlus className="h-4 w-4" />
                                            ) : (
                                                <Bell className={cn('h-4 w-4', n.read_at ? 'text-muted-foreground/60' : 'text-primary')} />
                                            )}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className={cn('text-sm font-medium', n.read_at ? 'text-foreground/75' : 'text-foreground')}>{n.title}</h3>
                                                {!n.read_at && <span className={cn('h-1.5 w-1.5 rounded-full', isInvitation ? 'bg-amber-500' : 'bg-primary')} />}
                                            </div>
                                            {n.body && <p className="mt-0.5 text-sm text-muted-foreground">{n.body}</p>}
                                            {n.action_url && (
                                                <div className="mt-3">
                                                    <Button size="sm" variant={isInvitation ? 'default' : 'outline'} asChild>
                                                        <Link href={n.action_url}>
                                                            {isInvitation ? 'Accept Invitation' : 'View'}
                                                        </Link>
                                                    </Button>
                                                </div>
                                            )}
                                            <p className="mt-1 text-xs text-muted-foreground/70">{n.created_at}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <EmptyState icon={BellOff} title="All caught up" description="No notifications yet." />
                    )}
                </FadeIn>
            </div>
        </>
    );
}

