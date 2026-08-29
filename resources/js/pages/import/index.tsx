import { Head, Link, useForm } from '@inertiajs/react';
import { FileUp, Tags, Upload } from 'lucide-react';
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

type AccountOption = { id: string; name: string };

type BatchRow = {
    id: string;
    file_name: string;
    total_rows: number;
    imported_rows: number;
    skipped_rows: number;
    created_at: string | null;
    importer: string | null;
    account: string | null;
};

type Props = {
    accounts: AccountOption[];
    batches: BatchRow[];
    rulesCount: number;
};

export default function ImportIndex({ accounts, batches, rulesCount }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        account_id: string;
        file: File | null;
    }>({
        account_id: accounts[0]?.id ?? '',
        file: null,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/import/preview', { forceFormData: true });
    }

    return (
        <>
            <Head title="Import CSV" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Import Mutasi Bank
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Upload CSV, preview kategori otomatis, lalu simpan
                            sebagai transaksi draft.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" className="gap-2" asChild>
                        <Link href="/import/rules">
                            <Tags className="size-4" />
                            Kelola Rule ({rulesCount})
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Upload className="size-4" />
                                Upload CSV
                            </CardTitle>
                            <CardDescription>
                                Header yang didukung: date/tanggal,
                                description/deskripsi, amount/jumlah — atau
                                debit+credit.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Akun Bank / Kas</Label>
                                    <Select
                                        value={data.account_id}
                                        onValueChange={(v) =>
                                            setData('account_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih akun" />
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
                                    {errors.account_id && (
                                        <p className="text-destructive text-sm">
                                            {errors.account_id}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="csv-file">File CSV</Label>
                                    <Input
                                        id="csv-file"
                                        type="file"
                                        accept=".csv,text/csv"
                                        onChange={(e) =>
                                            setData(
                                                'file',
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        required
                                    />
                                    {errors.file && (
                                        <p className="text-destructive text-sm">
                                            {errors.file}
                                        </p>
                                    )}
                                </div>

                                <div className="bg-muted/40 rounded-lg border p-3 text-xs leading-relaxed">
                                    <p className="font-medium">Contoh format:</p>
                                    <pre className="mt-1 overflow-x-auto whitespace-pre">
                                        {`date,description,amount
2026-08-01,GOJEK RIDE JAKARTA,-25000
2026-08-02,TRANSFER GAJI,5000000`}
                                    </pre>
                                    <p className="text-muted-foreground mt-2">
                                        Amount negatif = pengeluaran, positif =
                                        pemasukan.
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing || !data.file}
                                    className="gap-2"
                                >
                                    <FileUp className="size-4" />
                                    {processing
                                        ? 'Memproses...'
                                        : 'Preview Import'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Riwayat Import
                            </CardTitle>
                            <CardDescription>
                                20 batch terakhir untuk entity aktif
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {batches.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Belum ada history import.
                                </p>
                            ) : (
                                <div className="divide-y">
                                    {batches.map((batch) => (
                                        <div
                                            key={batch.id}
                                            className="flex flex-col gap-1 py-3"
                                        >
                                            <p className="text-sm font-medium">
                                                {batch.file_name}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {batch.imported_rows}/
                                                {batch.total_rows} diimpor
                                                {batch.skipped_rows > 0 &&
                                                    ` · ${batch.skipped_rows} dilewati`}
                                                {batch.account &&
                                                    ` · ${batch.account}`}
                                                {batch.importer &&
                                                    ` · ${batch.importer}`}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

ImportIndex.layout = {
    breadcrumbs: [{ title: 'Import CSV', href: '/import' }],
};
