import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
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

type ProjectOption = {
    id: string;
    name: string;
    client_id: string | null;
    client_name: string | null;
    status: string;
};

type ClientOption = {
    id: string;
    name: string;
};

type LineItem = {
    name: string;
    qty: string;
    price: string;
};

type Props = {
    projects: ProjectOption[];
    clients: ClientOption[];
    prefillProjectId: string | null;
    suggestedNumber: string;
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
}

export default function InvoicesCreate({
    projects,
    clients,
    prefillProjectId,
    suggestedNumber,
}: Props) {
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors } = useForm({
        project_id: prefillProjectId ?? '',
        client_id: '',
        issued_date: today,
        due_date: '',
        notes: '',
        discount: '0',
        items: [
            { name: '', qty: '1', price: '' },
        ] as LineItem[],
    });

    const selectedProject = useMemo(
        () => projects.find((p) => p.id === data.project_id),
        [data.project_id, projects],
    );

    const lineTotals = data.items.map((item) => {
        const qty = Number(item.qty) || 0;
        const price = Number(item.price) || 0;
        return qty * price;
    });

    const subtotal = lineTotals.reduce((sum, value) => sum + value, 0);
    const discount = Number(data.discount) || 0;
    const total = Math.max(0, subtotal - discount);

    function updateItem(index: number, field: keyof LineItem, value: string) {
        const items = [...data.items];
        items[index] = { ...items[index], [field]: value };
        setData('items', items);
    }

    function addItem() {
        setData('items', [...data.items, { name: '', qty: '1', price: '' }]);
    }

    function removeItem(index: number) {
        if (data.items.length <= 1) return;
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    }

    function onProjectChange(projectId: string) {
        const project = projects.find((p) => p.id === projectId);
        setData({
            ...data,
            project_id: projectId,
            client_id: project?.client_id ?? data.client_id,
        });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/invoices');
    }

    return (
        <>
            <Head title="Buat Invoice" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Buat Invoice
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Nomor otomatis: {suggestedNumber}. Total dihitung dari
                        item × harga.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Detail Invoice</CardTitle>
                        <CardDescription>
                            Pilih project, isi item, lalu simpan sebagai draft.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Project</Label>
                                    <Select
                                        value={data.project_id}
                                        onValueChange={onProjectChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih project" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {projects.map((project) => (
                                                <SelectItem
                                                    key={project.id}
                                                    value={project.id}
                                                >
                                                    {project.name}
                                                    {project.client_name
                                                        ? ` · ${project.client_name}`
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.project_id && (
                                        <p className="text-destructive text-sm">
                                            {errors.project_id}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label>Client</Label>
                                    <Select
                                        value={data.client_id}
                                        onValueChange={(v) =>
                                            setData('client_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Opsional / dari project" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem
                                                    key={client.id}
                                                    value={client.id}
                                                >
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {selectedProject?.client_name &&
                                        !data.client_id && (
                                            <p className="text-muted-foreground text-xs">
                                                Default dari project:{' '}
                                                {selectedProject.client_name}
                                            </p>
                                        )}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="issued_date">
                                        Tanggal Terbit
                                    </Label>
                                    <Input
                                        id="issued_date"
                                        type="date"
                                        value={data.issued_date}
                                        onChange={(e) =>
                                            setData(
                                                'issued_date',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="due_date">
                                        Jatuh Tempo
                                    </Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) =>
                                            setData('due_date', e.target.value)
                                        }
                                    />
                                    {errors.due_date && (
                                        <p className="text-destructive text-sm">
                                            {errors.due_date}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <Label>Item</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="gap-1"
                                        onClick={addItem}
                                    >
                                        <Plus className="size-3.5" />
                                        Tambah
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {data.items.map((item, index) => (
                                        <div
                                            key={index}
                                            className="grid grid-cols-[1fr_80px_120px_40px] items-start gap-2"
                                        >
                                            <Input
                                                placeholder="Nama item"
                                                value={item.name}
                                                onChange={(e) =>
                                                    updateItem(
                                                        index,
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <Input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="Qty"
                                                value={item.qty}
                                                onChange={(e) =>
                                                    updateItem(
                                                        index,
                                                        'qty',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="Harga"
                                                value={item.price}
                                                onChange={(e) =>
                                                    updateItem(
                                                        index,
                                                        'price',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    removeItem(index)
                                                }
                                                disabled={data.items.length <= 1}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                                {errors.items && (
                                    <p className="text-destructive text-sm">
                                        {errors.items}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="discount">Diskon (Rp)</Label>
                                    <Input
                                        id="discount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.discount}
                                        onChange={(e) =>
                                            setData('discount', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="notes">Catatan</Label>
                                    <Input
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) =>
                                            setData('notes', e.target.value)
                                        }
                                        placeholder="Opsional"
                                    />
                                </div>
                            </div>

                            <div className="bg-muted/40 space-y-1 rounded-lg border p-4 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span className="tabular-nums">
                                        {formatCurrency(subtotal)}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Diskon
                                    </span>
                                    <span className="tabular-nums">
                                        {formatCurrency(discount)}
                                    </span>
                                </div>
                                <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                    <span>Total</span>
                                    <span className="tabular-nums">
                                        {formatCurrency(total)}
                                    </span>
                                </div>
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/invoices">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Draft'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

InvoicesCreate.layout = {
    breadcrumbs: [
        { title: 'Invoice', href: '/invoices' },
        { title: 'Buat Baru', href: '/invoices/create' },
    ],
};
