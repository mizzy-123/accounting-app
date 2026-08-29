import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Download,
    FileText,
    RotateCcw,
    Send,
    Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type {
    TransactionDetail,
    TransactionStatus,
    TransactionType,
} from '@/types';

type Props = {
    transaction: TransactionDetail;
    canSubmit: boolean;
    canApprove: boolean;
    canReject: boolean;
    canDelete: boolean;
};

const typeLabels: Record<TransactionType, string> = {
    income: 'Pemasukan',
    expense: 'Pengeluaran',
    transfer: 'Transfer',
    inter_entity_transfer: 'Transfer Antar Entity',
    adjustment: 'Jurnal Penyesuaian',
};

const statusLabels: Record<TransactionStatus, string> = {
    draft: 'Draft',
    pending_approval: 'Menunggu Approval',
    approved: 'Disetujui (terkunci)',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    }).format(Number(amount));
}

export default function TransactionsShow({
    transaction,
    canSubmit,
    canApprove,
    canReject,
    canDelete,
}: Props) {
    const totalDebit = transaction.entries.reduce(
        (sum, entry) => sum + Number(entry.debit),
        0,
    );
    const totalKredit = transaction.entries.reduce(
        (sum, entry) => sum + Number(entry.kredit),
        0,
    );
    const isBalanced = totalDebit.toFixed(2) === totalKredit.toFixed(2);
    const isLocked = transaction.status === 'approved';

    return (
        <>
            <Head title={`Transaksi · ${transaction.date}`} />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Detail Transaksi
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {typeLabels[transaction.type]} · {transaction.date}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canSubmit && (
                            <Button
                                size="sm"
                                className="gap-2"
                                onClick={() =>
                                    router.post(
                                        `/transactions/${transaction.id}/submit`,
                                    )
                                }
                            >
                                <Send className="size-4" />
                                Ajukan Approval
                            </Button>
                        )}
                        {canApprove && (
                            <Button
                                size="sm"
                                className="gap-2"
                                onClick={() =>
                                    router.post(
                                        `/transactions/${transaction.id}/approve`,
                                    )
                                }
                            >
                                <CheckCircle2 className="size-4" />
                                Setujui & Kunci
                            </Button>
                        )}
                        {canReject && (
                            <Button
                                size="sm"
                                variant="outline"
                                className="gap-2"
                                onClick={() =>
                                    router.post(
                                        `/transactions/${transaction.id}/reject`,
                                    )
                                }
                            >
                                <RotateCcw className="size-4" />
                                Kembalikan Draft
                            </Button>
                        )}
                        {canDelete && (
                            <Button
                                size="sm"
                                variant="destructive"
                                className="gap-2"
                                onClick={() => {
                                    if (
                                        confirm(
                                            'Hapus transaksi draft/pending ini?',
                                        )
                                    ) {
                                        router.delete(
                                            `/transactions/${transaction.id}`,
                                        );
                                    }
                                }}
                            >
                                <Trash2 className="size-4" />
                                Hapus
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/transactions">Kembali</Link>
                        </Button>
                    </div>
                </div>

                {isLocked && (
                    <Card className="border-amber-500/40 bg-amber-500/5">
                        <CardContent className="pt-6 text-sm">
                            Transaksi ini sudah disetujui dan terkunci. Perbaikan
                            hanya lewat{' '}
                            <Link
                                href="/journals/create"
                                className="font-medium underline underline-offset-4"
                            >
                                Jurnal Penyesuaian
                            </Link>{' '}
                            baru.
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {transaction.description ||
                                typeLabels[transaction.type]}
                        </CardTitle>
                        <CardDescription>
                            Jumlah: {formatCurrency(transaction.amount)} ·
                            Status:{' '}
                            {statusLabels[transaction.status] ??
                                transaction.status}
                            {transaction.approver && (
                                <>
                                    {' '}
                                    · Disetujui oleh {transaction.approver.name}
                                </>
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {transaction.category && (
                            <p className="text-sm">
                                <span className="text-muted-foreground">
                                    Kategori:{' '}
                                </span>
                                {transaction.category.name}
                            </p>
                        )}
                        {transaction.reference && (
                            <p className="text-sm">
                                <span className="text-muted-foreground">
                                    Referensi:{' '}
                                </span>
                                {transaction.reference}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <FileText className="size-4" />
                            Jurnal (Double-Entry)
                        </CardTitle>
                        <CardDescription>
                            {isBalanced
                                ? 'Transaksi seimbang (debit = kredit).'
                                : 'Peringatan: transaksi tidak seimbang.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="pb-2 font-medium">
                                            Akun
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Debit
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Kredit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transaction.entries.map((entry) => (
                                        <tr
                                            key={entry.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-2">
                                                <p>{entry.account.name}</p>
                                                <p className="text-muted-foreground text-xs">
                                                    {entry.account.type}
                                                </p>
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {Number(entry.debit) > 0
                                                    ? formatCurrency(
                                                          entry.debit,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {Number(entry.kredit) > 0
                                                    ? formatCurrency(
                                                          entry.kredit,
                                                      )
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="font-medium">
                                        <td className="pt-3">Total</td>
                                        <td className="pt-3 text-right tabular-nums">
                                            {formatCurrency(
                                                totalDebit.toFixed(2),
                                            )}
                                        </td>
                                        <td className="pt-3 text-right tabular-nums">
                                            {formatCurrency(
                                                totalKredit.toFixed(2),
                                            )}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {transaction.attachments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Lampiran
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {transaction.attachments.map((attachment) => (
                                <a
                                    key={attachment.id}
                                    href={attachment.url}
                                    className="hover:bg-muted/50 flex items-center gap-3 rounded-lg border p-3 transition-colors"
                                >
                                    <Download className="text-muted-foreground size-4" />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">
                                            {attachment.original_name ||
                                                'Lampiran'}
                                        </p>
                                        {attachment.size && (
                                            <p className="text-muted-foreground text-xs">
                                                {Math.round(
                                                    attachment.size / 1024,
                                                )}{' '}
                                                KB
                                            </p>
                                        )}
                                    </div>
                                </a>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

TransactionsShow.layout = {
    breadcrumbs: [
        { title: 'Transaksi', href: '/transactions' },
        { title: 'Detail', href: '#' },
    ],
};
