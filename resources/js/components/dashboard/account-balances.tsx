import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DashboardAccount } from '@/types';

type Props = {
    accounts: DashboardAccount[];
};

const typeLabels: Record<string, string> = {
    asset: 'Aset',
    liability: 'Kewajiban',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export function AccountBalances({ accounts }: Props) {
    const grouped = accounts.reduce<Record<string, DashboardAccount[]>>(
        (groups, account) => {
            if (!groups[account.type]) {
                groups[account.type] = [];
            }
            groups[account.type].push(account);

            return groups;
        },
        {},
    );

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="text-base">Saldo Akun</CardTitle>
                <CardDescription>
                    Dihitung dari jurnal double-entry per akun
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-5 pt-0">
                {Object.entries(grouped).map(([type, typeAccounts]) => (
                    <div key={type}>
                        <p className="text-muted-foreground mb-2 text-xs font-medium uppercase tracking-wide">
                            {typeLabels[type] ?? type}
                        </p>
                        <div className="divide-y rounded-lg border">
                            {typeAccounts.map((account) => (
                                <div
                                    key={account.id}
                                    className="flex items-center justify-between gap-4 px-4 py-3"
                                >
                                    <span className="text-sm">
                                        {account.name}
                                    </span>
                                    <span
                                        className={`text-sm font-medium tabular-nums ${
                                            Number(account.balance) < 0
                                                ? 'text-red-600 dark:text-red-400'
                                                : ''
                                        }`}
                                    >
                                        {formatCurrency(account.balance)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
