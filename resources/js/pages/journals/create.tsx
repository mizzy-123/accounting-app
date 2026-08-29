import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AccountOption } from '@/types';

type JournalRow = {
    account_id: string;
    debit: string;
    kredit: string;
};

type Props = {
    accounts: AccountOption[];
};

export default function JournalsCreate({ accounts }: Props) {
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors } = useForm<{
        date: string;
        description: string;
        entries: JournalRow[];
        attachment: File | null;
    }>({
        date: today,
        description: '',
        entries: [
            { account_id: '', debit: '', kredit: '' },
            { account_id: '', debit: '', kredit: '' },
        ],
        attachment: null,
    });

    function updateEntry(
        index: number,
        field: keyof JournalRow,
        value: string,
    ) {
        const entries = [...data.entries];
        entries[index] = { ...entries[index], [field]: value };
        setData('entries', entries);
    }

    function addRow() {
        setData('entries', [
            ...data.entries,
            { account_id: '', debit: '', kredit: '' },
        ]);
    }

    function removeRow(index: number) {
        if (data.entries.length <= 2) return;
        setData(
            'entries',
            data.entries.filter((_, i) => i !== index),
        );
    }

    const totalDebit = data.entries.reduce(
        (sum, row) => sum + (Number(row.debit) || 0),
        0,
    );
    const totalKredit = data.entries.reduce(
        (sum, row) => sum + (Number(row.kredit) || 0),
        0,
    );
    const isBalanced = totalDebit.toFixed(2) === totalKredit.toFixed(2);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/journals', { forceFormData: true });
    }

    return (
        <>
            <Head title="Jurnal Penyesuaian" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Jurnal Penyesuaian
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Input debit/kredit langsung. Hanya owner yang bisa
                        mengakses halaman ini.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Jurnal Manual
                        </CardTitle>
                        <CardDescription>
                            Total debit harus sama dengan total kredit sebelum
                            disimpan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="date">Tanggal</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={data.date}
                                        onChange={(e) =>
                                            setData('date', e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Deskripsi
                                    </Label>
                                    <Input
                                        id="description"
                                        value={data.description}
                                        onChange={(e) =>
                                            setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Alasan penyesuaian"
                                    />
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="grid grid-cols-[1fr_120px_120px_40px] gap-2 text-xs font-medium">
                                    <span>Akun</span>
                                    <span className="text-right">Debit</span>
                                    <span className="text-right">Kredit</span>
                                    <span />
                                </div>

                                {data.entries.map((entry, index) => (
                                    <div
                                        key={index}
                                        className="grid grid-cols-[1fr_120px_120px_40px] items-start gap-2"
                                    >
                                        <Select
                                            value={entry.account_id}
                                            onValueChange={(value) =>
                                                updateEntry(
                                                    index,
                                                    'account_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih akun" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {accounts.map((account) => (
                                                    <SelectItem
                                                        key={account.id}
                                                        value={account.id}
                                                    >
                                                        {account.name} (
                                                        {account.type})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                            value={entry.debit}
                                            onChange={(e) =>
                                                updateEntry(
                                                    index,
                                                    'debit',
                                                    e.target.value,
                                                )
                                            }
                                            className="text-right"
                                        />
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                            value={entry.kredit}
                                            onChange={(e) =>
                                                updateEntry(
                                                    index,
                                                    'kredit',
                                                    e.target.value,
                                                )
                                            }
                                            className="text-right"
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => removeRow(index)}
                                            disabled={data.entries.length <= 2}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ))}

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="gap-2"
                                    onClick={addRow}
                                >
                                    <Plus className="size-4" />
                                    Tambah Baris
                                </Button>
                            </div>

                            <div
                                className={`rounded-lg border p-3 text-sm ${
                                    isBalanced
                                        ? 'border-emerald-500/30 bg-emerald-500/5 text-emerald-700 dark:text-emerald-400'
                                        : 'border-amber-500/30 bg-amber-500/5 text-amber-700 dark:text-amber-400'
                                }`}
                            >
                                Total Debit: {totalDebit.toFixed(2)} · Total
                                Kredit: {totalKredit.toFixed(2)}
                                {isBalanced
                                    ? ' · Seimbang'
                                    : ' · Belum seimbang'}
                            </div>

                            {errors.entries && (
                                <p className="text-destructive text-sm">
                                    {errors.entries}
                                </p>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="attachment">Lampiran</Label>
                                <Input
                                    id="attachment"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    onChange={(e) =>
                                        setData(
                                            'attachment',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button variant="outline" asChild>
                                    <Link href="/transactions">Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !isBalanced}
                                >
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Jurnal'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

JournalsCreate.layout = {
    breadcrumbs: [
        { title: 'Transaksi', href: '/transactions' },
        { title: 'Jurnal Penyesuaian', href: '/journals/create' },
    ],
};
