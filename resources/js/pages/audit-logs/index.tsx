import { Head } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type AuditLogRow = {
    id: string;
    action: string;
    model_type: string;
    model_id: string;
    changes: Record<string, unknown> | null;
    created_at: string | null;
    user: { id: string; name: string; email: string } | null;
};

type Props = {
    logs: {
        data: AuditLogRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

const actionLabels: Record<string, string> = {
    created: 'Dibuat',
    updated: 'Diubah',
    deleted: 'Dihapus',
    approved: 'Disetujui',
};

export default function AuditLogsIndex({ logs }: Props) {
    return (
        <>
            <Head title="Audit Log" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Audit Log
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Jejak siapa mengubah transaksi apa, kapan — read-only.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ScrollText className="size-4" />
                            Riwayat Perubahan
                        </CardTitle>
                        <CardDescription>
                            Mencakup transaksi dan baris jurnal
                            (transaction_entries).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {logs.data.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada audit log untuk entity ini.
                            </p>
                        ) : (
                            logs.data.map((log) => (
                                <div
                                    key={log.id}
                                    className="rounded-lg border p-3 text-sm"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p className="font-medium">
                                                {actionLabels[log.action] ??
                                                    log.action}{' '}
                                                · {log.model_type}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {log.user?.name ?? 'Sistem'} ·{' '}
                                                {log.created_at ?? '—'} · ID{' '}
                                                <span className="font-mono">
                                                    {log.model_id.slice(0, 8)}…
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    {log.changes && (
                                        <pre className="bg-muted/50 mt-2 max-h-40 overflow-auto rounded-md p-2 text-xs whitespace-pre-wrap">
                                            {JSON.stringify(
                                                log.changes,
                                                null,
                                                2,
                                            )}
                                        </pre>
                                    )}
                                </div>
                            ))
                        )}

                        {logs.links.length > 3 && (
                            <div className="flex flex-wrap gap-2 pt-2">
                                {logs.links.map((link, index) => (
                                    <a
                                        key={`${link.label}-${index}`}
                                        href={link.url ?? undefined}
                                        className={`rounded-md border px-2 py-1 text-xs ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-muted-foreground'
                                        } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [{ title: 'Audit Log', href: '/audit-logs' }],
};
