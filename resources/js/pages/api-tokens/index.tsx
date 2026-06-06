import { Head, router, useForm } from '@inertiajs/react';
import {
     Check, Copy, Key, Plus, Trash2, RefreshCw,
    Download, FileCode, Shield, Terminal, ArrowUpRight, Activity,
    Users, Globe, Calendar,  ShieldAlert, Cpu
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { FadeIn } from '@/components/shared/motion';
import { PageHeader } from '@/components/shared/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useClipboard } from '@/hooks/use-clipboard';

type Token = {
    id: number;
    name: string;
    team: {
        id: number;
        name: string;
    } | null;
    abilities: string[];
    last_ip_address: string | null;
    last_user_agent: string | null;
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
    is_expired: boolean;
};

type Team = {
    id: number;
    name: string;
};

type UsageStats = {
    totalRequestsToday: number;
    topEndpoints: Array<{
        endpoint: string;
        method: string;
        count: number;
    }>;
    recentLogs: Array<{
        id: number;
        token_name: string;
        endpoint: string;
        method: string;
        response_code: number;
        ip_address: string | null;
        created_at: string;
    }>;
};

type Props = {
    tokens: Token[];
    teams: Team[];
    availableScopes: Record<string, string>;
    plainTextToken?: string | null;
    usageStats: UsageStats;
};

export default function ApiTokensIndex({ tokens, teams, availableScopes, plainTextToken, usageStats }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [rotateId, setRotateId] = useState<number | null>(null);
    const [copiedText, copyToClipboard] = useClipboard();
    const [activeTab, setActiveTab] = useState<'tokens' | 'logs' | 'docs'>('tokens');
    const [selectedDocLang, setSelectedDocLang] = useState<'curl' | 'js' | 'python'>('curl');

    // Token creation form
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        team_id: teams[0]?.id ? String(teams[0].id) : '',
        abilities: [] as string[],
        expires_at: '30', // default 30 days
    });

    const handleCreateToken = (e: React.FormEvent) => {
        e.preventDefault();
        post('/api-tokens', {
            onSuccess: () => {
                setCreateOpen(false);
                reset('name', 'abilities');
            }
        });
    };

    const handleToggleScope = (scope: string) => {
        if (data.abilities.includes(scope)) {
            setData('abilities', data.abilities.filter(s => s !== scope));
        } else {
            setData('abilities', [...data.abilities, scope]);
        }
    };

    const handleSelectAllScopes = () => {
        setData('abilities', Object.keys(availableScopes));
    };

    const handleClearScopes = () => {
        setData('abilities', []);
    };

    // Documentation examples code generators
    const getDocCode = () => {
        const tokenPlaceholder = plainTextToken || 'framex_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
        const baseUrl = 'https://framex.hytek.org.in/api/v1';

        switch (selectedDocLang) {
            case 'curl':
                return `curl -X GET "${baseUrl}/blogs" \\
  -H "Authorization: Bearer ${tokenPlaceholder}" \\
  -H "Accept: application/json"`;
            case 'js':
                return `fetch("${baseUrl}/blogs", {
  method: "GET",
  headers: {
    "Authorization": "Bearer ${tokenPlaceholder}",
    "Accept": "application/json"
  }
})
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(err => console.error(err));`;
            case 'python':
                return `import requests

url = "${baseUrl}/blogs"
headers = {
    "Authorization": "Bearer ${tokenPlaceholder}",
    "Accept": "application/json"
}

response = requests.get(url, headers=headers)
print(response.json())`;
        }
    };

    return (
        <>
            <Head title="API Tokens" />

            <div className="space-y-6 p-6">
                <FadeIn>
                    <PageHeader
                        title="API Tokens & Developer Platform"
                        description="Issue enterprise keys and monitor automated integration traffic."
                    >
                        <Dialog open={createOpen} onOpenChange={(open) => {
                            setCreateOpen(open);

                            if (!open) {
                                reset();
                            }
                        }}>
                            <DialogTrigger asChild>
                                <Button size="sm" className="cursor-pointer">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Generate Token
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                                <DialogHeader>
                                    <DialogTitle>Generate Developer Token</DialogTitle>
                                    <DialogDescription>
                                        Configure team-scoped credentials with granular access control.
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={handleCreateToken} className="space-y-5 py-2">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {/* Name */}
                                        <div className="space-y-1.5">
                                            <Label htmlFor="token-name" className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Token Name</Label>
                                            <Input
                                                id="token-name"
                                                value={data.name}
                                                onChange={e => setData('name', e.target.value)}
                                                placeholder="e.g. CI/CD CMS Auto-Publish"
                                                className="h-9"
                                                required
                                            />
                                            {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                        </div>

                                        {/* Team Selection */}
                                        <div className="space-y-1.5">
                                            <Label htmlFor="token-team" className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Team Scope</Label>
                                            <select
                                                id="token-team"
                                                value={data.team_id}
                                                onChange={e => setData('team_id', e.target.value)}
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {teams.map(t => (
                                                    <option key={t.id} value={t.id} className="text-foreground bg-background">{t.name}</option>
                                                ))}
                                            </select>
                                            {errors.team_id && <p className="text-xs text-destructive">{errors.team_id}</p>}
                                        </div>
                                    </div>

                                    {/* Expiration */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="token-expiry" className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Token Expiration</Label>
                                        <select
                                            id="token-expiry"
                                            value={data.expires_at}
                                            onChange={e => setData('expires_at', e.target.value)}
                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <option value="7" className="text-foreground bg-background">7 Days</option>
                                            <option value="30" className="text-foreground bg-background">30 Days</option>
                                            <option value="90" className="text-foreground bg-background">90 Days</option>
                                            <option value="365" className="text-foreground bg-background">1 Year</option>
                                            <option value="" className="text-foreground bg-background">No Expiration</option>
                                        </select>
                                        {errors.expires_at && <p className="text-xs text-destructive">{errors.expires_at}</p>}
                                    </div>

                                    {/* Scopes */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Granular Scopes / Permissions</Label>
                                            <div className="flex gap-2">
                                                <Button type="button" variant="ghost" size="xs" onClick={handleSelectAllScopes} className="text-[10px] h-6 px-1.5 cursor-pointer">Select All</Button>
                                                <Button type="button" variant="ghost" size="xs" onClick={handleClearScopes} className="text-[10px] h-6 px-1.5 cursor-pointer">Clear</Button>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-56 overflow-y-auto border rounded-xl p-3 bg-muted/20">
                                            {Object.entries(availableScopes).map(([key, desc]) => (
                                                <div
                                                    key={key}
                                                    onClick={() => handleToggleScope(key)}
                                                    className={`flex items-start gap-2.5 p-2 rounded-lg border transition-all cursor-pointer select-none ${data.abilities.includes(key)
                                                            ? 'border-primary/40 bg-primary/5 dark:bg-primary/10'
                                                            : 'border-border bg-card/60 hover:bg-muted/30'
                                                        }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={data.abilities.includes(key)}
                                                        onChange={() => { }} // handled by div onClick
                                                        className="mt-0.5 pointer-events-none rounded border-input text-primary shadow-xs focus:ring-primary"
                                                    />
                                                    <div className="min-w-0">
                                                        <p className="text-xs font-bold font-mono text-foreground">{key}</p>
                                                        <p className="text-[10px] text-muted-foreground line-clamp-1">{desc}</p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        {errors.abilities && <p className="text-xs text-destructive">{errors.abilities}</p>}
                                    </div>

                                    <DialogFooter className="mt-6">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setCreateOpen(false)}
                                            className="cursor-pointer"
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={processing} className="cursor-pointer">
                                            Generate
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </PageHeader>
                </FadeIn>

                {/* Plain text token banner shown ONLY ONCE */}
                {plainTextToken && (
                    <FadeIn>
                        <div className="flex items-start gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5 shadow-sm">
                            <ShieldAlert className="h-6 w-6 shrink-0 text-amber-500 mt-0.5 animate-pulse" />
                            <div className="flex-1 space-y-2">
                                <h3 className="text-sm font-semibold text-foreground">
                                    Securely Store Your API Token
                                </h3>
                                <p className="text-xs text-muted-foreground leading-relaxed max-w-2xl">
                                    This token will <span className="font-bold text-amber-500">never be shown again</span>. Please copy and paste it into your configuration manager immediately. If you lose this key, you will have to rotate it.
                                </p>
                                <div className="flex items-center gap-2 max-w-xl mt-3">
                                    <code className="flex-1 rounded-xl bg-muted px-4 py-2.5 font-mono text-xs font-bold text-foreground border tracking-wider">
                                        {plainTextToken}
                                    </code>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        onClick={() => copyToClipboard(plainTextToken)}
                                        className="h-10 w-10 shrink-0 cursor-pointer"
                                    >
                                        {copiedText ? (
                                            <Check className="h-4 w-4 text-emerald-500" />
                                        ) : (
                                            <Copy className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </FadeIn>
                )}

                {/* Stats dashboard strip (Phase 6) */}
                <FadeIn delay={0.1}>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="border bg-card rounded-2xl p-5 flex items-center gap-4 shadow-sm">
                            <div className="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                                <Activity className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Requests Today</p>
                                <p className="text-xl font-bold tracking-tight mt-0.5 tabular-nums">{usageStats.totalRequestsToday}</p>
                            </div>
                        </div>
                        <div className="border bg-card rounded-2xl p-5 flex items-center gap-4 shadow-sm">
                            <div className="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                                <Key className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Active Keys</p>
                                <p className="text-xl font-bold tracking-tight mt-0.5 tabular-nums">{tokens.filter(t => !t.is_expired).length}</p>
                            </div>
                        </div>
                        <div className="border bg-card rounded-2xl p-5 flex items-center gap-4 shadow-sm">
                            <div className="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 shrink-0">
                                <Shield className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Expired Keys</p>
                                <p className="text-xl font-bold tracking-tight mt-0.5 tabular-nums">{tokens.filter(t => t.is_expired).length}</p>
                            </div>
                        </div>
                        <div className="border bg-card rounded-2xl p-5 flex items-center gap-4 shadow-sm">
                            <div className="h-10 w-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 shrink-0">
                                <Globe className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Top Endpoint Method</p>
                                <p className="text-sm font-bold truncate mt-0.5 uppercase">
                                    {usageStats.topEndpoints[0] ? `${usageStats.topEndpoints[0].method} ${usageStats.topEndpoints[0].endpoint}` : '—'}
                                </p>
                            </div>
                        </div>
                    </div>
                </FadeIn>

                {/* Main Tabs Navigation */}
                <FadeIn delay={0.15}>
                    <div className="flex border-b gap-4">
                        <button
                            onClick={() => setActiveTab('tokens')}
                            className={`pb-3 text-sm font-semibold tracking-tight transition-colors border-b-2 cursor-pointer ${activeTab === 'tokens' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                        >
                            Active Tokens ({tokens.length})
                        </button>
                        <button
                            onClick={() => setActiveTab('logs')}
                            className={`pb-3 text-sm font-semibold tracking-tight transition-colors border-b-2 cursor-pointer ${activeTab === 'logs' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                        >
                            API Request Logs
                        </button>
                        <button
                            onClick={() => setActiveTab('docs')}
                            className={`pb-3 text-sm font-semibold tracking-tight transition-colors border-b-2 cursor-pointer ${activeTab === 'docs' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                        >
                            Developer Documentation
                        </button>
                    </div>
                </FadeIn>

                {/* Tab content 1: Tokens List */}
                {activeTab === 'tokens' && (
                    <FadeIn delay={0.2}>
                        {tokens.length > 0 ? (
                            <div className="rounded-2xl border bg-card overflow-hidden shadow-sm">
                                <Table>
                                    <TableHeader className="bg-muted/30">
                                        <TableRow>
                                            <TableHead className="py-3">Token Name</TableHead>
                                            <TableHead className="py-3">Team Scope</TableHead>
                                            <TableHead className="py-3">Permissions</TableHead>
                                            <TableHead className="py-3">Last Used (IP / Client)</TableHead>
                                            <TableHead className="py-3">Created / Expires</TableHead>
                                            <TableHead className="w-24 text-right py-3" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {tokens.map((token) => (
                                            <TableRow key={token.id} className="hover:bg-muted/10">
                                                <TableCell className="font-semibold align-top py-4">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="text-sm">{token.name}</span>
                                                        <code className="text-[10px] text-muted-foreground font-mono">ID: {token.id}</code>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="align-top py-4">
                                                    {token.team ? (
                                                        <Badge variant="outline" className="font-semibold text-xs py-0.5 px-2 bg-muted/40">
                                                            {token.team.name}
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">—</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="align-top py-4">
                                                    <div className="flex flex-wrap gap-1 max-w-sm">
                                                        {token.abilities.map(ability => (
                                                            <Badge key={ability} variant="secondary" className="font-mono text-[10px] px-1.5 py-0 font-normal">
                                                                {ability}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="align-top py-4">
                                                    {token.last_used_at ? (
                                                        <div className="flex flex-col gap-0.5 text-xs text-muted-foreground">
                                                            <span className="font-medium text-foreground">{token.last_used_at}</span>
                                                            <code className="text-[10px] font-mono">{token.last_ip_address}</code>
                                                            <span className="text-[10px] text-muted-foreground/80 truncate max-w-44" title={token.last_user_agent || ''}>
                                                                {token.last_user_agent}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground italic">Never used</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="align-top py-4">
                                                    <div className="flex flex-col gap-1 text-xs text-muted-foreground">
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="h-3 w-3 shrink-0" />
                                                            <span>{token.created_at}</span>
                                                        </div>
                                                        <div className="flex items-center gap-1">
                                                            <ClockIcon className="h-3 w-3 shrink-0" />
                                                            {token.is_expired ? (
                                                                <span className="text-destructive font-semibold">Expired</span>
                                                            ) : token.expires_at ? (
                                                                <span>Expires {new Date(token.expires_at).toLocaleDateString()}</span>
                                                            ) : (
                                                                <span className="text-muted-foreground/60">No expiry</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right align-top py-4 pr-4">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {/* Rotate button */}
                                                        <Dialog
                                                            open={rotateId === token.id}
                                                            onOpenChange={(open) => setRotateId(open ? token.id : null)}
                                                        >
                                                            <DialogTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8 text-muted-foreground hover:text-foreground cursor-pointer"
                                                                    title="Rotate Token"
                                                                >
                                                                    <RefreshCw className="h-4 w-4" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent>
                                                                <DialogHeader>
                                                                    <DialogTitle>Rotate API Token</DialogTitle>
                                                                    <DialogDescription>
                                                                        Rotating <span className="font-semibold text-foreground">"{token.name}"</span> will revoke the existing secret key and issue a new one. Applications using the old key will fail.
                                                                    </DialogDescription>
                                                                </DialogHeader>
                                                                <DialogFooter>
                                                                    <Button
                                                                        variant="ghost"
                                                                        onClick={() => setRotateId(null)}
                                                                        className="cursor-pointer"
                                                                    >
                                                                        Cancel
                                                                    </Button>
                                                                    <Button
                                                                        onClick={() => {
                                                                            router.post(`/api-tokens/${token.id}/rotate`);
                                                                            setRotateId(null);
                                                                        }}
                                                                        className="bg-primary cursor-pointer"
                                                                    >
                                                                        Rotate Key
                                                                    </Button>
                                                                </DialogFooter>
                                                            </DialogContent>
                                                        </Dialog>

                                                        {/* Revoke (Delete) button */}
                                                        <Dialog
                                                            open={deleteId === token.id}
                                                            onOpenChange={(open) => setDeleteId(open ? token.id : null)}
                                                        >
                                                            <DialogTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8 text-muted-foreground hover:text-destructive cursor-pointer"
                                                                    title="Revoke Token"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent>
                                                                <DialogHeader>
                                                                    <DialogTitle>Revoke API Token</DialogTitle>
                                                                    <DialogDescription>
                                                                        Are you sure you want to permanently revoke <span className="font-semibold text-foreground">"{token.name}"</span>? Any integration using this token will stop working immediately.
                                                                    </DialogDescription>
                                                                </DialogHeader>
                                                                <DialogFooter>
                                                                    <Button
                                                                        variant="ghost"
                                                                        onClick={() => setDeleteId(null)}
                                                                        className="cursor-pointer"
                                                                    >
                                                                        Cancel
                                                                    </Button>
                                                                    <Button
                                                                        variant="destructive"
                                                                        onClick={() => {
                                                                            router.delete(`/api-tokens/${token.id}`);
                                                                            setDeleteId(null);
                                                                        }}
                                                                        className="cursor-pointer"
                                                                    >
                                                                        Revoke Token
                                                                    </Button>
                                                                </DialogFooter>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <EmptyState
                                icon={Key}
                                title="No active API tokens"
                                description="Securely automate your workflow by issuing your first API token."
                                action={{
                                    label: 'Generate Token',
                                    onClick: () => setCreateOpen(true),
                                }}
                            />
                        )}
                    </FadeIn>
                )}

                {/* Tab content 2: API Logs dashboard */}
                {activeTab === 'logs' && (
                    <FadeIn delay={0.2}>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Programmatic Traffic Log</h3>
                                <span className="text-xs text-muted-foreground">Showing recent 10 requests</span>
                            </div>
                            <div className="rounded-2xl border bg-card overflow-hidden shadow-sm">
                                <Table>
                                    <TableHeader className="bg-muted/30">
                                        <TableRow>
                                            <TableHead className="py-3">Timestamp</TableHead>
                                            <TableHead className="py-3">Token Used</TableHead>
                                            <TableHead className="py-3">Method</TableHead>
                                            <TableHead className="py-3">Endpoint</TableHead>
                                            <TableHead className="py-3">Status Code</TableHead>
                                            <TableHead className="py-3">Client IP</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {usageStats.recentLogs.length > 0 ? (
                                            usageStats.recentLogs.map((log) => (
                                                <TableRow key={log.id} className="hover:bg-muted/10 font-mono text-xs">
                                                    <TableCell className="text-muted-foreground py-3">{log.created_at}</TableCell>
                                                    <TableCell className="font-semibold text-foreground py-3">{log.token_name}</TableCell>
                                                    <TableCell className="py-3">
                                                        <span className={`inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold ${log.method === 'GET' ? 'bg-blue-500/10 text-blue-500' :
                                                                log.method === 'POST' ? 'bg-emerald-500/10 text-emerald-500' :
                                                                    log.method === 'PUT' || log.method === 'PATCH' ? 'bg-amber-500/10 text-amber-500' :
                                                                        'bg-rose-500/10 text-rose-500'
                                                            }`}>
                                                            {log.method}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-foreground py-3">/api/v1/{log.endpoint}</TableCell>
                                                    <TableCell className="py-3">
                                                        <span className={`inline-flex items-center gap-1 font-bold ${log.response_code >= 200 && log.response_code < 300 ? 'text-emerald-500' :
                                                                log.response_code >= 400 ? 'text-rose-500' : 'text-amber-500'
                                                            }`}>
                                                            {log.response_code}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground py-3">{log.ip_address || '—'}</TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} className="h-32 text-center text-muted-foreground italic">
                                                    No API traffic recorded today.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </FadeIn>
                )}

                {/* Tab content 3: Documentation page */}
                {activeTab === 'docs' && (
                    <FadeIn delay={0.2}>
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Docs description column */}
                            <div className="lg:col-span-1 space-y-6">
                                <div className="border bg-card rounded-2xl p-5 shadow-sm space-y-3">
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                        <Terminal className="h-4 w-4 text-primary" />
                                        Getting Started
                                    </h3>
                                    <p className="text-xs text-muted-foreground leading-relaxed">
                                        All endpoints require standard Bearer token authentication in the request headers:
                                    </p>
                                    <code className="block rounded-lg bg-muted p-3 font-mono text-[10px] text-foreground border">
                                        Authorization: Bearer framex_live_...
                                    </code>
                                    <p className="text-xs text-muted-foreground leading-relaxed">
                                        All routes are prefixed under <code className="font-mono bg-muted px-1.5 py-0.5 rounded">/api/v1/*</code>.
                                    </p>
                                </div>

                                <div className="border bg-card rounded-2xl p-5 shadow-sm space-y-4">
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                        <Download className="h-4 w-4 text-primary" />
                                        Collection Downloads
                                    </h3>
                                    <div className="flex flex-col gap-2">
                                        <Button variant="outline" size="sm" asChild className="w-full justify-start cursor-pointer">
                                            <a href="/docs/openapi.json" download="openapi.json">
                                                <FileCode className="mr-2 h-4 w-4 text-blue-500" />
                                                Download OpenAPI Spec
                                                <ArrowUpRight className="ml-auto h-3 w-3 opacity-60" />
                                            </a>
                                        </Button>
                                        <Button variant="outline" size="sm" asChild className="w-full justify-start cursor-pointer">
                                            <a href="/docs/postman.json" download="postman.json">
                                                <Users className="mr-2 h-4 w-4 text-amber-500" />
                                                Download Postman Collection
                                                <ArrowUpRight className="ml-auto h-3 w-3 opacity-60" />
                                            </a>
                                        </Button>
                                        <Button variant="outline" size="sm" asChild className="w-full justify-start cursor-pointer">
                                            <a href="/docs/bruno.json" download="bruno.json">
                                                <Cpu className="mr-2 h-4 w-4 text-emerald-500" />
                                                Download Bruno Collection
                                                <ArrowUpRight className="ml-auto h-3 w-3 opacity-60" />
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Code examples column */}
                            <div className="lg:col-span-2 space-y-4">
                                <div className="flex items-center justify-between border-b pb-2">
                                    <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Request Code Examples</span>
                                    <div className="flex border rounded-lg overflow-hidden h-7">
                                        <button
                                            onClick={() => setSelectedDocLang('curl')}
                                            className={`px-3 text-[11px] font-semibold transition-colors cursor-pointer ${selectedDocLang === 'curl' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-muted'}`}
                                        >
                                            cURL
                                        </button>
                                        <button
                                            onClick={() => setSelectedDocLang('js')}
                                            className={`px-3 text-[11px] font-semibold transition-colors border-l cursor-pointer ${selectedDocLang === 'js' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-muted'}`}
                                        >
                                            JavaScript
                                        </button>
                                        <button
                                            onClick={() => setSelectedDocLang('python')}
                                            className={`px-3 text-[11px] font-semibold transition-colors border-l cursor-pointer ${selectedDocLang === 'python' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-muted'}`}
                                        >
                                            Python
                                        </button>
                                    </div>
                                </div>

                                <div className="rounded-2xl border bg-slate-900 dark:bg-slate-950 p-5 font-mono text-xs text-slate-100 overflow-x-auto shadow-inner relative group select-all">
                                    <pre className="whitespace-pre">{getDocCode()}</pre>
                                </div>
                            </div>
                        </div>
                    </FadeIn>
                )}
            </div>
        </>
    );
}

// ClockIcon helper
function ClockIcon(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
        </svg>
    );
}
