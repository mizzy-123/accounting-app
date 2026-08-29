import { Head, Link, router } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { InvoiceStatus, InvoiceSummary } from '@/types';

type Props = {
    invoices: InvoiceSummary[];
    filters: { status: string | null };
    canCreate: boolean;
    canManageStatus: boolean;
};

const statusLabels: Record<InvoiceStatus, string> = {
    draft: 'Draft',
    sent: 'Terkirim',
    paid: 'Lunas',
};

const statusStyles: Record<InvoiceStatus, string> = {
    draft: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    sent: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
    paid: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export default function InvoicesIndex({
    invoices,
    filters,
    canCreate,
}: Props) {
    function setStatusFilter(status: string | null) {
        router.get(
            '/invoices',
            status ? { status } : {},
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Invoice" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Invoice
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Buat dan kelola invoice project bisnis.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/invoices/receivables">Lihat Piutang</Link>
                        </Button>
                        {canCreate && (
                            <Button size="sm" className="gap-2" asChild>
                                <Link href="/invoices/create">
                                    <Plus className="size-4" />
                                    Buat Invoice
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {[
                        { value: null, label: 'Semua' },
                        { value: 'draft', label: 'Draft' },
                        { value: 'sent', label: 'Terkirim' },
                        { value: 'paid', label: 'Lunas' },
                    ].map((item) => (
                        <Button
                            key={item.label}
                            size="sm"
                            variant={
                                filters.status === item.value ||
                                (!filters.status && item.value === null)
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => setStatusFilter(item.value)}
                        >
                            {item.label}
                        </Button>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <FileText className="size-4" />
                            Daftar Invoice ({invoices.length})
                        </CardTitle>
                        <CardDescription>
                            Status diubah manual oleh Owner: draft → sent → paid.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <FileText className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Belum ada invoice.
                                </p>
                                {canCreate && (
                                    <Button asChild className="mt-4" size="sm">
                                        <Link href="/invoices/create">
                                            Buat Invoice Pertama
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="divide-y">
                                {invoices.map((invoice) => (
                                    <Link
                                        key={invoice.id}
                                        href={`/invoices/${invoice.id}`}
                                        className="hover:bg-muted/40 -mx-2 flex flex-col gap-2 rounded-lg px-2 py-3 transition-colors sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-sm font-medium">
                                                    {invoice.invoice_number}
                                                </p>
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs ${statusStyles[invoice.status]}`}
                                                >
                                                    {statusLabels[invoice.status]}
                                                </span>
                                                {invoice.is_overdue && (
                                                    <span className="rounded-full bg-red-500/10 px-2 py-0.5 text-xs text-red-700 dark:text-red-400">
                                                        Jatuh tempo
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {invoice.client?.name ?? 'Tanpa client'}
                                                {invoice.project &&
                                                    ` · ${invoice.project.name}`}
                                                {invoice.due_date &&
                                                    ` · Due ${invoice.due_date}`}
                                            </p>
                                        </div>
                                        <p className="text-sm font-semibold tabular-nums">
                                            {formatCurrency(invoice.total)}
                                        </p>
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

InvoicesIndex.layout = {
    breadcrumbs: [{ title: 'Invoice', href: '/invoices' }],
};
