import { Head, Link, router } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type PreviewRow = {
    row: number;
    date: string;
    description: string;
    amount: string;
    type: 'income' | 'expense';
    category_id: string | null;
    matched_keyword: string | null;
    include: boolean;
    error: string | null;
};

type CategoryOption = {
    id: string;
    name: string;
    type: 'income' | 'expense';
};

type Props = {
    account: { id: string; name: string };
    fileName: string;
    rows: PreviewRow[];
    categories: CategoryOption[];
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export default function ImportPreview({
    account,
    fileName,
    rows: initialRows,
    categories,
}: Props) {
    const [rows, setRows] = useState(
        initialRows.map((row) => ({
            ...row,
            include: row.error === null && Boolean(row.category_id),
            category_id: row.category_id ?? '',
        })),
    );
    const [processing, setProcessing] = useState(false);
    const [formError, setFormError] = useState<string | null>(null);

    const includedCount = rows.filter((r) => r.include && !r.error).length;
    const unmatchedCount = rows.filter(
        (r) => !r.error && !r.category_id,
    ).length;

    const categoriesByType = useMemo(
        () => ({
            income: categories.filter((c) => c.type === 'income'),
            expense: categories.filter((c) => c.type === 'expense'),
        }),
        [categories],
    );

    function updateRow(
        index: number,
        patch: Partial<(typeof rows)[number]>,
    ) {
        setRows((current) =>
            current.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );
    }

    function toggleAll(include: boolean) {
        setRows((current) =>
            current.map((row) =>
                row.error
                    ? row
                    : {
                          ...row,
                          include: include && Boolean(row.category_id),
                      },
            ),
        );
    }

    function confirm() {
        setFormError(null);
        setProcessing(true);

        router.post(
            '/import/confirm',
            {
                account_id: account.id,
                file_name: fileName,
                rows: rows.map((row) => ({
                    date: row.date,
                    description: row.description,
                    amount: row.amount,
                    type: row.type,
                    category_id: row.category_id || null,
                    include:
                        row.include && !row.error && Boolean(row.category_id),
                })),
            },
            {
                onError: (errors) => {
                    const first = Object.values(errors)[0];
                    setFormError(
                        typeof first === 'string'
                            ? first
                            : 'Gagal menyimpan import.',
                    );
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <>
            <Head title="Preview Import" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Preview Import
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {fileName} · Akun {account.name} · {includedCount}{' '}
                            siap diimpor
                            {unmatchedCount > 0 &&
                                ` · ${unmatchedCount} belum punya kategori`}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/import">Batal</Link>
                        </Button>
                        <Button
                            size="sm"
                            onClick={confirm}
                            disabled={processing || includedCount === 0}
                        >
                            {processing
                                ? 'Menyimpan...'
                                : `Simpan ${includedCount} Transaksi`}
                        </Button>
                    </div>
                </div>

                {formError && (
                    <p className="text-destructive text-sm">{formError}</p>
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-3 space-y-0">
                        <div>
                            <CardTitle className="text-base">
                                Baris CSV ({rows.length})
                            </CardTitle>
                            <CardDescription>
                                Koreksi kategori sebelum konfirmasi. Baris error
                                otomatis dilewati.
                            </CardDescription>
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => toggleAll(true)}
                            >
                                Pilih semua
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => toggleAll(false)}
                            >
                                Kosongkan
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[48rem] text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="w-10 pb-2 font-medium">✓</th>
                                    <th className="pb-2 font-medium">Tgl</th>
                                    <th className="pb-2 font-medium">
                                        Deskripsi
                                    </th>
                                    <th className="pb-2 text-right font-medium">
                                        Jumlah
                                    </th>
                                    <th className="pb-2 font-medium">Tipe</th>
                                    <th className="pb-2 font-medium">
                                        Kategori
                                    </th>
                                    <th className="pb-2 font-medium">Match</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row, index) => (
                                    <tr
                                        key={row.row}
                                        className={`border-b last:border-0 ${
                                            row.error
                                                ? 'bg-red-500/5'
                                                : !row.category_id
                                                  ? 'bg-amber-500/5'
                                                  : ''
                                        }`}
                                    >
                                        <td className="py-2">
                                            <input
                                                type="checkbox"
                                                checked={row.include}
                                                disabled={
                                                    Boolean(row.error) ||
                                                    !row.category_id
                                                }
                                                onChange={(e) =>
                                                    updateRow(index, {
                                                        include:
                                                            e.target.checked,
                                                    })
                                                }
                                                className="size-4"
                                            />
                                        </td>
                                        <td className="py-2 whitespace-nowrap">
                                            {row.date || '—'}
                                        </td>
                                        <td className="max-w-[18rem] py-2">
                                            <p className="truncate">
                                                {row.description}
                                            </p>
                                            {row.error && (
                                                <p className="text-destructive text-xs">
                                                    {row.error}
                                                </p>
                                            )}
                                        </td>
                                        <td
                                            className={`py-2 text-right tabular-nums ${
                                                row.type === 'income'
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-red-600 dark:text-red-400'
                                            }`}
                                        >
                                            {row.amount
                                                ? formatCurrency(row.amount)
                                                : '—'}
                                        </td>
                                        <td className="py-2 capitalize">
                                            {row.type === 'income'
                                                ? 'Masuk'
                                                : 'Keluar'}
                                        </td>
                                        <td className="py-2">
                                            {!row.error && (
                                                <Select
                                                    value={row.category_id}
                                                    onValueChange={(v) =>
                                                        updateRow(index, {
                                                            category_id: v,
                                                            include: true,
                                                            matched_keyword:
                                                                null,
                                                        })
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 w-[10rem]">
                                                        <SelectValue placeholder="Pilih" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {categoriesByType[
                                                            row.type
                                                        ].map((category) => (
                                                            <SelectItem
                                                                key={
                                                                    category.id
                                                                }
                                                                value={
                                                                    category.id
                                                                }
                                                            >
                                                                {category.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        </td>
                                        <td className="py-2">
                                            {row.matched_keyword ? (
                                                <span className="inline-flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400">
                                                    <Check className="size-3" />
                                                    {row.matched_keyword}
                                                </span>
                                            ) : row.error ? (
                                                <span className="inline-flex items-center gap-1 text-xs text-red-600">
                                                    <X className="size-3" />
                                                    Error
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground text-xs">
                                                    Manual
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ImportPreview.layout = {
    breadcrumbs: [
        { title: 'Import CSV', href: '/import' },
        { title: 'Preview', href: '#' },
    ],
};
