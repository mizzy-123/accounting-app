import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Link } from '@inertiajs/react';

export type BudgetProgressItem = {
    budget_id: string | null;
    category_id: string;
    category_name: string;
    budget: string;
    actual: string;
    remaining: string;
    progress_percent: string;
};

export type BudgetProgress = {
    period: string;
    items: BudgetProgressItem[];
    totals: {
        budget: string;
        actual: string;
        remaining: string;
    };
};

type Props = {
    budgetProgress: BudgetProgress | null;
};

function formatCurrency(amount: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export function PersonalBudgetWidget({ budgetProgress }: Props) {
    const items = budgetProgress?.items ?? [];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Progress Budget</CardTitle>
                <CardDescription>
                    Pengeluaran vs target bulan{' '}
                    {budgetProgress?.period ?? 'ini'}.{' '}
                    <Link
                        href="/budgets"
                        className="text-foreground underline-offset-4 hover:underline"
                    >
                        Kelola budget
                    </Link>
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {items.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Belum ada budget untuk periode ini. Tetapkan target di
                        halaman Budget.
                    </p>
                ) : (
                    items.map((item) => {
                        const progress = Math.min(
                            Number(item.progress_percent),
                            100,
                        );

                        return (
                            <div
                                key={item.category_id}
                                className="space-y-1.5"
                            >
                                <div className="flex items-center justify-between text-sm">
                                    <span>{item.category_name}</span>
                                    <span className="text-muted-foreground text-xs tabular-nums">
                                        {formatCurrency(item.actual)} /{' '}
                                        {formatCurrency(item.budget)}
                                    </span>
                                </div>
                                <div className="bg-muted h-2 overflow-hidden rounded-full">
                                    <div
                                        className={`h-full rounded-full transition-all ${
                                            progress >= 90
                                                ? 'bg-red-500'
                                                : progress >= 70
                                                  ? 'bg-amber-500'
                                                  : 'bg-emerald-500'
                                        }`}
                                        style={{ width: `${progress}%` }}
                                    />
                                </div>
                            </div>
                        );
                    })
                )}
            </CardContent>
        </Card>
    );
}
