import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, ClipboardCheck, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { TransactionSummary, TransactionType } from '@/types';

type Props = {
    transactions: TransactionSummary[];
};

const typeLabels: Record<TransactionType, string> = {
    income: 'Pemasukan',
    expense: 'Pengeluaran',
    transfer: 'Transfer',
    inter_entity_transfer: 'Transfer Antar Entity',
    adjustment: 'Jurnal Penyesuaian',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export default function ApprovalsIndex({ transactions }: Props) {
    return (
        <>
            <Head title="Antrian Approval" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Antrian Approval
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Transaksi menunggu persetujuan owner. Setelah disetujui,
                        transaksi terkunci.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardCheck className="size-4" />
                            Pending Approval
                        </CardTitle>
                        <CardDescription>
                            {transactions.length} transaksi menunggu.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="divide-y">
                        {transactions.length === 0 ? (
                            <p className="text-muted-foreground py-4 text-sm">
                                Tidak ada transaksi menunggu approval.
                            </p>
                        ) : (
                            transactions.map((transaction) => (
                                <div
                                    key={transaction.id}
                                    className="flex flex-wrap items-center justify-between gap-3 py-3"
                                >
                                    <div className="min-w-0">
                                        <Link
                                            href={`/transactions/${transaction.id}`}
                                            className="font-medium hover:underline"
                                        >
                                            {transaction.description ||
                                                typeLabels[transaction.type]}
                                        </Link>
                                        <p className="text-muted-foreground text-xs">
                                            {transaction.date} ·{' '}
                                            {typeLabels[transaction.type]} ·{' '}
                                            {transaction.creator?.name ?? '—'} ·{' '}
                                            {formatCurrency(transaction.amount)}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            className="gap-1.5"
                                            onClick={() =>
                                                router.post(
                                                    `/transactions/${transaction.id}/approve`,
                                                )
                                            }
                                        >
                                            <CheckCircle2 className="size-4" />
                                            Setujui
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="gap-1.5"
                                            onClick={() =>
                                                router.post(
                                                    `/transactions/${transaction.id}/reject`,
                                                )
                                            }
                                        >
                                            <RotateCcw className="size-4" />
                                            Tolak
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ApprovalsIndex.layout = {
    breadcrumbs: [{ title: 'Approval', href: '/approvals' }],
};
