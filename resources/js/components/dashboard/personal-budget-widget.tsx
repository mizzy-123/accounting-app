import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { CategoryExpense } from '@/types';

type Props = {
    categoryExpenses: CategoryExpense[];
};

/** Target budget statis — akan diganti data `budgets` table di Sprint 7 */
const STATIC_BUDGETS: Record<string, number> = {
    Transport: 1_500_000,
    Makan: 2_000_000,
    Utilities: 800_000,
    Operasional: 3_000_000,
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
}

export function PersonalBudgetWidget({ categoryExpenses }: Props) {
    const items = Object.entries(STATIC_BUDGETS).map(([name, limit]) => {
        const spent = Number(
            categoryExpenses.find((item) => item.name === name)?.amount ?? 0,
        );
        const progress = limit > 0 ? Math.min((spent / limit) * 100, 100) : 0;

        return { name, limit, spent, progress };
    });

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Progress Budget</CardTitle>
                <CardDescription>
                    Pengeluaran real bulan ini vs target statis (Sprint 7:
                    budget dinamis)
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {items.map((item) => (
                    <div key={item.name} className="space-y-1.5">
                        <div className="flex items-center justify-between text-sm">
                            <span>{item.name}</span>
                            <span className="text-muted-foreground text-xs tabular-nums">
                                {formatCurrency(item.spent)} /{' '}
                                {formatCurrency(item.limit)}
                            </span>
                        </div>
                        <div className="bg-muted h-2 overflow-hidden rounded-full">
                            <div
                                className={`h-full rounded-full transition-all ${
                                    item.progress >= 90
                                        ? 'bg-red-500'
                                        : item.progress >= 70
                                          ? 'bg-amber-500'
                                          : 'bg-emerald-500'
                                }`}
                                style={{ width: `${item.progress}%` }}
                            />
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
