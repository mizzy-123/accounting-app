import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
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
import type { AccountOption, CategoryOption, Client, OtherEntityOption } from '@/types';
import type { Project } from '@/types';

type SimpleType = 'income' | 'expense' | 'transfer';
type FormMode = 'simple' | 'inter-entity';

type Props = {
    accounts: AccountOption[];
    paymentAccounts: AccountOption[];
    transferDestinationAccounts: AccountOption[];
    chartOverview: Partial<
        Record<'asset' | 'liability' | 'equity' | 'revenue' | 'expense', AccountOption[]>
    >;
    incomeCategories: CategoryOption[];
    expenseCategories: CategoryOption[];
    canInterEntityTransfer: boolean;
    otherEntities: OtherEntityOption[];
    projects: Pick<Project, 'id' | 'name' | 'client_id'>[];
    clients: Pick<Client, 'id' | 'name'>[];
    isBusiness: boolean;
};

const typeLabels: Record<string, string> = {
    asset: 'Aset / Dompet',
    liability: 'Kewajiban',
    equity: 'Ekuitas',
    revenue: 'Pendapatan (otomatis)',
    expense: 'Beban (otomatis)',
};

function accountLabel(account: AccountOption): string {
    if (account.type === 'liability') {
        return `${account.name} · Kewajiban`;
    }

    return account.name;
}

