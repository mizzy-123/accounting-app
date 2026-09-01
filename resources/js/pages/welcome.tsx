import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Building2,
    ChartColumn,
    Check,
    Shield,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';

const features = [
    {
        icon: Building2,
        title: 'Multi-entity',
        description:
            'Pisahkan keuangan pribadi dan bisnis dalam satu akun.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Double-entry',
        description:
            'Catat transaksi, transfer, jurnal, dan approval dengan benar.',
    },
    {
        icon: ChartColumn,
        title: 'Laporan lengkap',
        description:
            'Dashboard, cash flow, laba-rugi, neraca, dan export PDF/Excel.',
    },
    {
        icon: Users,
        title: 'Tim & role',
        description:
            'Undang anggota tim dengan role owner, member, atau viewer.',
    },
    {
        icon: Shield,
        title: 'Isolasi data',
        description:
            'Entity pribadi terlindung — hanya owner yang bisa akses.',
    },
];

export default function Welcome() {
    const { auth, name, saas } = usePage().props;
    const plan = saas?.currentPlan;

    return (
        <>
            <Head title="Akuntansi multi-entity untuk pribadi & bisnis" />
            <div className="bg-background min-h-screen">
                <header className="border-b">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <div className="font-semibold">{name}</div>
                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Masuk</Link>
                                    </Button>
                                    {saas?.registrationEnabled && (
                                        <Button asChild>
                                            <Link href={register()}>
                                                Daftar gratis
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-6 py-16">
                    <section className="mx-auto max-w-3xl text-center">
                        <span className="bg-primary/10 text-primary mb-4 inline-flex rounded-full px-3 py-1 text-sm font-medium">
                            {plan?.name ?? 'Gratis'} — semua fitur unlocked
                        </span>
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
                            Kelola keuangan pribadi & bisnis dalam satu
                            platform
                        </h1>
                        <p className="text-muted-foreground mt-4 text-lg">
                            Software akuntansi berbasis double-entry dengan
                            multi-entity, tim, invoice, laporan, dan approval
                            flow. Saat ini{' '}
                            <strong>100% gratis</strong> untuk semua pengguna.
                        </p>
                        {!auth.user && saas?.registrationEnabled && (
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Button size="lg" asChild>
                                    <Link href={register()}>
                                        Mulai gratis sekarang
                                    </Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <Link href={login()}>Sudah punya akun</Link>
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="mt-20 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((feature) => (
                            <Card key={feature.title}>
                                <CardHeader>
                                    <feature.icon className="text-primary mb-2 size-5" />
                                    <CardTitle className="text-base">
                                        {feature.title}
                                    </CardTitle>
                                    <CardDescription>
                                        {feature.description}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        ))}
                    </section>

                    <section className="mt-20 flex justify-center">
                        <Card className="w-full max-w-md border-primary/20">
                            <CardHeader className="text-center">
                                <CardTitle>Paket {plan?.name ?? 'Gratis'}</CardTitle>
                                <CardDescription>
                                    {plan?.description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-center">
                                    <span className="text-4xl font-bold">
                                        Rp0
                                    </span>
                                    <span className="text-muted-foreground">
                                        {' '}
                                        / bulan
                                    </span>
                                </div>
                                <ul className="space-y-2 text-sm">
                                    {[
                                        'Unlimited entity bisnis',
                                        'Chart of accounts otomatis',
                                        'Transaksi & jurnal',
                                        'Invoice & piutang',
                                        'Import CSV bank',
                                        'Laporan & export',
                                        'Tim multi-user',
                                    ].map((item) => (
                                        <li
                                            key={item}
                                            className="flex items-center gap-2"
                                        >
                                            <Check className="text-primary size-4 shrink-0" />
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                                {!auth.user && saas?.registrationEnabled && (
                                    <Button className="w-full" asChild>
                                        <Link href={register()}>
                                            Buat akun gratis
                                        </Link>
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </section>
                </main>
            </div>
        </>
    );
}
