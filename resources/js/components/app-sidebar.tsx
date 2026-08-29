import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    BookOpen,
    Briefcase,
    Building2,
    CalendarClock,
    FileText,
    FolderGit2,
    LayoutGrid,
    NotebookPen,
    Users,
    Wallet,
} from 'lucide-react';
import { EntitySwitcher } from '@/components/entity-switcher';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { activeEntity } = usePage().props;

    // Nav items yang selalu muncul
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Transaksi',
            href: '/transactions',
            icon: ArrowLeftRight,
        },
    ];

    if (activeEntity?.role === 'owner') {
        mainNavItems.push({
            title: 'Jurnal Penyesuaian',
            href: '/journals/create',
            icon: NotebookPen,
        });
    }

    if (activeEntity?.role !== 'viewer') {
        mainNavItems.push({
            title: 'Transaksi Berulang',
            href: '/recurring',
            icon: CalendarClock,
        });
    }

    // Nav items khusus entity bisnis
    if (activeEntity?.type === 'business') {
        mainNavItems.push(
            {
                title: 'Clients',
                href: '/clients',
                icon: Building2,
            },
            {
                title: 'Projects',
                href: '/projects',
                icon: Briefcase,
            },
            {
                title: 'Invoice',
                href: '/invoices',
                icon: FileText,
            },
            {
                title: 'Piutang',
                href: '/invoices/receivables',
                icon: Wallet,
            },
        );
    }

    // Manajemen tim — hanya owner entity bisnis
    if (activeEntity?.type === 'business' && activeEntity?.role === 'owner') {
        mainNavItems.push({
            title: 'Manajemen Tim',
            href: `/entity/${activeEntity.id}/team`,
            icon: Users,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                {/* App logo */}
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                {/* Entity switcher — pindah antara Personal / Manifestasi */}
                <EntitySwitcher />
            </SidebarHeader>

            <SidebarSeparator />

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
