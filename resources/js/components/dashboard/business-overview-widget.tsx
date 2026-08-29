import { Link } from '@inertiajs/react';
import { Briefcase, FileText } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BusinessOverview } from '@/types';

type Props = {
    overview: BusinessOverview;
};

function formatCurrency(amount: string | null): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount ?? 0));
}

export function BusinessOverviewWidget({ overview }: Props) {
    return (
        <div className="grid gap-4 md:grid-cols-2 md:gap-5">
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Briefcase className="size-4" />
                        Project Aktif
                    </CardTitle>
                    <CardDescription>Data real dari modul project</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {overview.active_projects.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Belum ada project aktif.{' '}
                            <Link href="/projects" className="underline">
                                Kelola project
                            </Link>
                        </p>
                    ) : (
                        overview.active_projects.map((project) => (
                            <Link
                                key={project.id}
                                href={`/projects/${project.id}`}
                                className="hover:bg-muted/40 flex items-center justify-between rounded-lg border px-3 py-2 transition-colors"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {project.name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {project.client_name ?? 'Tanpa client'}
                                    </p>
                                </div>
                                <span className="shrink-0 text-sm tabular-nums">
                                    {formatCurrency(project.budget)}
                                </span>
                            </Link>
                        ))
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <FileText className="size-4" />
                        Status Piutang
                    </CardTitle>
                    <CardDescription>
                        Invoice belum lunas ·{' '}
                        <Link href="/invoices/receivables" className="underline">
                            lihat semua
                        </Link>
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {overview.receivables.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Tidak ada piutang outstanding.
                        </p>
                    ) : (
                        overview.receivables.map((item) => (
                            <Link
                                key={item.id}
                                href={`/invoices/${item.id}`}
                                className="hover:bg-muted/40 block rounded-lg border px-3 py-2 transition-colors"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <p className="truncate text-sm font-medium">
                                        {item.client_name ?? item.invoice_number}
                                    </p>
                                    <span
                                        className={`shrink-0 rounded-full px-2 py-0.5 text-xs ${
                                            item.is_overdue
                                                ? 'bg-red-500/10 text-red-700 dark:text-red-400'
                                                : 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                                        }`}
                                    >
                                        {item.is_overdue
                                            ? 'Jatuh tempo'
                                            : 'Belum lunas'}
                                    </span>
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    {item.invoice_number}
                                    {item.due_date && ` · Due ${item.due_date}`}
                                </p>
                                <p className="mt-1 text-sm font-medium tabular-nums">
                                    {formatCurrency(item.total)}
                                </p>
                            </Link>
                        ))
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
