import { router, usePage } from '@inertiajs/react';
import {
    Building2,
    Check,
    ChevronsUpDown,
    User,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { Entity } from '@/types';

function EntityIcon({ type }: { type: Entity['type'] }) {
    return type === 'personal' ? (
        <User className="size-4" />
    ) : (
        <Building2 className="size-4" />
    );
}

function RoleBadge({ role }: { role: Entity['role'] }) {
    const colors: Record<Entity['role'], string> = {
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

export function EntitySwitcher() {
    const { activeEntity, entities } = usePage().props;
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    if (!entities || entities.length === 0) {
        return null;
    }

    function switchEntity(entity: Entity) {
        if (entity.id === activeEntity?.id) return;

        // UUID entity.id adalah string — gunakan URL langsung
        router.post(
            `/entity/${entity.id}/switch`,
            {},
            {
                preserveScroll: true,
                preserveState: false,
            },
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            tooltip={activeEntity?.name ?? 'Pilih Entity'}
                        >
                            {/* Entity icon */}
                            <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                                {activeEntity ? (
                                    <EntityIcon type={activeEntity.type} />
                                ) : (
                                    <Building2 className="size-4" />
                                )}
                            </div>

                            {/* Entity name & role — hidden saat collapsed */}
                            {!isCollapsed && (
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">
                                        {activeEntity?.name ?? 'Pilih Entity'}
                                    </span>
                                    <span className="text-muted-foreground truncate text-xs capitalize">
                                        {activeEntity?.type === 'personal'
                                            ? 'Pribadi'
                                            : 'Bisnis'}{' '}
                                        ·{' '}
                                        <span className="capitalize">
                                            {activeEntity?.role}
                                        </span>
                                    </span>
                                </div>
                            )}

                            <ChevronsUpDown className="ml-auto size-4 shrink-0" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="w-64 rounded-lg"
                        align="start"
                        side="bottom"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">
                            Pilih Entity
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />

                        {entities.map((entity) => (
                            <DropdownMenuItem
                                key={entity.id}
                                onClick={() => switchEntity(entity)}
                                className="gap-2 p-2"
                            >
                                {/* Icon */}
                                <div className="bg-sidebar flex size-6 items-center justify-center rounded-sm border">
                                    <EntityIcon type={entity.type} />
                                </div>

                                {/* Name */}
                                <div className="flex-1 text-sm">
                                    <p className="font-medium">{entity.name}</p>
                                    <p className="text-muted-foreground text-xs capitalize">
                                        {entity.type === 'personal'
                                            ? 'Pribadi'
                                            : 'Bisnis'}
                                    </p>
                                </div>

                                {/* Role badge */}
                                <RoleBadge role={entity.role} />

                                {/* Active indicator */}
                                {activeEntity?.id === entity.id && (
                                    <Check className="text-primary ml-1 size-4 shrink-0" />
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
