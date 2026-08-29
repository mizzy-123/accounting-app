import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowUpRight,
    Calendar,
    CheckCircle2,
    CircleDot,
    Edit2,
    TrendingDown,
    TrendingUp,
    Wallet,
    XCircle,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Project, ProjectStatus } from '@/types';

type TransactionRow = {
    id: string;
    date: string;
    description: string | null;
    type: string;
    amount: string;
    status: string;
    category: { name: string } | null;
    creator: { name: string };
};

type Props = {
    project: Project & {
        total_revenue: string;
        total_expense: string;
        net_profit: string;
        budget_used_percent: number;
        is_over_budget: boolean;
    };
    transactions: TransactionRow[];
    canManage: boolean;
    canDelete: boolean;
};

function formatCurrency(amount: string | null | undefined): string {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

const statusConfig: Record<ProjectStatus, { label: string; color: string; icon: React.ComponentType<{ className?: string }> }> = {
    active: { label: 'Aktif', color: 'text-emerald-600 dark:text-emerald-400', icon: CircleDot },
    completed: { label: 'Selesai', color: 'text-blue-600 dark:text-blue-400', icon: CheckCircle2 },
    cancelled: { label: 'Dibatalkan', color: 'text-red-600 dark:text-red-400', icon: XCircle },
};

const typeLabels: Record<string, string> = {
    income: 'Pemasukan',
    expense: 'Pengeluaran',
    transfer: 'Transfer',
    inter_entity_transfer: 'Transfer Antar Entity',
    adjustment: 'Jurnal',
};

const typeColors: Record<string, string> = {
    income: 'text-emerald-600 dark:text-emerald-400',
    expense: 'text-red-600 dark:text-red-400',
};

export default function ProjectShow({
    project,
    transactions,
    canManage,
    canDelete,
}: Props) {
    const statusConf = statusConfig[project.status];
    const StatusIcon = statusConf.icon;
    const netProfit = Number(project.net_profit);

    return (
        <>
            <Head title={`${project.name} — Project`} />

            <div className="space-y-6">
                {/* Back + Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <Button
                            variant="ghost"
                            size="sm"
                            asChild
                            className="-ml-1 mt-0.5"
                        >
                            <Link href="/projects">
                                <ArrowLeft className="mr-1.5 size-4" />
                                Projects
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Title card */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {project.name}
                                    </h1>
                                    <span
                                        className={`flex items-center gap-1 text-sm font-medium ${statusConf.color}`}
                                    >
                                        <StatusIcon className="size-4" />
                                        {statusConf.label}
                                    </span>
                                </div>
                                <div className="text-muted-foreground mt-1 flex flex-wrap items-center gap-3 text-sm">
                                    {project.client && (
                                        <span className="flex items-center gap-1">
                                            <ArrowUpRight className="size-3.5" />
                                            {project.client.name}
                                        </span>
                                    )}
                                    {project.start_date && (
                                        <span className="flex items-center gap-1">
                                            <Calendar className="size-3.5" />
                                            {project.start_date}
                                            {project.end_date &&
                                                ` → ${project.end_date}`}
                                        </span>
                                    )}
                                </div>
                            </div>
                            {canManage && (
                                <div className="flex flex-wrap gap-2">
                                    <Button size="sm" asChild>
                                        <Link
                                            href={`/invoices/create?project_id=${project.id}`}
                                        >
                                            Buat Invoice
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/projects/${project.id}`}>
                                            <Edit2 className="mr-1.5 size-4" />
                                            Edit Project
                                        </Link>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Financial summary */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="pt-5">
                            <div className="flex items-center gap-2">
                                <TrendingUp className="size-4 text-emerald-500" />
                                <span className="text-muted-foreground text-sm">
                                    Total Pemasukan
                                </span>
                            </div>
                            <p className="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {formatCurrency(project.total_revenue)}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-5">
                            <div className="flex items-center gap-2">
                                <TrendingDown className="text-destructive size-4" />
                                <span className="text-muted-foreground text-sm">
                                    Total Pengeluaran
                                </span>
                            </div>
                            <p className="text-destructive mt-1 text-2xl font-bold">
                                {formatCurrency(project.total_expense)}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-5">
                            <div className="flex items-center gap-2">
                                <Wallet className="text-primary size-4" />
                                <span className="text-muted-foreground text-sm">
                                    Net Profit
                                </span>
                            </div>
                            <p
                                className={`mt-1 text-2xl font-bold ${netProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'}`}
                            >
                                {formatCurrency(project.net_profit)}
                            </p>
                        </CardContent>
                    </Card>

                    {project.budget ? (
                        <Card
                            className={project.is_over_budget ? 'border-destructive/50' : ''}
                        >
                            <CardContent className="pt-5">
                                <div className="flex items-center gap-2">
                                    {project.is_over_budget ? (
                                        <AlertTriangle className="text-destructive size-4" />
                                    ) : (
                                        <Wallet className="text-muted-foreground size-4" />
                                    )}
                                    <span className="text-muted-foreground text-sm">
                                        Budget Terpakai
                                    </span>
                                </div>
                                <p
                                    className={`mt-1 text-2xl font-bold ${project.is_over_budget ? 'text-destructive' : ''}`}
                                >
                                    {project.budget_used_percent.toFixed(1)}%
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    dari {formatCurrency(project.budget)}
                                </p>
                                {/* Progress bar */}
                                <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${project.is_over_budget ? 'bg-destructive' : 'bg-primary'}`}
                                        style={{
                                            width: `${Math.min(project.budget_used_percent, 100)}%`,
                                        }}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="border-dashed">
                            <CardContent className="text-muted-foreground flex items-center justify-center py-5 text-sm">
                                Belum ada budget
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Transaction list */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-base">
                                    Transaksi Terkait
                                </CardTitle>
                                <CardDescription>
                                    {transactions.length} transaksi ter-tag ke
                                    project ini
                                </CardDescription>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/transactions/create?project_id=${project.id}`}
                                >
                                    + Tambah Transaksi
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {transactions.length === 0 ? (
                            <div className="text-muted-foreground flex flex-col items-center justify-center py-12 text-center">
                                <Wallet className="text-muted-foreground/30 mb-3 size-10" />
                                <p className="text-sm">
                                    Belum ada transaksi ter-tag ke project ini.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {transactions.map((tx) => (
                                    <Link
                                        key={tx.id}
                                        href={`/transactions/${tx.id}`}
                                        className="hover:bg-muted/50 flex items-center gap-4 px-6 py-3 transition-colors"
                                    >
                                        {/* Date */}
                                        <span className="text-muted-foreground w-24 shrink-0 text-xs">
                                            {tx.date}
                                        </span>

                                        {/* Description + category */}
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm">
                                                {tx.description || '—'}
                                            </p>
                                            {tx.category && (
                                                <p className="text-muted-foreground text-xs">
                                                    {tx.category.name}
                                                </p>
                                            )}
                                        </div>

                                        {/* Type badge */}
                                        <span className="text-muted-foreground shrink-0 text-xs">
                                            {typeLabels[tx.type] ?? tx.type}
                                        </span>

                                        {/* Amount */}
                                        <span
                                            className={`w-32 shrink-0 text-right text-sm font-semibold ${typeColors[tx.type] ?? ''}`}
                                        >
                                            {tx.type === 'income' ? '+' : tx.type === 'expense' ? '-' : ''}
                                            {formatCurrency(tx.amount)}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
