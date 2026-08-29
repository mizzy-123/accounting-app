import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Plus, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { InvoiceSummary } from '@/types';

type Props = {
    invoices: InvoiceSummary[];
    summary: {
        count: number;
        total_outstanding: string;
        overdue_count: number;
    };
    canManageStatus: boolean;
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

function dueLabel(invoice: InvoiceSummary): string {
    if (!invoice.due_date) return 'Tanpa jatuh tempo';
    if (invoice.is_overdue) {
        const days = Math.abs(invoice.days_until_due ?? 0);
        return `Lewat ${days} hari`;
    }
    if (invoice.days_until_due === 0) return 'Jatuh tempo hari ini';
    if (invoice.days_until_due !== null && invoice.days_until_due <= 7) {
        return `${invoice.days_until_due} hari lagi`;
    }

    return `Due ${invoice.due_date}`;
}

export default function InvoicesReceivables({
    invoices,
    summary,
    canManageStatus,
}: Props) {
    function markPaid(id: string) {
        router.patch(
            `/invoices/${id}/status`,
            { status: 'paid' },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Piutang" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Piutang
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Invoice yang belum lunas, diurutkan berdasarkan jatuh
                            tempo.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/invoices">Semua Invoice</Link>
                        </Button>
                        <Button size="sm" className="gap-2" asChild>
                            <Link href="/invoices/create">
                                <Plus className="size-4" />
                                Buat Invoice
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-muted-foreground text-xs">
                                Jumlah belum lunas
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">
                                {summary.count}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-muted-foreground text-xs">
                                Total outstanding
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">
                                {formatCurrency(summary.total_outstanding)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-muted-foreground text-xs">
                                Jatuh tempo
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums text-red-600 dark:text-red-400">
                                {summary.overdue_count}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Wallet className="size-4" />
                            Daftar Piutang
                        </CardTitle>
                        <CardDescription>
                            Prioritas overdue ditampilkan lebih dulu.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Wallet className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Tidak ada piutang. Semua invoice sudah lunas
                                    atau belum dibuat.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {invoices.map((invoice) => (
                                    <div
                                        key={invoice.id}
                                        className="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <Link
                                            href={`/invoices/${invoice.id}`}
                                            className="min-w-0 flex-1"
                                        >
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-sm font-medium">
                                                    {invoice.invoice_number}
                                                </p>
                                                {invoice.is_overdue ? (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-xs text-red-700 dark:text-red-400">
                                                        <AlertTriangle className="size-3" />
                                                        {dueLabel(invoice)}
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs text-amber-700 dark:text-amber-400">
                                                        {dueLabel(invoice)}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-muted-foreground text-xs">
                                                {invoice.client?.name ??
                                                    'Tanpa client'}
                                                {invoice.project &&
                                                    ` · ${invoice.project.name}`}
                                            </p>
                                        </Link>
                                        <div className="flex items-center gap-3">
                                            <p className="text-sm font-semibold tabular-nums">
                                                {formatCurrency(invoice.total)}
                                            </p>
                                            {canManageStatus &&
                                                invoice.status === 'sent' && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            markPaid(invoice.id)
                                                        }
                                                    >
                                                        Tandai Lunas
                                                    </Button>
                                                )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

InvoicesReceivables.layout = {
    breadcrumbs: [
        { title: 'Invoice', href: '/invoices' },
        { title: 'Piutang', href: '/invoices/receivables' },
    ],
};
