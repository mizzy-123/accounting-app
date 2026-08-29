import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, Plus, Receipt } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { TransactionSummary, TransactionType } from '@/types';

type PaginatedTransactions = {
    data: TransactionSummary[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    transactions: PaginatedTransactions;
    canCreate: boolean;
};

const typeLabels: Record<TransactionType, string> = {
    income: 'Pemasukan',
    expense: 'Pengeluaran',
    transfer: 'Transfer',
    inter_entity_transfer: 'Transfer Antar Entity',
    adjustment: 'Jurnal Penyesuaian',
};

const statusLabels = {
    draft: 'Draft',
    pending_approval: 'Menunggu Approval',
    approved: 'Disetujui',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export default function TransactionsIndex({ transactions, canCreate }: Props) {
    return (
        <>
            <Head title="Transaksi" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Transaksi
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Daftar transaksi keuangan entity aktif.
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild size="sm" className="gap-2">
                            <Link href="/transactions/create">
                                <Plus className="size-4" />
                                Catat Transaksi
                            </Link>
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Receipt className="size-4" />
                            Riwayat Transaksi
                        </CardTitle>
                        <CardDescription>
                            Transaksi dicatat otomatis dengan double-entry di
                            belakang layar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {transactions.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <ArrowLeftRight className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Belum ada transaksi. Mulai catat pemasukan
                                    atau pengeluaran pertama Anda.
                                </p>
                                {canCreate && (
                                    <Button asChild className="mt-4" size="sm">
                                        <Link href="/transactions/create">
                                            Catat Transaksi
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="divide-y">
                                {transactions.data.map((transaction) => (
                                    <Link
                                        key={transaction.id}
                                        href={`/transactions/${transaction.id}`}
                                        className="hover:bg-muted/40 -mx-2 flex items-center gap-4 rounded-lg px-2 py-3 transition-colors"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate text-sm font-medium">
                                                    {transaction.description ||
                                                        typeLabels[transaction.type]}
                                                </p>
                                                <span className="bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-xs">
                                                    {typeLabels[transaction.type]}
                                                </span>
                                            </div>
                                            <p className="text-muted-foreground text-xs">
                                                {transaction.date}
                                                {transaction.category &&
                                                    ` · ${transaction.category.name}`}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p
                                                className={`text-sm font-semibold ${
                                                    transaction.type === 'income'
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : transaction.type ===
                                                            'expense'
                                                          ? 'text-red-600 dark:text-red-400'
                                                          : ''
                                                }`}
                                            >
                                                {formatCurrency(transaction.amount)}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {statusLabels[transaction.status]}
                                            </p>
                                        </div>
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

TransactionsIndex.layout = {
    breadcrumbs: [{ title: 'Transaksi', href: '/transactions' }],
};
