import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { InvoiceDetail, InvoiceStatus } from '@/types';

type Props = {
    invoice: InvoiceDetail;
    canManageStatus: boolean;
    canDelete: boolean;
};

const statusLabels: Record<InvoiceStatus, string> = {
    draft: 'Draft',
    sent: 'Terkirim',
    paid: 'Lunas',
};

function formatCurrency(amount: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export default function InvoicesShow({
    invoice,
    canManageStatus,
    canDelete,
}: Props) {
    function updateStatus(status: InvoiceStatus) {
        router.patch(
            `/invoices/${invoice.id}/status`,
            { status },
            { preserveScroll: true },
        );
    }

    function destroy() {
        if (!confirm(`Hapus invoice draft ${invoice.invoice_number}?`)) return;
        router.delete(`/invoices/${invoice.id}`);
    }

    return (
        <>
            <Head title={`${invoice.invoice_number} — Invoice`} />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="sm" asChild className="-ml-1 mt-0.5">
                            <Link href="/invoices">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                {invoice.invoice_number}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {statusLabels[invoice.status]}
                                {invoice.is_overdue && ' · Jatuh tempo'}
                                {invoice.due_date && ` · Due ${invoice.due_date}`}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {canManageStatus && invoice.status === 'draft' && (
                            <Button size="sm" onClick={() => updateStatus('sent')}>
                                Tandai Terkirim
                            </Button>
                        )}
                        {canManageStatus && invoice.status === 'sent' && (
                            <>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => updateStatus('draft')}
                                >
                                    Kembali Draft
                                </Button>
                                <Button size="sm" onClick={() => updateStatus('paid')}>
                                    Tandai Lunas
                                </Button>
                            </>
                        )}
                        {canManageStatus && invoice.status === 'paid' && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => updateStatus('sent')}
                            >
                                Batalkan Lunas
                            </Button>
                        )}
                        {canDelete && (
                            <Button
                                size="sm"
                                variant="destructive"
                                className="gap-1"
                                onClick={destroy}
                            >
                                <Trash2 className="size-3.5" />
                                Hapus
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Informasi</CardTitle>
                        <CardDescription>
                            {invoice.client?.name ?? 'Tanpa client'}
                            {invoice.project && ` · Project: ${invoice.project.name}`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <p className="text-muted-foreground text-xs">Tanggal terbit</p>
                            <p>{invoice.issued_date}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground text-xs">Jatuh tempo</p>
                            <p>{invoice.due_date ?? '—'}</p>
                        </div>
                        {invoice.creator && (
                            <div>
                                <p className="text-muted-foreground text-xs">Dibuat oleh</p>
                                <p>{invoice.creator.name}</p>
                            </div>
                        )}
                        {invoice.notes && (
                            <div className="sm:col-span-2">
                                <p className="text-muted-foreground text-xs">Catatan</p>
                                <p>{invoice.notes}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Item</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="pb-2 font-medium">Item</th>
                                        <th className="pb-2 text-right font-medium">Qty</th>
                                        <th className="pb-2 text-right font-medium">Harga</th>
                                        <th className="pb-2 text-right font-medium">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoice.items.map((item, index) => (
                                        <tr key={index} className="border-b last:border-0">
                                            <td className="py-2">{item.name}</td>
                                            <td className="py-2 text-right tabular-nums">
                                                {item.qty}
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {formatCurrency(item.price)}
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {formatCurrency(item.subtotal)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 space-y-1 border-t pt-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span className="tabular-nums">
                                    {formatCurrency(invoice.subtotal)}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Diskon</span>
                                <span className="tabular-nums">
                                    {formatCurrency(invoice.discount)}
                                </span>
                            </div>
                            <div className="flex justify-between text-base font-semibold">
                                <span>Total</span>
                                <span className="tabular-nums">
                                    {formatCurrency(invoice.total)}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

InvoicesShow.layout = {
    breadcrumbs: [
        { title: 'Invoice', href: '/invoices' },
        { title: 'Detail', href: '#' },
    ],
};
