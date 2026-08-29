import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowUpRight,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { AccountBalances } from '@/components/dashboard/account-balances';
import { BusinessOverviewWidget } from '@/components/dashboard/business-overview-widget';
import { CashFlowChart } from '@/components/dashboard/cash-flow-chart';
import { InvoiceReminderWidget } from '@/components/dashboard/invoice-reminder-widget';
import { PersonalBudgetWidget } from '@/components/dashboard/personal-budget-widget';
import type { BudgetProgress } from '@/components/dashboard/personal-budget-widget';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type {
    BusinessOverview,
    CategoryExpense,
    DashboardAccount,
    DashboardChart,
    DashboardTotals,
    EntityType,
    InvoiceReminder,
    RecentTransaction,
    TransactionType,
} from '@/types';

type Props = {
    entityType: EntityType;
    accounts: DashboardAccount[];
    totals: DashboardTotals;
    chart: DashboardChart;
    categoryExpenses: CategoryExpense[];
    recentTransactions: RecentTransaction[];
    businessOverview: BusinessOverview | null;
    invoiceReminders: InvoiceReminder[];
    budgetProgress: BudgetProgress | null;
};

const typeLabels: Record<TransactionType, string> = {
    income: 'Pemasukan',
    expense: 'Pengeluaran',
    transfer: 'Transfer',
    inter_entity_transfer: 'Transfer Entity',
    adjustment: 'Penyesuaian',
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

function SummaryCard({
    title,
    amount,
    icon: Icon,
    tone,
}: {
    title: string;
    amount: string;
    icon: React.ComponentType<{ className?: string }>;
    tone?: 'positive' | 'negative' | 'neutral';
}) {
    const toneClass =
        tone === 'positive'
            ? 'text-emerald-600 dark:text-emerald-400'
            : tone === 'negative'
              ? 'text-red-600 dark:text-red-400'
              : '';

    return (
        <Card>
            <CardContent className="flex items-start gap-4 p-5 md:p-6">
                <div className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-lg">
                    <Icon className="text-muted-foreground size-5" />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-muted-foreground text-xs leading-snug">
                        {title}
                    </p>
                    <p
                        className={`mt-1 text-base font-semibold break-words tabular-nums sm:text-lg ${toneClass}`}
                    >
                        {formatCurrency(amount)}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    entityType,
    accounts,
    totals,
    chart,
    categoryExpenses,
    recentTransactions,
    businessOverview,
    invoiceReminders,
    budgetProgress,
}: Props) {
    const { activeEntity } = usePage().props;
    const [chartPeriod, setChartPeriod] = useState<'weekly' | 'monthly'>(
        'monthly',
    );

    const netTone =
        Number(totals.net_this_month) >= 0 ? 'positive' : 'negative';

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 md:space-y-8">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Ringkasan keuangan{' '}
                        <span className="font-medium text-foreground">
                            {activeEntity?.name}
                        </span>
                        {entityType === 'personal'
                            ? ' · Mode Personal'
                            : ' · Mode Bisnis'}
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4">
                    <SummaryCard
                        title="Total Aset"
                        amount={totals.total_assets}
                        icon={Wallet}
                    />
                    <SummaryCard
                        title="Pemasukan Bulan Ini"
                        amount={totals.income_this_month}
                        icon={ArrowUpRight}
                        tone="positive"
                    />
                    <SummaryCard
                        title="Pengeluaran Bulan Ini"
                        amount={totals.expense_this_month}
                        icon={ArrowDownRight}
                        tone="negative"
                    />
                    <SummaryCard
                        title="Net Bulan Ini"
                        amount={totals.net_this_month}
                        icon={TrendingUp}
                        tone={netTone}
                    />
                </div>

                <div className="grid gap-5 xl:grid-cols-5 xl:gap-6">
                    <Card className="xl:col-span-3">
                        <CardContent className="p-5 md:p-6">
                            <CashFlowChart
                                data={
                                    chartPeriod === 'weekly'
                                        ? chart.weekly
                                        : chart.monthly
                                }
                                period={chartPeriod}
                                onPeriodChange={setChartPeriod}
                            />
                        </CardContent>
                    </Card>

                    <Card className="xl:col-span-2">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">
                                Transaksi Terbaru
                            </CardTitle>
                            <CardDescription>
                                5 transaksi terakhir entity ini
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-0">
                            {recentTransactions.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Belum ada transaksi.{' '}
                                    <Link
                                        href="/transactions/create"
                                        className="text-foreground underline"
                                    >
                                        Catat transaksi
                                    </Link>
                                </p>
                            ) : (
                                <div className="divide-y">
                                    {recentTransactions.map((transaction) => (
                                        <Link
                                            key={transaction.id}
                                            href={`/transactions/${transaction.id}`}
                                            className="hover:bg-muted/40 -mx-2 flex items-center justify-between rounded-lg px-2 py-2.5 transition-colors"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {transaction.description ||
                                                        typeLabels[
                                                            transaction.type as TransactionType
                                                        ]}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {transaction.date}
                                                </p>
                                            </div>
                                            <span
                                                className={`shrink-0 text-sm font-medium tabular-nums ${
                                                    transaction.type ===
                                                    'income'
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : transaction.type ===
                                                            'expense'
                                                          ? 'text-red-600 dark:text-red-400'
                                                          : ''
                                                }`}
                                            >
                                                {formatCurrency(
                                                    transaction.amount,
                                                )}
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-5 lg:grid-cols-2 lg:gap-6">
                    <AccountBalances accounts={accounts} />

                    {entityType === 'personal' ? (
                        <PersonalBudgetWidget
                            budgetProgress={budgetProgress}
                        />
                    ) : (
                        <Card className="lg:col-span-1">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Pengeluaran per Kategori
                                </CardTitle>
                                <CardDescription>
                                    Bulan berjalan (data real)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {categoryExpenses.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        Belum ada pengeluaran bulan ini.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {categoryExpenses.map((item) => (
                                            <div
                                                key={item.name}
                                                className="flex items-center justify-between text-sm"
                                            >
                                                <span>{item.name}</span>
                                                <span className="font-medium tabular-nums">
                                                    {formatCurrency(item.amount)}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {entityType === 'business' &&
                    invoiceReminders.length > 0 && (
                        <InvoiceReminderWidget reminders={invoiceReminders} />
                    )}

                {entityType === 'business' && businessOverview && (
                    <BusinessOverviewWidget overview={businessOverview} />
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
