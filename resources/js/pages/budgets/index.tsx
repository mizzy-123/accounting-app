import { Head, router, useForm } from '@inertiajs/react';
import { Download, PieChart, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type CategoryOption = {
    id: string;
    name: string;
    type: string;
};

type BudgetRow = {
    id: string;
    category_id: string;
    category_name: string | null;
    period: string;
    amount: string;
};

type Comparison = {
    period: string;
    items: Array<{
        budget_id: string | null;
        category_id: string;
        category_name: string;
        budget: string;
        actual: string;
        remaining: string;
        progress_percent: string;
    }>;
    totals: {
        budget: string;
        actual: string;
        remaining: string;
    };
};

type Props = {
    period: string;
    budgets: BudgetRow[];
    comparison: Comparison;
    categories: CategoryOption[];
    canManage: boolean;
};

function formatCurrency(amount: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

function CreateBudgetDialog({
    categories,
    period,
}: {
    categories: CategoryOption[];
    period: string;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        category_id: '',
        period,
        amount: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/budgets', {
            onSuccess: () => {
                reset('category_id', 'amount');
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" className="gap-2">
                    <Plus className="size-4" />
                    Tambah Budget
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Budget Kategori</DialogTitle>
                    <DialogDescription>
                        Tetapkan batas pengeluaran per kategori untuk periode
                        bulanan.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>Periode</Label>
                        <Input
                            type="month"
                            value={data.period}
                            onChange={(e) => setData('period', e.target.value)}
                            required
                        />
                        {errors.period && (
                            <p className="text-destructive text-sm">
                                {errors.period}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label>Kategori</Label>
                        <Select
                            value={data.category_id}
                            onValueChange={(v) => setData('category_id', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((cat) => (
                                    <SelectItem key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category_id && (
                            <p className="text-destructive text-sm">
                                {errors.category_id}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label>Jumlah</Label>
                        <Input
                            type="number"
                            min="0"
                            step="1000"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            required
                        />
                        {errors.amount && (
                            <p className="text-destructive text-sm">
                                {errors.amount}
                            </p>
                        )}
                    </div>
                    <Button type="submit" disabled={processing} className="w-full">
                        Simpan
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function BudgetsIndex({
    period,
    budgets,
    comparison,
    categories,
    canManage,
}: Props) {
    function changePeriod(next: string) {
        router.get('/budgets', { period: next }, { preserveState: true });
    }

    return (
        <>
            <Head title="Budget" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Budget
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Target pengeluaran per kategori vs realisasi.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            type="month"
                            value={period}
                            onChange={(e) => changePeriod(e.target.value)}
                            className="w-40"
                        />
                        {canManage && (
                            <CreateBudgetDialog
                                categories={categories}
                                period={period}
                            />
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Budget</CardDescription>
                            <CardTitle className="text-lg tabular-nums">
                                {formatCurrency(comparison.totals.budget)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Actual</CardDescription>
                            <CardTitle className="text-lg tabular-nums">
                                {formatCurrency(comparison.totals.actual)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Sisa</CardDescription>
                            <CardTitle className="text-lg tabular-nums">
                                {formatCurrency(comparison.totals.remaining)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <PieChart className="size-4" />
                            Progress per Kategori
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {comparison.items.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada budget untuk periode {period}.
                            </p>
                        ) : (
                            comparison.items.map((item) => {
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
                                                {formatCurrency(item.budget)} (
                                                {item.progress_percent}%)
                                            </span>
                                        </div>
                                        <div className="bg-muted h-2 overflow-hidden rounded-full">
                                            <div
                                                className={`h-full rounded-full ${
                                                    progress >= 90
                                                        ? 'bg-red-500'
                                                        : progress >= 70
                                                          ? 'bg-amber-500'
                                                          : 'bg-emerald-500'
                                                }`}
                                                style={{
                                                    width: `${progress}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </CardContent>
                </Card>

                {canManage && budgets.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Daftar Budget
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y">
                            {budgets.map((budget) => (
                                <div
                                    key={budget.id}
                                    className="flex items-center justify-between gap-3 py-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {budget.category_name}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {formatCurrency(budget.amount)} ·{' '}
                                            {budget.period}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-destructive"
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    'Hapus budget kategori ini?',
                                                )
                                            ) {
                                                router.delete(
                                                    `/budgets/${budget.id}`,
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

BudgetsIndex.layout = {
    breadcrumbs: [{ title: 'Budget', href: '/budgets' }],
};
