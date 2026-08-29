import { Head, router, useForm, usePage } from '@inertiajs/react';

import {
    Crown,
    Eye,
    MoreHorizontal,
    Shield,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { EntityRole } from '@/types';

type Member = {
    id: number;
    name: string;
    email: string;
    role: EntityRole;
};

type EntityInfo = {
    id: number;
    name: string;
    type: string;
};

type Props = {
    entity: EntityInfo;
    members: Member[];
};

const roleConfig: Record<
    EntityRole,
    { label: string; icon: React.ComponentType<{ className?: string }>; color: string }
> = {
    owner: {
        label: 'Owner',
        icon: Crown,
        color: 'text-violet-600 dark:text-violet-400',
    },
    member: {
        label: 'Member',
        icon: Shield,
        color: 'text-blue-600 dark:text-blue-400',
    },
    viewer: {
        label: 'Viewer',
        icon: Eye,
        color: 'text-slate-600 dark:text-slate-400',
    },
};

function RoleIcon({ role }: { role: EntityRole }) {
    const config = roleConfig[role];
    const Icon = config.icon;
    return <Icon className={`size-4 ${config.color}`} />;
}

function RoleBadge({ role }: { role: EntityRole }) {
    const config = roleConfig[role];
    const bgColors: Record<EntityRole, string> = {
        owner: 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-400',
        member: 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-400',
        viewer: 'bg-slate-500/10 text-slate-700 ring-slate-500/20 dark:text-slate-400',
    };

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${bgColors[role]}`}
        >
            <RoleIcon role={role} />
            {config.label}
        </span>
    );
}

function InviteMemberDialog({ entity }: { entity: EntityInfo }) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: 'member' as 'member' | 'viewer',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/entity/${entity.id}/team`, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" className="gap-2">
                    <UserPlus className="size-4" />
                    Undang Anggota
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Undang Anggota Tim</DialogTitle>
                    <DialogDescription>
                        Undang anggota baru ke{' '}
                        <span className="font-medium">{entity.name}</span>.
                        User harus sudah terdaftar di sistem.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="invite-email">Email</Label>
                        <Input
                            id="invite-email"
                            type="email"
                            placeholder="email@example.com"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoFocus
                        />
                        {errors.email && (
                            <p className="text-destructive text-sm">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="invite-role">Role</Label>
                        <Select
                            value={data.role}
                            onValueChange={(v) =>
                                setData('role', v as 'member' | 'viewer')
                            }
                        >
                            <SelectTrigger id="invite-role" className="w-full">
                                <SelectValue placeholder="Pilih role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="member">
                                    <div className="flex items-center gap-2">
                                        <Shield className="size-4 text-blue-600" />
                                        <div>
                                            <p className="font-medium">Member</p>
                                            <p className="text-muted-foreground text-xs">
                                                Bisa input transaksi
                                            </p>
                                        </div>
                                    </div>
                                </SelectItem>
                                <SelectItem value="viewer">
                                    <div className="flex items-center gap-2">
                                        <Eye className="size-4 text-slate-600" />
                                        <div>
                                            <p className="font-medium">Viewer</p>
                                            <p className="text-muted-foreground text-xs">
                                                Read-only laporan
                                            </p>
                                        </div>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.role && (
                            <p className="text-destructive text-sm">
                                {errors.role}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Mengundang...' : 'Undang'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function MemberRow({
    member,
    entity,
    currentUserId,
}: {
    member: Member;
    entity: EntityInfo;
    currentUserId: number;
}) {
    const isOwner = member.role === 'owner';
    const isSelf = member.id === currentUserId;

    function updateRole(role: 'member' | 'viewer') {
        router.patch(
            `/entity/${entity.id}/team/${member.id}`,
            { role },
            { preserveScroll: true },
        );
    }

    function removeMember() {
        if (
            !confirm(
                `Hapus ${member.name} dari tim ${entity.name}?`,
            )
        )
            return;

        router.delete(`/entity/${entity.id}/team/${member.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <div className="flex items-center gap-4 py-3">
            {/* Avatar placeholder */}
            <div className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-full font-medium uppercase">
                {member.name.charAt(0)}
            </div>

            {/* Info */}
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <p className="truncate text-sm font-medium">
                        {member.name}
                    </p>
                    {isSelf && (
                        <span className="text-muted-foreground text-xs">
                            (Anda)
                        </span>
                    )}
                </div>
                <p className="text-muted-foreground truncate text-xs">
                    {member.email}
                </p>
            </div>

            {/* Role badge */}
            <RoleBadge role={member.role} />

            {/* Actions — tidak bisa edit owner atau diri sendiri */}
            {!isOwner && !isSelf && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="size-8 p-0"
                        >
                            <MoreHorizontal className="size-4" />
                            <span className="sr-only">Aksi</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onClick={() => updateRole('member')}
                            disabled={member.role === 'member'}
                        >
                            <Shield className="mr-2 size-4 text-blue-600" />
                            Jadikan Member
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => updateRole('viewer')}
                            disabled={member.role === 'viewer'}
                        >
                            <Eye className="mr-2 size-4 text-slate-600" />
                            Jadikan Viewer
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={removeMember}
                            className="text-destructive focus:text-destructive"
                        >
                            <Trash2 className="mr-2 size-4" />
                            Hapus dari Tim
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}

export default function TeamIndex({ entity, members }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={`Tim · ${entity.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Manajemen Tim
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Kelola anggota tim untuk entity{' '}
                            <span className="font-medium">{entity.name}</span>.
                        </p>
                    </div>
                    <InviteMemberDialog entity={entity} />
                </div>

                {/* Members card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Users className="size-4" />
                            Anggota Tim ({members.length})
                        </CardTitle>
                        <CardDescription>
                            Owner memiliki akses penuh. Member bisa input
                            transaksi. Viewer hanya bisa melihat laporan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {members.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-10 text-center">
                                <Users className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Belum ada anggota tim.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {members.map((member) => (
                                    <MemberRow
                                        key={member.id}
                                        member={member}
                                        entity={entity}
                                        currentUserId={auth.user.id}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Role legend */}
                <Card className="bg-muted/30">
                    <CardContent className="pt-4">
                        <div className="grid gap-3 sm:grid-cols-3">
                            {(
                                Object.entries(roleConfig) as [
                                    EntityRole,
                                    (typeof roleConfig)[EntityRole],
                                ][]
                            ).map(([role, config]) => (
                                <div key={role} className="flex gap-2">
                                    <config.icon
                                        className={`mt-0.5 size-4 shrink-0 ${config.color}`}
                                    />
                                    <div>
                                        <p className="text-sm font-medium">
                                            {config.label}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {role === 'owner' &&
                                                'Akses penuh semua fitur'}
                                            {role === 'member' &&
                                                'Input transaksi, lihat laporan'}
                                            {role === 'viewer' &&
                                                'Read-only laporan saja'}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
