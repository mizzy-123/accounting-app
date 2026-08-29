import type { CashFlowPoint } from '@/types';

type Props = {
    data: CashFlowPoint[];
    period: 'weekly' | 'monthly';
    onPeriodChange: (period: 'weekly' | 'monthly') => void;
};

function formatShort(amount: string): string {
    const value = Number(amount);
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(1)}jt`;
    }
    if (value >= 1_000) {
        return `${(value / 1_000).toFixed(0)}rb`;
    }

    return value.toFixed(0);
}

function formatAxisLabel(label: string, period: 'weekly' | 'monthly'): string {
    if (period === 'monthly') {
        // "Mar 2026" → "Mar"
        return label.split(' ')[0] ?? label;
    }

    return label;
}

export function CashFlowChart({ data, period, onPeriodChange }: Props) {
    const maxValue = Math.max(
        ...data.flatMap((point) => [
            Number(point.income),
            Number(point.expense),
        ]),
        1,
    );

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <h3 className="text-sm font-medium">
                        Pemasukan vs Pengeluaran
                    </h3>
                    <p className="text-muted-foreground text-xs">
                        Data real dari transaksi entity aktif
                    </p>
                </div>
                <div className="bg-muted flex shrink-0 rounded-lg p-0.5">
                    <button
                        type="button"
                        onClick={() => onPeriodChange('weekly')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                            period === 'weekly'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Mingguan
                    </button>
                    <button
                        type="button"
                        onClick={() => onPeriodChange('monthly')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                            period === 'monthly'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Bulanan
                    </button>
                </div>
            </div>

            {data.length === 0 ? (
                <div className="text-muted-foreground flex h-52 items-center justify-center text-sm">
                    Belum ada data transaksi untuk periode ini.
                </div>
            ) : (
                <>
                    <div className="-mx-1 overflow-x-auto pb-1">
                        <div className="flex min-w-[32rem] items-end gap-3 px-1 sm:min-w-0 sm:gap-4">
                            {data.map((point) => {
                                const incomeHeight =
                                    (Number(point.income) / maxValue) * 100;
                                const expenseHeight =
                                    (Number(point.expense) / maxValue) * 100;
                                const axisLabel = formatAxisLabel(
                                    point.label,
                                    period,
                                );

                                return (
                                    <div
                                        key={point.label}
                                        className="flex min-w-[3.25rem] flex-1 flex-col items-center gap-2 sm:min-w-0"
                                    >
                                        <div className="flex h-44 w-full items-end justify-center gap-1.5">
                                            <div
                                                className="bg-emerald-500/80 dark:bg-emerald-500 w-3 rounded-t-sm transition-all sm:w-4"
                                                style={{
                                                    height: `${Math.max(incomeHeight, point.income !== '0.00' ? 4 : 0)}%`,
                                                }}
                                                title={`Pemasukan: ${formatShort(point.income)}`}
                                            />
                                            <div
                                                className="bg-red-500/80 dark:bg-red-500 w-3 rounded-t-sm transition-all sm:w-4"
                                                style={{
                                                    height: `${Math.max(expenseHeight, point.expense !== '0.00' ? 4 : 0)}%`,
                                                }}
                                                title={`Pengeluaran: ${formatShort(point.expense)}`}
                                            />
                                        </div>
                                        <span
                                            className="text-muted-foreground w-full text-center text-[11px] leading-tight"
                                            title={point.label}
                                        >
                                            {axisLabel}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs">
                        <span className="flex items-center gap-1.5">
                            <span className="bg-emerald-500 size-2.5 rounded-sm" />
                            Pemasukan
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="bg-red-500 size-2.5 rounded-sm" />
                            Pengeluaran
                        </span>
                    </div>
                </>
            )}
        </div>
    );
}
