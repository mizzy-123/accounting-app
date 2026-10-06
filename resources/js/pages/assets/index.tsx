import { Head, router, useForm } from '@inertiajs/react';
import { Landmark, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    depreciate as depreciateAsset,
    destroy as destroyAsset,
    dispose as disposeAsset,
    store as storeAsset,
} from '@/actions/App/Http/Controllers/FixedAssetController';
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

type AccountOption = {
    id: string;
    name: string;
    type: string;
};

type AssetRow = {
    id: string;
    name: string;
    acquisition_date: string;
    cost: string;
    residual_value: string;
    useful_life_months: number;
    monthly_amount: string;
    accumulated: string;
    book_value: string;
    status: string;
    notes: string | null;
    periods_posted: number;
    asset_account: string | null;
    accumulated_account: string | null;
    expense_account: string | null;
};

type Props = {
    assets: AssetRow[];
    accounts: AccountOption[];
    defaultPeriod: string;
    canManage: boolean;
};

function formatCurrency(amount: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

function statusLabel(status: string): string {
    if (status === 'fully_depreciated') {
        return 'Habis disusutkan';
    }

    if (status === 'disposed') {
        return 'Dilepas';
    }

    return 'Aktif';
}

function CreateAssetDialog({ accounts }: { accounts: AccountOption[] }) {
    const [open, setOpen] = useState(false);
    const assetAccounts = useMemo(
        () => accounts.filter((account) => account.type === 'asset'),
        [accounts],
    );
    const expenseAccounts = useMemo(
        () => accounts.filter((account) => account.type === 'expense'),
        [accounts],
    );
    const paymentAccounts = useMemo(
        () =>
            accounts.filter((account) =>
                ['asset', 'liability'].includes(account.type),
            ),
        [accounts],
    );

    const defaultAsset =
        assetAccounts.find((account) => account.name === 'Aset Tetap')?.id ?? '';
    const defaultAccumulated =
        assetAccounts.find((account) => account.name === 'Akumulasi Penyusutan')
            ?.id ?? '';
    const defaultExpense =
        expenseAccounts.find((account) => account.name === 'Beban Penyusutan')
            ?.id ?? '';

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        asset_account_id: defaultAsset,
        accumulated_account_id: defaultAccumulated,
        expense_account_id: defaultExpense,
        payment_account_id: '',
        acquisition_date: new Date().toISOString().slice(0, 10),
        cost: '',
        residual_value: '0',
        useful_life_months: '36',
        notes: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(storeAsset.url(), {
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
                    Tambah Aset
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Tambah Aset Tetap</DialogTitle>
                    <DialogDescription>
                        Penyusutan memakai metode garis lurus per bulan. Isi
                        akun pembayaran jika ingin otomatis mencatat jurnal
                        perolehan.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="asset-name">Nama aset</Label>
                        <Input
                            id="asset-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Laptop, Motor, Mesin, dll."
                        />
                        {errors.name && (
                            <p className="text-destructive text-sm">{errors.name}</p>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Tanggal perolehan</Label>
                            <Input
                                type="date"
                                value={data.acquisition_date}
                                onChange={(e) =>
                                    setData('acquisition_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Masa manfaat (bulan)</Label>
                            <Input
                                type="number"
                                min={1}
                                value={data.useful_life_months}
                                onChange={(e) =>
                                    setData('useful_life_months', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Harga perolehan</Label>
                            <Input
                                type="number"
                                min={1}
                                value={data.cost}
                                onChange={(e) => setData('cost', e.target.value)}
                            />
                            {errors.cost && (
                                <p className="text-destructive text-sm">
                                    {errors.cost}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label>Nilai residu</Label>
                            <Input
                                type="number"
                                min={0}
                                value={data.residual_value}
                                onChange={(e) =>
                                    setData('residual_value', e.target.value)
                                }
                            />
                            {errors.residual_value && (
                                <p className="text-destructive text-sm">
                                    {errors.residual_value}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Akun aset</Label>
                        <Select
                            value={data.asset_account_id}
                            onValueChange={(value) =>
                                setData('asset_account_id', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun aset" />
                            </SelectTrigger>
                            <SelectContent>
                                {assetAccounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Akun akumulasi penyusutan</Label>
                        <Select
                            value={data.accumulated_account_id}
                            onValueChange={(value) =>
                                setData('accumulated_account_id', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun akumulasi" />
                            </SelectTrigger>
                            <SelectContent>
                                {assetAccounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Akun beban penyusutan</Label>
                        <Select
                            value={data.expense_account_id}
                            onValueChange={(value) =>
                                setData('expense_account_id', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun beban" />
                            </SelectTrigger>
                            <SelectContent>
                                {expenseAccounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>Akun pembayaran (opsional)</Label>
                        <Select
                            value={data.payment_account_id || '__none'}
                            onValueChange={(value) =>
                                setData(
                                    'payment_account_id',
                                    value === '__none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Tidak mencatat jurnal perolehan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none">
                                    Tidak mencatat jurnal perolehan
                                </SelectItem>
                                {paymentAccounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan aset'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DisposeAssetDialog({
    asset,
    accounts,
}: {
    asset: AssetRow;
    accounts: AccountOption[];
}) {
    const [open, setOpen] = useState(false);
    const paymentAccounts = useMemo(
        () =>
            accounts.filter((account) =>
                ['asset', 'liability'].includes(account.type),
            ),
        [accounts],
    );
    const revenueAccounts = useMemo(
        () => accounts.filter((account) => account.type === 'revenue'),
        [accounts],
    );
    const expenseAccounts = useMemo(
        () => accounts.filter((account) => account.type === 'expense'),
        [accounts],
    );

    const { data, setData, post, processing, errors } = useForm({
        date: new Date().toISOString().slice(0, 10),
        proceeds: asset.book_value,
        proceeds_account_id:
            paymentAccounts.find((account) => account.name === 'Kas')?.id ?? '',
        gain_account_id:
            revenueAccounts.find(
                (account) => account.name === 'Pendapatan Pelepasan Aset',
            )?.id ?? '',
        loss_account_id:
            expenseAccounts.find(
                (account) => account.name === 'Kerugian Pelepasan Aset',
            )?.id ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(disposeAsset.url(asset.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" size="sm" variant="outline">
                    Lepas aset
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Lepas {asset.name}</DialogTitle>
                    <DialogDescription>
                        Jurnal akan menutup nilai perolehan dan akumulasi
                        penyusutan. Isi 0 jika aset dihapusbukukan tanpa
                        penjualan.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="space-y-2">
                        <Label>Tanggal pelepasan</Label>
                        <Input
                            type="date"
                            value={data.date}
                            onChange={(e) => setData('date', e.target.value)}
                        />
                        {errors.date && (
                            <p className="text-destructive text-sm">{errors.date}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label>Hasil penjualan</Label>
                        <Input
                            type="number"
                            min={0}
                            value={data.proceeds}
                            onChange={(e) => setData('proceeds', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Akun penerimaan</Label>
                        <Select
                            value={data.proceeds_account_id || '__none'}
                            onValueChange={(value) =>
                                setData(
                                    'proceeds_account_id',
                                    value === '__none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun kas/bank" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none">
                                    Tidak ada penerimaan
                                </SelectItem>
                                {paymentAccounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Mencatat...' : 'Catat pelepasan'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AssetCard({
    asset,
    accounts,
    defaultPeriod,
    canManage,
}: {
    asset: AssetRow;
    accounts: AccountOption[];
    defaultPeriod: string;
    canManage: boolean;
}) {
    const { data, setData, post, processing } = useForm({
        through: defaultPeriod,
    });

    function postDepreciation(e: React.FormEvent) {
        e.preventDefault();
        post(depreciateAsset.url(asset.id), { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <CardTitle className="text-base">{asset.name}</CardTitle>
                        <CardDescription>
                            {statusLabel(asset.status)} · perolehan{' '}
                            {asset.acquisition_date}
                        </CardDescription>
                    </div>
                    {canManage && asset.periods_posted === 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-destructive"
                            onClick={() => {
                                if (confirm(`Hapus aset ${asset.name}?`)) {
                                    router.delete(destroyAsset.url(asset.id), {
                                        preserveScroll: true,
                                    });
                                }
                            }}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
                <div className="grid gap-2 sm:grid-cols-2">
                    <p>Harga: {formatCurrency(asset.cost)}</p>
                    <p>Residu: {formatCurrency(asset.residual_value)}</p>
                    <p>Per bulan: {formatCurrency(asset.monthly_amount)}</p>
                    <p>Terkumpul: {formatCurrency(asset.accumulated)}</p>
                    <p className="font-medium">
                        Nilai buku: {formatCurrency(asset.book_value)}
                    </p>
                    <p>Periode tercatat: {asset.periods_posted}</p>
                </div>
                <p className="text-muted-foreground text-xs">
                    {asset.asset_account} · {asset.accumulated_account} ·{' '}
                    {asset.expense_account}
                </p>

                {canManage && asset.status === 'active' && (
                    <form
                        onSubmit={postDepreciation}
                        className="flex flex-wrap items-end gap-2"
                    >
                        <div className="space-y-1">
                            <Label>Catat sampai</Label>
                            <Input
                                type="month"
                                value={data.through}
                                onChange={(e) => setData('through', e.target.value)}
                            />
                        </div>
                        <Button type="submit" size="sm" disabled={processing}>
                            {processing ? 'Mencatat...' : 'Catat penyusutan'}
                        </Button>
                    </form>
                )}

                {canManage &&
                    (asset.status === 'active' ||
                        asset.status === 'fully_depreciated') && (
                        <DisposeAssetDialog asset={asset} accounts={accounts} />
                    )}
            </CardContent>
        </Card>
    );
}

export default function AssetsIndex({
    assets,
    accounts,
    defaultPeriod,
    canManage,
}: Props) {
    return (
        <>
            <Head title="Aset Tetap" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Aset Tetap
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Catat aset dan penyusutan garis lurus per bulan.
                        </p>
                    </div>
                    {canManage && <CreateAssetDialog accounts={accounts} />}
                </div>

                {assets.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <Landmark className="text-muted-foreground/40 mb-4 size-12" />
                            <h3 className="font-semibold">Belum ada aset tetap</h3>
                            <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                Tambahkan laptop, kendaraan, atau mesin lalu catat
                                penyusutannya setiap bulan.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {assets.map((asset) => (
                            <AssetCard
                                key={asset.id}
                                asset={asset}
                                accounts={accounts}
                                defaultPeriod={defaultPeriod}
                                canManage={canManage}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
