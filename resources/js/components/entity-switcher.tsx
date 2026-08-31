import { router, useForm, usePage } from '@inertiajs/react';
import {
    Building2,
    Check,
    ChevronsUpDown,
    Plus,
    User,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
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
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { Entity, EntityRole, EntityType } from '@/types';

function EntityIcon({ type }: { type: EntityType }) {
    return type === 'personal' ? (
        <User className="size-4" />
    ) : (
        <Building2 className="size-4" />
    );
}

function RoleBadge({ role }: { role: EntityRole }) {
    const colors: Record<EntityRole, string> = {
        owner: 'bg-violet-500/15 text-violet-700 dark:text-violet-400',
        member: 'bg-blue-500/15 text-blue-700 dark:text-blue-400',
        viewer: 'bg-slate-500/15 text-slate-700 dark:text-slate-400',
    };

    return (
        <span
            className={`ml-auto rounded px-1.5 py-0.5 text-[10px] font-medium capitalize ${colors[role]}`}
        >
            {role}
        </span>
    );
}

function entityTypeLabel(type: EntityType): string {
    return type === 'personal' ? 'Pribadi' : 'Bisnis';
}

function CreateEntityDialog({
    open,
    onOpenChange,
    canCreatePersonalEntity,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    canCreatePersonalEntity: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        type: 'business' as EntityType,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        post('/entities', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
                router.flushAll();
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Buat Entity Baru</DialogTitle>
                    <DialogDescription>
                        Setiap entity punya buku besar terpisah. Chart of
                        accounts & kategori default otomatis dibuat. Anda
                        menjadi owner entity ini.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="entity-type">Tipe Entity</Label>
                        <Select
                            value={data.type}
                            onValueChange={(value: EntityType) =>
                                setData('type', value)
                            }
                        >
                            <SelectTrigger id="entity-type">
                                <SelectValue placeholder="Pilih tipe entity" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="business">
                                    Bisnis — keuangan usaha, project, invoice
                                </SelectItem>
                                {canCreatePersonalEntity && (
                                    <SelectItem value="personal">
                                        Pribadi — keuangan pribadi & budget
                                    </SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                        {!canCreatePersonalEntity && (
                            <p className="text-muted-foreground text-xs">
                                Entity pribadi sudah ada — hanya satu entity
                                pribadi per akun.
                            </p>
                        )}
                        {errors.type && (
                            <p className="text-destructive text-sm">
                                {errors.type}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="entity-name">Nama Entity</Label>
                        <Input
                            id="entity-name"
                            placeholder={
                                data.type === 'personal'
                                    ? 'Contoh: Personal, Keuangan Saya'
                                    : 'Contoh: PT ABC, Toko Online'
                            }
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

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Membuat...' : 'Buat Entity'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function EntitySwitcher() {
    const {
        activeEntity,
        entities,
        canCreateEntity,
        canCreatePersonalEntity,
        auth,
    } = usePage().props;
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';
    const [createOpen, setCreateOpen] = useState(false);
    const showCreateEntity = canCreateEntity ?? Boolean(auth?.user);

    if (!entities || entities.length === 0) {
        return null;
    }

    const current = activeEntity ?? null;
    const hasSelection = current !== null;

    function switchEntity(entity: Entity) {
        if (entity.id === current?.id) {
            return;
        }

        router.post(
            `/entity/${entity.id}/switch`,
            {},
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    router.flushAll();
                },
            },
        );
    }

    const triggerLabel = hasSelection
        ? current.name
        : 'Pilih entity';
    const triggerHint = hasSelection
        ? `${entityTypeLabel(current.type)} · ${current.role}`
        : 'Klik untuk memilih';

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                size="lg"
                                className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                tooltip={
                                    hasSelection
                                        ? `${current.name} (${entityTypeLabel(current.type)})`
                                        : 'Pilih entity aktif'
                                }
                                aria-label={
                                    hasSelection
                                        ? `Entity aktif: ${current.name}. Klik untuk ganti.`
                                        : 'Pilih entity aktif'
                                }
                            >
                                <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                                    {hasSelection ? (
                                        <EntityIcon type={current.type} />
                                    ) : (
                                        <Building2 className="size-4 opacity-70" />
                                    )}
                                </div>

                                {!isCollapsed && (
                                    <div className="grid min-w-0 flex-1 text-left text-sm leading-tight">
                                        <span
                                            className={`truncate font-semibold ${!hasSelection ? 'text-muted-foreground' : ''}`}
                                        >
                                            {triggerLabel}
                                        </span>
                                        <span className="text-muted-foreground truncate text-xs capitalize">
                                            {triggerHint}
                                        </span>
                                    </div>
                                )}

                                <ChevronsUpDown className="ml-auto size-4 shrink-0 opacity-60" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            className="w-64 rounded-lg"
                            align="start"
                            side="bottom"
                            sideOffset={4}
                        >
                            <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">
                                {hasSelection
                                    ? 'Ganti entity'
                                    : 'Pilih entity'}
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />

                            {entities.map((entity) => {
                                const isActive = current?.id === entity.id;

                                return (
                                    <DropdownMenuItem
                                        key={entity.id}
                                        onClick={() => switchEntity(entity)}
                                        className="gap-2 p-2"
                                    >
                                        <div className="bg-sidebar flex size-6 items-center justify-center rounded-sm border">
                                            <EntityIcon type={entity.type} />
                                        </div>

                                        <div className="min-w-0 flex-1 text-sm">
                                            <p className="truncate font-medium">
                                                {entity.name}
                                            </p>
                                            <p className="text-muted-foreground text-xs capitalize">
                                                {entityTypeLabel(entity.type)}
                                            </p>
                                        </div>

                                        <RoleBadge role={entity.role} />

                                        {isActive && (
                                            <Check className="text-primary ml-1 size-4 shrink-0" />
                                        )}
                                    </DropdownMenuItem>
                                );
                            })}

                            {showCreateEntity && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onSelect={() => setCreateOpen(true)}
                                        className="gap-2 p-2"
                                    >
                                        <div className="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-sm">
                                            <Plus className="size-4" />
                                        </div>
                                        <span className="font-medium">
                                            Buat entity baru
                                        </span>
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>

                {showCreateEntity && (
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="default"
                            tooltip="Buat entity baru"
                            onClick={() => setCreateOpen(true)}
                            className="text-primary hover:text-primary"
                        >
                            <Plus className="size-4" />
                            {!isCollapsed && (
                                <span className="font-medium">Buat Entity</span>
                            )}
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                )}
            </SidebarMenu>

            {showCreateEntity && (
                <CreateEntityDialog
                    open={createOpen}
                    onOpenChange={setCreateOpen}
                    canCreatePersonalEntity={
                        canCreatePersonalEntity ?? false
                    }
                />
            )}
        </>
    );
}
