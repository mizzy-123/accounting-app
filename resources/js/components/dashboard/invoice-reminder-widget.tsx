import { Link } from '@inertiajs/react';
import { AlertTriangle, Clock } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { InvoiceReminder } from '@/types';

type Props = {
    reminders: InvoiceReminder[];
};

function formatCurrency(amount: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}

export function InvoiceReminderWidget({ reminders }: Props) {
    if (reminders.length === 0) {
        return null;
    }

    return (
        <Card className="border-amber-500/30 bg-amber-500/5">
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-base">
                    <AlertTriangle className="size-4 text-amber-600 dark:text-amber-400" />
                    Reminder Piutang
                </CardTitle>
                <CardDescription>
                    Invoice mendekati atau lewat jatuh tempo
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
                {reminders.map((item) => (
                    <Link
                        key={`${item.severity}-${item.id}`}
                        href={`/invoices/${item.id}`}
                        className="bg-background hover:bg-muted/50 flex items-center justify-between gap-3 rounded-lg border px-3 py-2 transition-colors"
                    >
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="text-sm font-medium">
                                    {item.invoice_number}
                                </p>
                                <span
                                    className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${
                                        item.severity === 'overdue'
                                            ? 'bg-red-500/10 text-red-700 dark:text-red-400'
                                            : 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                                    }`}
                                >
                                    {item.severity === 'overdue' ? (
                                        <AlertTriangle className="size-3" />
                                    ) : (
                                        <Clock className="size-3" />
                                    )}
                                    {item.severity === 'overdue'
                                        ? `Lewat ${Math.abs(item.days_until_due ?? 0)} hari`
                                        : `${item.days_until_due ?? 0} hari lagi`}
                                </span>
                            </div>
                            <p className="text-muted-foreground truncate text-xs">
                                {item.client_name ?? 'Tanpa client'}
                                {item.due_date && ` · Due ${item.due_date}`}
                            </p>
                        </div>
                        <span className="shrink-0 text-sm font-medium tabular-nums">
                            {formatCurrency(item.total)}
                        </span>
                    </Link>
                ))}
            </CardContent>
        </Card>
    );
}
