import { Head, router, useForm } from '@inertiajs/react';
import { CalendarClock, Plus, Repeat } from 'lucide-react';
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

type RecurringItem = {
    id: string;
    description: string;
    type: string | null;
    amount: string | null;
    frequency: string;
    next_run_at: string;
    ends_at: string | null;
    is_active: boolean;
};

type FormOptions = {
    accounts: { id: string; name: string }[];
    incomeCategories: { id: string; name: string }[];
    expenseCategories: { id: string; name: string }[];
};

type Props = {
    recurringTransactions: RecurringItem[];
    canCreate: boolean;
    formOptions: FormOptions;
};

const frequencyLabels: Record<string, string> = {
    daily: 'Harian',
    weekly: 'Mingguan',
    monthly: 'Bulanan',
    yearly: 'Tahunan',
};

function CreateRecurringDialog({ formOptions }: { formOptions: FormOptions }) {
    const [open, setOpen] = useState(false);
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors, reset } = useForm({
        description: '',
        type: 'expense' as 'income' | 'expense' | 'transfer',
        amount: '',
        account_id: '',
        category_id: '',
        from_account_id: '',
        to_account_id: '',
        frequency: 'monthly' as 'daily' | 'weekly' | 'monthly' | 'yearly',
        next_run_at: today,
        ends_at: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/recurring', {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" className="gap-2">
                    <Plus className="size-4" />
                    Tambah Template
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Transaksi Berulang</DialogTitle>
                    <DialogDescription>
                        Simpan template transaksi yang akan di-generate otomatis
                        sesuai jadwal.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>Deskripsi</Label>
                        <Input
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Contoh: Sewa kantor bulanan"
                            required
                        />
                    </div>

                    <div className="space-y-2">
                        <Label>Jenis</Label>
                        <Select
                            value={data.type}
                            onValueChange={(v) =>
                                setData(
                                    'type',
                                    v as 'income' | 'expense' | 'transfer',
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="expense">
                                    Pengeluaran
                                </SelectItem>
                                <SelectItem value="income">
                                    Pemasukan
                                </SelectItem>
                                <SelectItem value="transfer">Transfer</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Jumlah (Rp)</Label>
                        <Input
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={data.amount}
                            onChange={(e) =>
                                setData('amount', e.target.value)
                            }
                            required
                        />
                    </div>

                    {data.type !== 'transfer' ? (
                        <>
                            <div className="space-y-2">
                                <Label>Akun</Label>
                                <Select
                                    value={data.account_id}
                                    onValueChange={(v) =>
                                        setData('account_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih akun" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formOptions.accounts.map((a) => (
                                            <SelectItem key={a.id} value={a.id}>
                                                {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Kategori</Label>
                                <Select
                                    value={data.category_id}
                                    onValueChange={(v) =>
                                        setData('category_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(data.type === 'income'
                                            ? formOptions.incomeCategories
                                            : formOptions.expenseCategories
                                        ).map((c) => (
                                            <SelectItem key={c.id} value={c.id}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Dari Akun</Label>
                                <Select
                                    value={data.from_account_id}
                                    onValueChange={(v) =>
                                        setData('from_account_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Sumber" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formOptions.accounts.map((a) => (
                                            <SelectItem key={a.id} value={a.id}>
                                                {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Ke Akun</Label>
                                <Select
                                    value={data.to_account_id}
                                    onValueChange={(v) =>
                                        setData('to_account_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tujuan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formOptions.accounts.map((a) => (
                                            <SelectItem key={a.id} value={a.id}>
                                                {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Frekuensi</Label>
                            <Select
                                value={data.frequency}
                                onValueChange={(v) =>
                                    setData(
                                        'frequency',
                                        v as typeof data.frequency,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(frequencyLabels).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Jalankan Berikutnya</Label>
                            <Input
                                type="date"
                                value={data.next_run_at}
                                onChange={(e) =>
                                    setData('next_run_at', e.target.value)
                                }
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Berakhir (opsional)</Label>
                        <Input
                            type="date"
                            value={data.ends_at}
                            onChange={(e) =>
                                setData('ends_at', e.target.value)
                            }
                        />
                    </div>

                    {errors.description && (
                        <p className="text-destructive text-sm">
                            {errors.description}
                        </p>
                    )}

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RecurringIndex({
    recurringTransactions,
    canCreate,
    formOptions,
}: Props) {
    function deactivate(id: string) {
        if (!confirm('Nonaktifkan transaksi berulang ini?')) return;
        router.delete(`/recurring/${id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Transaksi Berulang" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Transaksi Berulang
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Template transaksi yang di-generate otomatis oleh
                            scheduler harian.
                        </p>
                    </div>
                    {canCreate && (
                        <CreateRecurringDialog formOptions={formOptions} />
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Repeat className="size-4" />
                            Template Aktif
                        </CardTitle>
                        <CardDescription>
                            Jalankan manual:{' '}
                            <code className="text-xs">
                                php artisan accounting:process-recurring
                            </code>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recurringTransactions.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <CalendarClock className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Belum ada template transaksi berulang.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {recurringTransactions.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center gap-4 py-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium">
                                                {item.description}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {frequencyLabels[item.frequency]}{' '}
                                                · Berikutnya:{' '}
                                                {item.next_run_at}
                                                {item.amount &&
                                                    ` · Rp ${Number(item.amount).toLocaleString('id-ID')}`}
                                            </p>
                                        </div>
                                        {canCreate && item.is_active && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    deactivate(item.id)
                                                }
                                            >
                                                Nonaktifkan
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

RecurringIndex.layout = {
    breadcrumbs: [{ title: 'Transaksi Berulang', href: '/recurring' }],
};
