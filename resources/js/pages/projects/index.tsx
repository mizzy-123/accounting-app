import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Briefcase,
    Calendar,
    CheckCircle2,
    CircleDot,
    Edit2,
    MoreHorizontal,
    Plus,
    Trash2,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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
import type { Client, Project, ProjectStatus } from '@/types';

type Props = {
    projects: Project[];
    clients: Pick<Client, 'id' | 'name'>[];
    statusFilter: string;
    canManage: boolean;
    canDelete: boolean;
};

function formatCurrency(amount: string | null | undefined): string {
    if (!amount) return '—';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

const statusConfig: Record<
    ProjectStatus,
    { label: string; icon: React.ComponentType<{ className?: string }>; badge: string }
> = {
    active: {
        label: 'Aktif',
        icon: CircleDot,
        badge: 'bg-emerald-500/15 text-emerald-700 ring-emerald-500/20 dark:text-emerald-400',
    },
    completed: {
        label: 'Selesai',
        icon: CheckCircle2,
        badge: 'bg-blue-500/15 text-blue-700 ring-blue-500/20 dark:text-blue-400',
    },
    cancelled: {
        label: 'Dibatalkan',
        icon: XCircle,
        badge: 'bg-red-500/15 text-red-700 ring-red-500/20 dark:text-red-400',
    },
};

function StatusBadge({ status }: { status: ProjectStatus }) {
    const config = statusConfig[status];
    const Icon = config.icon;
    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${config.badge}`}
        >
            <Icon className="size-3" />
            {config.label}
        </span>
    );
}

function ProjectFormDialog({
    project,
    clients,
    trigger,
}: {
    project?: Project;
    clients: Pick<Client, 'id' | 'name'>[];
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const isEdit = !!project;

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: project?.name ?? '',
        client_id: project?.client_id ?? '',
        budget: project?.budget ?? '',
        start_date: project?.start_date ?? '',
        end_date: project?.end_date ?? '',
        status: (project?.status ?? 'active') as ProjectStatus,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            patch(`/projects/${project.id}`, {
                onSuccess: () => setOpen(false),
            });
        } else {
            post('/projects', {
                onSuccess: () => {
                    reset();
                    setOpen(false);
                },
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Edit Project' : 'Buat Project Baru'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? `Ubah detail project "${project.name}".`
                            : 'Isi detail project baru. Client dan budget bersifat opsional.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Nama */}
                    <div className="space-y-2">
                        <Label htmlFor="proj-name">Nama Project</Label>
                        <Input
                            id="proj-name"
                            placeholder="Website Redesign Q3"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        {errors.name && (
                            <p className="text-destructive text-sm">{errors.name}</p>
                        )}
                    </div>

                    {/* Client */}
                    <div className="space-y-2">
                        <Label htmlFor="proj-client">
                            Client{' '}
                            <span className="text-muted-foreground text-xs">(opsional)</span>
                        </Label>
                        <Select
                            value={data.client_id || 'none'}
                            onValueChange={(v) =>
                                setData('client_id', v === 'none' ? '' : v)
                            }
                        >
                            <SelectTrigger id="proj-client" className="w-full">
                                <SelectValue placeholder="Pilih client..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    <span className="text-muted-foreground">
                                        Tanpa client
                                    </span>
                                </SelectItem>
                                {clients.map((c) => (
                                    <SelectItem key={c.id} value={c.id}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.client_id && (
                            <p className="text-destructive text-sm">{errors.client_id}</p>
                        )}
                    </div>

                    {/* Budget */}
                    <div className="space-y-2">
                        <Label htmlFor="proj-budget">
                            Budget{' '}
                            <span className="text-muted-foreground text-xs">(opsional)</span>
                        </Label>
                        <div className="relative">
                            <span className="text-muted-foreground absolute top-2.5 left-3 text-sm">
                                Rp
                            </span>
                            <Input
                                id="proj-budget"
                                type="number"
                                min="0"
                                step="1000"
                                className="pl-10"
                                placeholder="50000000"
                                value={data.budget ?? ''}
                                onChange={(e) => setData('budget', e.target.value)}
                            />
                        </div>
                        {errors.budget && (
                            <p className="text-destructive text-sm">{errors.budget}</p>
                        )}
                    </div>

                    {/* Tanggal */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="proj-start">Mulai</Label>
                            <Input
                                id="proj-start"
                                type="date"
                                value={data.start_date ?? ''}
                                onChange={(e) => setData('start_date', e.target.value)}
                            />
                            {errors.start_date && (
                                <p className="text-destructive text-sm">{errors.start_date}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="proj-end">Selesai</Label>
                            <Input
                                id="proj-end"
                                type="date"
                                value={data.end_date ?? ''}
                                onChange={(e) => setData('end_date', e.target.value)}
                            />
                            {errors.end_date && (
                                <p className="text-destructive text-sm">{errors.end_date}</p>
                            )}
                        </div>
                    </div>

                    {/* Status — hanya saat edit */}
                    {isEdit && (
                        <div className="space-y-2">
                            <Label htmlFor="proj-status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(v) =>
                                    setData('status', v as ProjectStatus)
                                }
                            >
                                <SelectTrigger id="proj-status" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Aktif</SelectItem>
                                    <SelectItem value="completed">Selesai</SelectItem>
                                    <SelectItem value="cancelled">Dibatalkan</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Menyimpan...'
                                : isEdit
                                  ? 'Simpan'
                                  : 'Buat Project'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ProjectCard({
    project,
    clients,
    canManage,
    canDelete,
}: {
    project: Project;
    clients: Pick<Client, 'id' | 'name'>[];
    canManage: boolean;
    canDelete: boolean;
}) {
    function handleDelete() {
        if (
            !confirm(
                `Hapus project "${project.name}"? Semua data terkait akan terpengaruh.`,
            )
        )
            return;
        router.delete(`/projects/${project.id}`);
    }

    const netProfit = Number(project.net_profit ?? 0);
    const budgetPct = project.budget_used_percent ?? 0;

    return (
        <Card className="group flex flex-col transition-shadow hover:shadow-md">
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <CardTitle className="truncate text-base">
                            <Link
                                href={`/projects/${project.id}`}
                                className="hover:text-primary transition-colors"
                            >
                                {project.name}
                            </Link>
                        </CardTitle>
                        {project.client && (
                            <p className="text-muted-foreground mt-0.5 truncate text-xs">
                                {project.client.name}
                            </p>
                        )}
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        <StatusBadge status={project.status} />
                        {canManage && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="size-7 p-0 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <MoreHorizontal className="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <ProjectFormDialog
                                        project={project}
                                        clients={clients}
                                        trigger={
                                            <DropdownMenuItem
                                                onSelect={(e) =>
                                                    e.preventDefault()
                                                }
                                            >
                                                <Edit2 className="mr-2 size-4" />
                                                Edit
                                            </DropdownMenuItem>
                                        }
                                    />
                                    {canDelete && (
                                        <>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={handleDelete}
                                                className="text-destructive focus:text-destructive"
                                            >
                                                <Trash2 className="mr-2 size-4" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-3 pt-0">
                {/* Financial summary */}
                <div className="grid grid-cols-3 gap-2 rounded-lg border p-3">
                    <div className="text-center">
                        <p className="text-muted-foreground text-[10px] uppercase tracking-wide">
                            Pemasukan
                        </p>
                        <p className="mt-0.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                            {formatCurrency(project.total_revenue)}
                        </p>
                    </div>
                    <div className="text-center">
                        <p className="text-muted-foreground text-[10px] uppercase tracking-wide">
                            Pengeluaran
                        </p>
                        <p className="text-destructive mt-0.5 text-sm font-semibold">
                            {formatCurrency(project.total_expense)}
                        </p>
                    </div>
                    <div className="text-center">
                        <p className="text-muted-foreground text-[10px] uppercase tracking-wide">
                            Net
                        </p>
                        <p
                            className={`mt-0.5 text-sm font-bold ${netProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'}`}
                        >
                            {formatCurrency(project.net_profit)}
                        </p>
                    </div>
                </div>

                {/* Budget progress */}
                {project.budget && (
                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between text-xs">
                            <span className="text-muted-foreground">
                                Budget: {formatCurrency(project.budget)}
                            </span>
                            <span
                                className={`font-medium ${project.is_over_budget ? 'text-destructive' : 'text-muted-foreground'}`}
                            >
                                {budgetPct.toFixed(0)}%
                                {project.is_over_budget && (
                                    <AlertTriangle className="ml-1 inline size-3" />
                                )}
                            </span>
                        </div>
                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className={`h-full rounded-full transition-all ${project.is_over_budget ? 'bg-destructive' : 'bg-primary'}`}
                                style={{ width: `${Math.min(budgetPct, 100)}%` }}
                            />
                        </div>
                    </div>
                )}

                {/* Dates + link */}
                <div className="mt-auto flex items-center justify-between text-xs">
                    {project.start_date ? (
                        <div className="text-muted-foreground flex items-center gap-1">
                            <Calendar className="size-3" />
                            {project.start_date}
                            {project.end_date && ` → ${project.end_date}`}
                        </div>
                    ) : (
                        <span />
                    )}
                    <Link
                        href={`/projects/${project.id}`}
                        className="text-primary flex items-center gap-1 hover:underline"
                    >
                        Detail <ArrowRight className="size-3" />
                    </Link>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ProjectsIndex({
    projects,
    clients,
    statusFilter,
    canManage,
    canDelete,
}: Props) {
    const filters: { value: string; label: string }[] = [
        { value: 'active', label: 'Aktif' },
        { value: 'completed', label: 'Selesai' },
        { value: 'cancelled', label: 'Dibatalkan' },
        { value: 'all', label: 'Semua' },
    ];

    return (
        <>
            <Head title="Projects" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Projects
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Kelola proyek bisnis dan pantau keuangannya.
                        </p>
                    </div>
                    {canManage && (
                        <ProjectFormDialog
                            clients={clients}
                            trigger={
                                <Button size="sm" className="gap-2">
                                    <Plus className="size-4" />
                                    Buat Project
                                </Button>
                            }
                        />
                    )}
                </div>

                {/* Status filter tabs */}
                <div className="flex gap-1 rounded-lg border p-1 w-fit">
                    {filters.map((f) => (
                        <Link
                            key={f.value}
                            href={`/projects?status=${f.value}`}
                            className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                statusFilter === f.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {f.label}
                        </Link>
                    ))}
                </div>

                {/* Content */}
                {projects.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <Briefcase className="text-muted-foreground/40 mb-4 size-12" />
                            <h3 className="font-semibold">
                                {statusFilter === 'all'
                                    ? 'Belum ada project'
                                    : `Tidak ada project ${statusConfig[statusFilter as ProjectStatus]?.label.toLowerCase() ?? statusFilter}`}
                            </h3>
                            <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                {canManage
                                    ? 'Buat project pertama untuk mulai melacak keuangan per proyek.'
                                    : 'Belum ada project di kategori ini.'}
                            </p>
                            {canManage && statusFilter === 'active' && (
                                <ProjectFormDialog
                                    clients={clients}
                                    trigger={
                                        <Button className="mt-4 gap-2" size="sm">
                                            <Plus className="size-4" />
                                            Buat Project
                                        </Button>
                                    }
                                />
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard
                                key={project.id}
                                project={project}
                                clients={clients}
                                canManage={canManage}
                                canDelete={canDelete}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
