import { Head, router, useForm } from '@inertiajs/react';
import { BookMarked, Edit2, MoreHorizontal, Plus, Trash2 } from 'lucide-react';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';

type AccountRow = {
    id: string;
    name: string;
    type: AccountType;
    is_active: boolean;
    is_system: boolean;
    entries_count: number;
};

type Props = {
    accounts: AccountRow[];
    canManage: boolean;
};

const typeLabels: Record<AccountType, string> = {
    asset: 'Aset',
    liability: 'Kewajiban',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

function AccountDialog({
    account,
    trigger,
}: {
    account?: AccountRow;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const isEdit = !!account;
    const lockedType = Boolean(account?.is_system || (account?.entries_count ?? 0) > 0);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: account?.name ?? '',
        type: (account?.type ?? 'asset') as AccountType,
        is_active: account?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEdit) {
            patch(`/accounts/${account.id}`, {
                onSuccess: () => setOpen(false),
            });
            return;
        }

        post('/accounts', {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Edit Akun' : 'Tambah Akun Custom'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Ubah nama atau status akun. Tipe akun sistem tidak bisa diubah.'
                            : 'Akun custom punya buku besar sendiri dan bisa dipakai di jurnal/transaksi.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="account-name">Nama akun</Label>
                        <Input
                            id="account-name"
                            value={data.name}
                            disabled={account?.is_system}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Contoh: Inventaris, Pajak, dll."
                        />
                        {errors.name && (
                            <p className="text-destructive text-sm">{errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Tipe</Label>
                        <Select
                            value={data.type}
                            disabled={lockedType}
                            onValueChange={(value: AccountType) =>
                                setData('type', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(typeLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.type && (
                            <p className="text-destructive text-sm">{errors.type}</p>
                        )}
                    </div>

                    {isEdit && (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                            />
                            Aktif
                        </label>
                    )}

                    <div className="flex justify-end gap-2">
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

export default function AccountsIndex({ accounts, canManage }: Props) {
    const grouped = accounts.reduce<Record<string, AccountRow[]>>((carry, account) => {
        carry[account.type] ??= [];
        carry[account.type].push(account);
        return carry;
    }, {});

    return (
        <>
            <Head title="Chart of Accounts" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Chart of Accounts
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Kelola akun sistem dan tambah akun custom untuk entity
                            aktif.
                        </p>
                    </div>
                    {canManage && (
                        <AccountDialog
                            trigger={
                                <Button size="sm" className="gap-2">
                                    <Plus className="size-4" />
                                    Tambah Akun
                                </Button>
                            }
                        />
                    )}
                </div>

                {accounts.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <BookMarked className="text-muted-foreground/40 mb-4 size-12" />
                            <h3 className="font-semibold">Belum ada akun</h3>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-6">
                        {(Object.keys(typeLabels) as AccountType[]).map((type) => {
                            const rows = grouped[type] ?? [];
                            if (rows.length === 0) {
                                return null;
                            }

                            return (
                                <Card key={type}>
                                    <CardHeader>
                                        <CardTitle className="text-base">
                                            {typeLabels[type]}
                                        </CardTitle>
                                        <CardDescription>
                                            {rows.length} akun
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="divide-y">
                                        {rows.map((account) => (
                                            <div
                                                key={account.id}
                                                className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {account.name}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {account.is_system
                                                            ? 'Akun sistem'
                                                            : 'Akun custom'}
                                                        {' · '}
                                                        {account.entries_count} jurnal
                                                        {account.is_active
                                                            ? ''
                                                            : ' · nonaktif'}
                                                    </p>
                                                </div>
                                                {canManage && (
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="size-8 p-0"
                                                            >
                                                                <MoreHorizontal className="size-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <AccountDialog
                                                                account={account}
                                                                trigger={
                                                                    <DropdownMenuItem
                                                                        onSelect={(e) =>
                                                                            e.preventDefault()
                                                                        }
                                                                    >
                                                                        <Edit2 className="mr-2 size-4" />
                                                                        Edit
                                                                    </DropdownMenuItem>
                                                                }
                                                            />
                                                            {!account.is_system && (
                                                                <>
                                                                    <DropdownMenuSeparator />
                                                                    <DropdownMenuItem
                                                                        className="text-destructive focus:text-destructive"
                                                                        onClick={() => {
                                                                            if (
                                                                                confirm(
                                                                                    `Hapus akun ${account.name}?`,
                                                                                )
                                                                            ) {
                                                                                router.delete(
                                                                                    `/accounts/${account.id}`,
                                                                                    {
                                                                                        preserveScroll: true,
                                                                                    },
                                                                                );
                                                                            }
                                                                        }}
                                                                    >
                                                                        <Trash2 className="mr-2 size-4" />
                                                                        Hapus
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                )}
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