export default function TransactionsCreate({
    accounts,
    paymentAccounts,
    transferDestinationAccounts,
    chartOverview,
    incomeCategories,
    expenseCategories,
    canInterEntityTransfer,
    otherEntities,
    projects,
    clients,
    isBusiness,
}: Props) {
    const today = new Date().toISOString().slice(0, 10);
    const [mode, setMode] = useState<FormMode>('simple');
    const [simpleType, setSimpleType] = useState<SimpleType>('expense');

    const simpleForm = useForm({
        type: 'expense' as SimpleType,
        date: today,
        amount: '',
        account_id: '',
        category_id: '',
        from_account_id: '',
        to_account_id: '',
        description: '',
        project_id: '',
        client_id: '',
        attachment: null as File | null,
    });

    const interEntityForm = useForm({
        date: today,
        amount: '',
        from_account_id: '',
        to_entity_id: otherEntities[0]?.id ?? '',
        to_account_id: '',
        description: '',
    });

    const selectedEntity = useMemo(
        () =>
            otherEntities.find(
                (entity) => entity.id === interEntityForm.data.to_entity_id,
            ),
        [interEntityForm.data.to_entity_id, otherEntities],
    );

    function submitSimple(e: React.FormEvent) {
        e.preventDefault();
        simpleForm.transform((data) => ({
            ...data,
            type: simpleType,
        }));
        simpleForm.post('/transactions', {
            forceFormData: true,
        });
    }

    function submitInterEntity(e: React.FormEvent) {
        e.preventDefault();
        interEntityForm.post('/transactions/inter-entity');
    }

    return (
        <>
            <Head title="Catat Transaksi" />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Catat Transaksi
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Pilih dompet/sumber dana — sistem otomatis menjurnal
                        ke akun Pendapatan atau Beban.
                    </p>
                </div>

                <Card className="border-dashed">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Kenapa tidak semua akun muncul?
                        </CardTitle>
                        <CardDescription>
                            Form ini hanya menampilkan akun yang relevan sebagai
                            sumber/tujuan uang. Akun lain tetap ada di chart of
                            accounts dan dipakai otomatis atau lewat Jurnal
                            Penyesuaian.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
                        {(
                            [
                                'asset',
                                'liability',
                                'equity',
                                'revenue',
                                'expense',
                            ] as const
                        ).map((type) => {
                            const rows = chartOverview[type] ?? [];
                            if (rows.length === 0) {
                                return null;
                            }

                            return (
                                <div key={type}>
                                    <p className="text-muted-foreground mb-1 text-xs font-medium uppercase tracking-wide">
                                        {typeLabels[type]}
                                    </p>
                                    <p className="text-foreground">
                                        {rows.map((a) => a.name).join(', ')}
                                    </p>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                {canInterEntityTransfer && (
                    <div className="grid grid-cols-2 gap-2">
                        <Button
                            type="button"
                            variant={mode === 'simple' ? 'default' : 'outline'}
                            onClick={() => setMode('simple')}
                        >
                            Transaksi Biasa
                        </Button>
                        <Button
                            type="button"
                            variant={
                                mode === 'inter-entity' ? 'default' : 'outline'
                            }
                            onClick={() => setMode('inter-entity')}
                        >
                            Transfer Antar Entity
                        </Button>
                    </div>
                )}

                {mode === 'simple' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Transaksi Baru
                            </CardTitle>
                            <CardDescription>
                                Pilih jenis transaksi lalu isi detailnya.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submitSimple}
                                className="space-y-4"
                            >
                                <div className="grid grid-cols-3 gap-2">
                                    {(
                                        [
                                            'income',
                                            'expense',
                                            'transfer',
                                        ] as SimpleType[]
                                    ).map((type) => (
                                        <Button
                                            key={type}
                                            type="button"
                                            variant={
                                                simpleType === type
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            onClick={() => {
                                                setSimpleType(type);
                                                simpleForm.setData(
                                                    'account_id',
                                                    '',
                                                );
                                                simpleForm.setData(
                                                    'category_id',
                                                    '',
                                                );
                                            }}
                                        >
                                            {type === 'income' && 'Pemasukan'}
                                            {type === 'expense' && 'Pengeluaran'}
                                            {type === 'transfer' && 'Transfer'}
                                        </Button>
                                    ))}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="date">Tanggal</Label>
                                        <Input
                                            id="date"
                                            type="date"
                                            value={simpleForm.data.date}
                                            onChange={(e) =>
                                                simpleForm.setData(
                                                    'date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">
                                            Jumlah (Rp)
                                        </Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={simpleForm.data.amount}
                                            onChange={(e) =>
                                                simpleForm.setData(
                                                    'amount',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        {simpleForm.errors.amount && (
                                            <p className="text-destructive text-sm">
                                                {simpleForm.errors.amount}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {simpleType !== 'transfer' && (
                                    <>
                                        <div className="space-y-2">
                                            <Label>
                                                {simpleType === 'income'
                                                    ? 'Masuk ke akun (dompet)'
                                                    : 'Dibayar dari akun'}
                                            </Label>
                                            <p className="text-muted-foreground text-xs">
                                                {simpleType === 'income'
                                                    ? 'Hanya akun aset: Kas, Bank, E-Wallet, Piutang, dll. Lawan jurnal: Pendapatan (otomatis).'
                                                    : 'Bisa aset (Kas/Bank/E-Wallet) atau kewajiban (mis. Hutang Kartu Kredit). Lawan jurnal: Beban (otomatis).'}
                                            </p>
                                            <Select
                                                value={simpleForm.data.account_id}
                                                onValueChange={(value) =>
                                                    simpleForm.setData(
                                                        'account_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih akun" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {(simpleType === 'income'
                                                        ? accounts
                                                        : paymentAccounts
                                                    ).map((account) => (
                                                        <SelectItem
                                                            key={account.id}
                                                            value={account.id}
                                                        >
                                                            {accountLabel(
                                                                account,
                                                            )}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {simpleForm.errors.account_id && (
                                                <p className="text-destructive text-sm">
                                                    {simpleForm.errors.account_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Kategori</Label>
                                            <Select
                                                value={
                                                    simpleForm.data.category_id
                                                }
                                                onValueChange={(value) =>
                                                    simpleForm.setData(
                                                        'category_id',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih kategori" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {(simpleType === 'income'
                                                        ? incomeCategories
                                                        : expenseCategories
                                                    ).map((category) => (
                                                        <SelectItem
                                                            key={category.id}
                                                            value={category.id}
                                                        >
                                                            {category.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </>
                                )}

                                {simpleType === 'transfer' && (
                                    <div className="space-y-3">
                                        <p className="text-muted-foreground text-xs">
                                            Transfer antar dompet, atau bayar
                                            kewajiban (dari Bank ke Hutang Kartu
                                            Kredit).
                                        </p>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Dari akun (dompet)</Label>
                                                <Select
                                                    value={
                                                        simpleForm.data
                                                            .from_account_id
                                                    }
                                                    onValueChange={(value) =>
                                                        simpleForm.setData(
                                                            'from_account_id',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Pilih akun sumber" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {accounts.map(
                                                            (account) => (
                                                                <SelectItem
                                                                    key={
                                                                        account.id
                                                                    }
                                                                    value={
                                                                        account.id
                                                                    }
                                                                >
                                                                    {
                                                                        account.name
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="space-y-2">
                                                <Label>
                                                    Ke akun (dompet / hutang)
                                                </Label>
                                                <Select
                                                    value={
                                                        simpleForm.data
                                                            .to_account_id
                                                    }
                                                    onValueChange={(value) =>
                                                        simpleForm.setData(
                                                            'to_account_id',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Pilih akun tujuan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {transferDestinationAccounts.map(
                                                            (account) => (
                                                                <SelectItem
                                                                    key={
                                                                        account.id
                                                                    }
                                                                    value={
                                                                        account.id
                                                                    }
                                                                >
                                                                    {accountLabel(
                                                                        account,
                                                                    )}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Deskripsi
                                    </Label>
                                    <Input
                                        id="description"
                                        value={simpleForm.data.description}
                                        onChange={(e) =>
                                            simpleForm.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Opsional"
                                    />
                                </div>

                                {/* Tag ke Project — hanya entity bisnis, hanya income/expense */}
                                {isBusiness &&
                                    simpleType !== 'transfer' && (
                                        <div className="space-y-3 rounded-lg border border-dashed p-4">
                                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                                                Tag ke Project{' '}
                                                <span className="normal-case">
                                                    (opsional)
                                                </span>
                                            </p>
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="project-id">
                                                        Project
                                                    </Label>
                                                    <Select
                                                        value={
                                                            simpleForm.data
                                                                .project_id ||
                                                            'none'
                                                        }
                                                        onValueChange={(v) => {
                                                            const pid =
                                                                v === 'none'
                                                                    ? ''
                                                                    : v;
                                                            simpleForm.setData(
                                                                'project_id',
                                                                pid,
                                                            );
                                                            // Auto-fill client dari project yang dipilih
                                                            if (pid) {
                                                                const proj =
                                                                    projects.find(
                                                                        (p) =>
                                                                            p.id ===
                                                                            pid,
                                                                    );
                                                                if (
                                                                    proj?.client_id
                                                                ) {
                                                                    simpleForm.setData(
                                                                        'client_id',
                                                                        proj.client_id,
                                                                    );
                                                                }
                                                            }
                                                        }}
                                                    >
                                                        <SelectTrigger id="project-id">
                                                            <SelectValue placeholder="Pilih project..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">
                                                                <span className="text-muted-foreground">
                                                                    Tanpa project
                                                                </span>
                                                            </SelectItem>
                                                            {projects.map(
                                                                (p) => (
                                                                    <SelectItem
                                                                        key={
                                                                            p.id
                                                                        }
                                                                        value={
                                                                            p.id
                                                                        }
                                                                    >
                                                                        {p.name}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="client-id">
                                                        Client
                                                    </Label>
                                                    <Select
                                                        value={
                                                            simpleForm.data
                                                                .client_id ||
                                                            'none'
                                                        }
                                                        onValueChange={(v) =>
                                                            simpleForm.setData(
                                                                'client_id',
                                                                v === 'none'
                                                                    ? ''
                                                                    : v,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger id="client-id">
                                                            <SelectValue placeholder="Pilih client..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">
                                                                <span className="text-muted-foreground">
                                                                    Tanpa client
                                                                </span>
                                                            </SelectItem>
                                                            {clients.map(
                                                                (c) => (
                                                                    <SelectItem
                                                                        key={
                                                                            c.id
                                                                        }
                                                                        value={
                                                                            c.id
                                                                        }
                                                                    >
                                                                        {c.name}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                <div className="space-y-2">
                                    <Label htmlFor="attachment">
                                        Lampiran (struk/invoice)
                                    </Label>
                                    <Input
                                        id="attachment"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        onChange={(e) =>
                                            simpleForm.setData(
                                                'attachment',
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </div>

                                <div className="flex justify-end gap-2 pt-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href="/transactions">Batal</Link>
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={simpleForm.processing}
                                    >
                                        {simpleForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Transaksi'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Transfer Antar Entity
                            </CardTitle>
                            <CardDescription>
                                Contoh: owner draw dari bisnis ke personal.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submitInterEntity}
                                className="space-y-4"
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="ie-date">Tanggal</Label>
                                        <Input
                                            id="ie-date"
                                            type="date"
                                            value={interEntityForm.data.date}
                                            onChange={(e) =>
                                                interEntityForm.setData(
                                                    'date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="ie-amount">
                                            Jumlah (Rp)
                                        </Label>
                                        <Input
                                            id="ie-amount"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={interEntityForm.data.amount}
                                            onChange={(e) =>
                                                interEntityForm.setData(
                                                    'amount',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label>Dari Akun (entity aktif)</Label>
                                    <Select
                                        value={
                                            interEntityForm.data.from_account_id
                                        }
                                        onValueChange={(value) =>
                                            interEntityForm.setData(
                                                'from_account_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih akun sumber" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accounts.map((account) => (
                                                <SelectItem
                                                    key={account.id}
                                                    value={account.id}
                                                >
                                                    {account.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Entity Tujuan</Label>
                                    <Select
                                        value={interEntityForm.data.to_entity_id}
                                        onValueChange={(value) => {
                                            interEntityForm.setData({
                                                ...interEntityForm.data,
                                                to_entity_id: value,
                                                to_account_id: '',
                                            });
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih entity" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {otherEntities.map((entity) => (
                                                <SelectItem
                                                    key={entity.id}
                                                    value={entity.id}
                                                >
                                                    {entity.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Ke Akun (entity tujuan)</Label>
                                    <Select
                                        value={interEntityForm.data.to_account_id}
                                        onValueChange={(value) =>
                                            interEntityForm.setData(
                                                'to_account_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih akun tujuan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(selectedEntity?.accounts ?? []).map(
                                                (account) => (
                                                    <SelectItem
                                                        key={account.id}
                                                        value={account.id}
                                                    >
                                                        {account.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="ie-description">
                                        Deskripsi
                                    </Label>
                                    <Input
                                        id="ie-description"
                                        value={interEntityForm.data.description}
                                        onChange={(e) =>
                                            interEntityForm.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Contoh: Owner draw bulan ini"
                                    />
                                </div>

                                <div className="flex justify-end gap-2 pt-2">
                                    <Button
                                        type="submit"
                                        disabled={interEntityForm.processing}
                                    >
                                        {interEntityForm.processing
                                            ? 'Menyimpan...'
                                            : 'Transfer'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

TransactionsCreate.layout = {
    breadcrumbs: [
        { title: 'Transaksi', href: '/transactions' },
        { title: 'Catat Baru', href: '/transactions/create' },
    ],
};
