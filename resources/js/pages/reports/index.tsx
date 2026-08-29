import { Head, router } from '@inertiajs/react';
import { FileSpreadsheet, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EntityType } from '@/types';

type ReportType =
    | 'cash_flow'
    | 'profit_loss'
    | 'balance_sheet'
    | 'project_profitability'
    | 'budget';

type Filters = {
    start: string;
    end: string;
    as_of: string;
    period: string;
};

type Props = {
    entityType: EntityType;
    type: ReportType;
    allowedTypes: ReportType[];
    filters: Filters;
    report: Record<string, unknown>;
};

const typeLabels: Record<ReportType, string> = {
    cash_flow: 'Cash Flow',
    profit_loss: 'Laba Rugi',
    balance_sheet: 'Neraca',
    project_profitability: 'Profitabilitas Project',
    budget: 'Budget vs Actual',
};

function formatCurrency(amount: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

function MoneyCell({ value }: { value: string | number }) {
    return (
        <span className="tabular-nums">{formatCurrency(value)}</span>
    );
}

function CashFlowReport({ report }: { report: Record<string, unknown> }) {
    const monthly = (report.monthly as Array<Record<string, string>>) ?? [];
    const categories =
        (report.categories as Array<Record<string, string>>) ?? [];
    const totals = report.totals as Record<string, string>;

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Pemasukan</CardDescription>
                        <CardTitle className="text-lg">
                            <MoneyCell value={totals.income} />
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Pengeluaran</CardDescription>
                        <CardTitle className="text-lg">
                            <MoneyCell value={totals.expense} />
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardDescription>Net</CardDescription>
                        <CardTitle className="text-lg">
                            <MoneyCell value={totals.net} />
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Per Bulan</CardTitle>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="pb-2 font-medium">Bulan</th>
                                <th className="pb-2 text-right font-medium">
                                    Pemasukan
                                </th>
                                <th className="pb-2 text-right font-medium">
                                    Pengeluaran
                                </th>
                                <th className="pb-2 text-right font-medium">
                                    Net
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {monthly.map((row) => (
                                <tr key={row.period}>
                                    <td className="py-2">{row.label}</td>
                                    <td className="py-2 text-right">
                                        <MoneyCell value={row.income} />
                                    </td>
                                    <td className="py-2 text-right">
                                        <MoneyCell value={row.expense} />
                                    </td>
                                    <td className="py-2 text-right">
                                        <MoneyCell value={row.net} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Breakdown Kategori
                    </CardTitle>
                </CardHeader>
                <CardContent className="divide-y">
                    {categories.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Tidak ada data kategori.
                        </p>
                    ) : (
                        categories.map((cat) => (
                            <div
                                key={`${cat.name}-${cat.type}`}
                                className="flex items-center justify-between py-2 text-sm"
                            >
                                <span>
                                    {cat.name}{' '}
                                    <span className="text-muted-foreground">
                                        ({cat.type})
                                    </span>
                                </span>
                                <MoneyCell value={cat.amount} />
                            </div>
                        ))
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

function ProfitLossReport({ report }: { report: Record<string, unknown> }) {
    const revenue = (report.revenue as Array<Record<string, string>>) ?? [];
    const expenses = (report.expenses as Array<Record<string, string>>) ?? [];
    const totals = report.totals as Record<string, string>;

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Pendapatan</CardTitle>
                </CardHeader>
                <CardContent className="divide-y">
                    {revenue.map((row) => (
                        <div
                            key={row.id}
                            className="flex justify-between py-2 text-sm"
                        >
                            <span>{row.name}</span>
                            <MoneyCell value={row.amount} />
                        </div>
                    ))}
                    <div className="flex justify-between py-2 text-sm font-semibold">
                        <span>Total Pendapatan</span>
                        <MoneyCell value={totals.revenue} />
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Beban</CardTitle>
                </CardHeader>
                <CardContent className="divide-y">
                    {expenses.map((row) => (
                        <div
                            key={row.id}
                            className="flex justify-between py-2 text-sm"
                        >
                            <span>{row.name}</span>
                            <MoneyCell value={row.amount} />
                        </div>
                    ))}
                    <div className="flex justify-between py-2 text-sm font-semibold">
                        <span>Total Beban</span>
                        <MoneyCell value={totals.expenses} />
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Laba Bersih</CardTitle>
                    <CardDescription>
                        <span
                            className={
                                Number(totals.net_income) >= 0
                                    ? 'text-emerald-600'
                                    : 'text-red-600'
                            }
                        >
                            <MoneyCell value={totals.net_income} />
                        </span>
                    </CardDescription>
                </CardHeader>
            </Card>
        </div>
    );
}

function BalanceSheetReport({ report }: { report: Record<string, unknown> }) {
    const assets = (report.assets as Array<Record<string, string>>) ?? [];
    const liabilities =
        (report.liabilities as Array<Record<string, string>>) ?? [];
    const equity = (report.equity as Array<Record<string, string>>) ?? [];
    const totals = report.totals as Record<string, string | boolean>;

    return (
        <div className="space-y-6">
            <p className="text-muted-foreground text-sm">
                Per tanggal: {String(report.as_of)}
            </p>
            {(
                [
                    ['Aset', assets, totals.assets],
                    ['Kewajiban', liabilities, totals.liabilities],
                    ['Ekuitas', equity, totals.equity],
                ] as const
            ).map(([title, rows, total]) => (
                <Card key={title}>
                    <CardHeader>
                        <CardTitle className="text-base">{title}</CardTitle>
                    </CardHeader>
                    <CardContent className="divide-y">
                        {rows.length === 0 ? (
                            <p className="text-muted-foreground text-sm">—</p>
                        ) : (
                            rows.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex justify-between py-2 text-sm"
                                >
                                    <span>{row.name}</span>
                                    <MoneyCell value={row.amount} />
                                </div>
                            ))
                        )}
                        <div className="flex justify-between py-2 text-sm font-semibold">
                            <span>Total {title}</span>
                            <MoneyCell value={String(total)} />
                        </div>
                    </CardContent>
                </Card>
            ))}
            <Card>
                <CardContent className="pt-6">
                    <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span>
                            Kewajiban + Ekuitas:{' '}
                            <MoneyCell
                                value={String(totals.liabilities_and_equity)}
                            />
                        </span>
                        <span
                            className={
                                totals.is_balanced
                                    ? 'font-medium text-emerald-600'
                                    : 'font-medium text-red-600'
                            }
                        >
                            {totals.is_balanced
                                ? 'Neraca seimbang'
                                : `Tidak seimbang (selisih ${totals.difference})`}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function ProjectProfitReport({ report }: { report: Record<string, unknown> }) {
    const projects =
        (report.projects as Array<Record<string, string | null>>) ?? [];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    Profitabilitas Project
                </CardTitle>
            </CardHeader>
            <CardContent className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="pb-2 font-medium">Project</th>
                            <th className="pb-2 font-medium">Client</th>
                            <th className="pb-2 text-right font-medium">
                                Revenue
                            </th>
                            <th className="pb-2 text-right font-medium">
                                Cost
                            </th>
                            <th className="pb-2 text-right font-medium">
                                Profit
                            </th>
                            <th className="pb-2 text-right font-medium">
                                Margin
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {projects.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="text-muted-foreground py-4"
                                >
                                    Belum ada project.
                                </td>
                            </tr>
                        ) : (
                            projects.map((row) => (
                                <tr key={String(row.id)}>
                                    <td className="py-2">{row.name}</td>
                                    <td className="py-2">
                                        {row.client_name ?? '—'}
                                    </td>
                                    <td className="py-2 text-right">
                                        <MoneyCell
                                            value={String(row.revenue)}
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        <MoneyCell value={String(row.cost)} />
                                    </td>
                                    <td className="py-2 text-right">
                                        <MoneyCell
                                            value={String(row.profit)}
                                        />
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {row.margin_percent
                                            ? `${row.margin_percent}%`
                                            : '—'}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </CardContent>
        </Card>
    );
}

function BudgetReport({ report }: { report: Record<string, unknown> }) {
    const items =
        (report.items as Array<Record<string, string | null>>) ?? [];
    const totals = report.totals as Record<string, string>;

    return (
        <div className="space-y-4">
            <p className="text-muted-foreground text-sm">
                Periode: {String(report.period)}
            </p>
            <Card>
                <CardContent className="divide-y pt-6">
                    {items.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Belum ada budget.
                        </p>
                    ) : (
                        items.map((item) => (
                            <div
                                key={String(item.category_id)}
                                className="flex justify-between gap-4 py-2 text-sm"
                            >
                                <span>{item.category_name}</span>
                                <span className="text-muted-foreground tabular-nums">
                                    <MoneyCell value={String(item.actual)} /> /{' '}
                                    <MoneyCell value={String(item.budget)} /> (
                                    {item.progress_percent}%)
                                </span>
                            </div>
                        ))
                    )}
                    <div className="flex justify-between py-2 text-sm font-semibold">
                        <span>Total</span>
                        <span className="tabular-nums">
                            <MoneyCell value={totals.actual} /> /{' '}
                            <MoneyCell value={totals.budget} />
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

export default function ReportsIndex({
    type,
    allowedTypes,
    filters,
    report,
}: Props) {
    function applyFilters(next: Partial<Filters> & { type?: ReportType }) {
        router.get(
            '/reports',
            {
                type: next.type ?? type,
                start: next.start ?? filters.start,
                end: next.end ?? filters.end,
                as_of: next.as_of ?? filters.as_of,
                period: next.period ?? filters.period,
            },
            { preserveState: true },
        );
    }

    function exportUrl(format: 'pdf' | 'excel'): string {
        const params = new URLSearchParams({
            type,
            format,
            start: filters.start,
            end: filters.end,
            as_of: filters.as_of,
            period: filters.period,
        });

        return `/reports/export?${params.toString()}`;
    }

    return (
        <>
            <Head title="Laporan" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Laporan
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Laporan keuangan entity aktif, siap diekspor PDF /
                            Excel.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={exportUrl('pdf')} className="gap-2">
                                <FileText className="size-4" />
                                PDF
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={exportUrl('excel')} className="gap-2">
                                <FileSpreadsheet className="size-4" />
                                Excel
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {allowedTypes.map((t) => (
                        <Button
                            key={t}
                            size="sm"
                            variant={type === t ? 'default' : 'outline'}
                            onClick={() => applyFilters({ type: t })}
                        >
                            {typeLabels[t]}
                        </Button>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filter</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-4">
                        {(type === 'cash_flow' ||
                            type === 'profit_loss' ||
                            type === 'project_profitability') && (
                            <>
                                <div className="space-y-2">
                                    <Label>Dari</Label>
                                    <Input
                                        type="date"
                                        value={filters.start}
                                        onChange={(e) =>
                                            applyFilters({
                                                start: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Sampai</Label>
                                    <Input
                                        type="date"
                                        value={filters.end}
                                        onChange={(e) =>
                                            applyFilters({
                                                end: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                            </>
                        )}
                        {type === 'balance_sheet' && (
                            <div className="space-y-2">
                                <Label>Per tanggal</Label>
                                <Input
                                    type="date"
                                    value={filters.as_of}
                                    onChange={(e) =>
                                        applyFilters({ as_of: e.target.value })
                                    }
                                />
                            </div>
                        )}
                        {type === 'budget' && (
                            <div className="space-y-2">
                                <Label>Periode</Label>
                                <Input
                                    type="month"
                                    value={filters.period}
                                    onChange={(e) =>
                                        applyFilters({
                                            period: e.target.value,
                                        })
                                    }
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>

                {type === 'cash_flow' && <CashFlowReport report={report} />}
                {type === 'profit_loss' && (
                    <ProfitLossReport report={report} />
                )}
                {type === 'balance_sheet' && (
                    <BalanceSheetReport report={report} />
                )}
                {type === 'project_profitability' && (
                    <ProjectProfitReport report={report} />
                )}
                {type === 'budget' && <BudgetReport report={report} />}
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Laporan', href: '/reports' }],
};
