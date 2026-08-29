import { Head, router, useForm } from '@inertiajs/react';
import {
    Building2,
    Edit2,
    Mail,
    MoreHorizontal,
    Phone,
    Plus,
    Trash2,
    Users,
} from 'lucide-react';
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
import type { Client } from '@/types';

type Props = {
    clients: Client[];
    canManage: boolean;
};

function ClientDialog({
    client,
    trigger,
}: {
    client?: Client;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const isEdit = !!client;

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: client?.name ?? '',
        contact_info: client?.contact_info ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            patch(`/clients/${client.id}`, {
                onSuccess: () => {
                    setOpen(false);
                },
            });
        } else {
            post('/clients', {
                onSuccess: () => {
                    reset();
                    setOpen(false);
                },
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Edit Client' : 'Tambah Client Baru'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? `Ubah informasi client ${client.name}.`
                            : 'Tambahkan client baru ke daftar client bisnis.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="client-name">Nama Client</Label>
                        <Input
                            id="client-name"
                            placeholder="PT Acme Indonesia"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            autoFocus
                        />
                        {errors.name && (
                            <p className="text-destructive text-sm">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="client-contact">
                            Info Kontak{' '}
                            <span className="text-muted-foreground text-xs">
                                (opsional)
                            </span>
                        </Label>
                        <textarea
                            id="client-contact"
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Email: ceo@acme.com&#10;Telp: 021-xxx-xxxx&#10;Alamat: Jakarta..."
                            value={data.contact_info ?? ''}
                            onChange={(e) =>
                                setData('contact_info', e.target.value)
                            }
                        />
                        {errors.contact_info && (
                            <p className="text-destructive text-sm">
                                {errors.contact_info}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Menyimpan...'
                                : isEdit
                                  ? 'Simpan Perubahan'
                                  : 'Tambah Client'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ClientCard({
    client,
    canManage,
}: {
    client: Client;
    canManage: boolean;
}) {
    function handleDelete() {
        if (!confirm(`Hapus client "${client.name}"? Aksi ini tidak bisa dibatalkan.`))
            return;
        router.delete(`/clients/${client.id}`, { preserveScroll: true });
    }

    return (
        <Card className="group transition-shadow hover:shadow-md">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                    {/* Icon + Info */}
                    <div className="flex min-w-0 items-start gap-3">
                        <div className="bg-primary/10 flex size-10 shrink-0 items-center justify-center rounded-lg">
                            <Building2 className="text-primary size-5" />
                        </div>
                        <div className="min-w-0">
                            <p className="truncate font-semibold">
                                {client.name}
                            </p>
                            {client.contact_info && (
                                <p className="text-muted-foreground mt-0.5 line-clamp-2 text-xs">
                                    {client.contact_info}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Actions */}
                    {canManage && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="size-8 shrink-0 p-0 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <MoreHorizontal className="size-4" />
                                    <span className="sr-only">Aksi</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <ClientDialog
                                    client={client}
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
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    onClick={handleDelete}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 size-4" />
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>

                {/* Stats */}
                <div className="mt-4 flex gap-4 border-t pt-4">
                    <div className="flex items-center gap-1.5 text-xs">
                        <Users className="text-muted-foreground size-3.5" />
                        <span className="text-muted-foreground">
                            {client.projects_count ?? 0} project
                        </span>
                    </div>
                    <div className="flex items-center gap-1.5 text-xs">
                        <Mail className="text-muted-foreground size-3.5" />
                        <span className="text-muted-foreground">
                            {client.transactions_count ?? 0} transaksi
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ClientsIndex({ clients, canManage }: Props) {
    return (
        <>
            <Head title="Clients" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Clients
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Daftar klien bisnis yang terhubung ke project & transaksi.
                        </p>
                    </div>
                    {canManage && (
                        <ClientDialog
                            trigger={
                                <Button size="sm" className="gap-2">
                                    <Plus className="size-4" />
                                    Tambah Client
                                </Button>
                            }
                        />
                    )}
                </div>

                {/* Content */}
                {clients.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <Building2 className="text-muted-foreground/40 mb-4 size-12" />
                            <h3 className="font-semibold">Belum ada client</h3>
                            <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                Tambahkan client pertama untuk mulai mengelola
                                proyek dan transaksi bisnis.
                            </p>
                            {canManage && (
                                <ClientDialog
                                    trigger={
                                        <Button className="mt-4 gap-2" size="sm">
                                            <Plus className="size-4" />
                                            Tambah Client
                                        </Button>
                                    }
                                />
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {clients.map((client) => (
                            <ClientCard
                                key={client.id}
                                client={client}
                                canManage={canManage}
                            />
                        ))}
                    </div>
                )}

                {/* Info Card */}
                {clients.length > 0 && (
                    <Card className="bg-muted/30 border-dashed">
                        <CardContent className="flex items-center gap-3 py-3">
                            <Phone className="text-muted-foreground size-4 shrink-0" />
                            <p className="text-muted-foreground text-xs">
                                Info kontak client bisa berisi email, telepon, atau alamat dalam format bebas.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
