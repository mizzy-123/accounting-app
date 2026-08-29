import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Tags, Trash2 } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type CategoryOption = {
    id: string;
    name: string;
    type: 'income' | 'expense';
};

type RuleRow = {
    id: string;
    keyword: string;
    category: CategoryOption;
};

type Props = {
    rules: RuleRow[];
    categories: CategoryOption[];
    canManage: boolean;
};

function CreateRuleDialog({
    categories,
}: {
    categories: CategoryOption[];
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        keyword: '',
        category_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/import/rules', {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" className="gap-2">
                    <Plus className="size-4" />
                    Tambah Rule
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Rule Auto-Kategori</DialogTitle>
                    <DialogDescription>
                        Jika deskripsi mutasi mengandung keyword, kategori
                        otomatis dipilih saat preview import.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>Keyword</Label>
                        <Input
                            value={data.keyword}
                            onChange={(e) =>
                                setData('keyword', e.target.value)
                            }
                            placeholder="Contoh: GOJEK"
                            required
                        />
                        {errors.keyword && (
                            <p className="text-destructive text-sm">
                                {errors.keyword}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label>Kategori</Label>
                        <Select
                            value={data.category_id}
                            onValueChange={(v) => setData('category_id', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem
                                        key={category.id}
                                        value={category.id}
                                    >
                                        {category.name} ({category.type})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category_id && (
                            <p className="text-destructive text-sm">
                                {errors.category_id}
                            </p>
                        )}
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ImportRules({ rules, categories, canManage }: Props) {
    function destroy(id: string, keyword: string) {
        if (!confirm(`Hapus rule "${keyword}"?`)) return;
        router.delete(`/import/rules/${id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Rule Kategori" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Rule Auto-Kategori
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Keyword di deskripsi mutasi → kategori otomatis saat
                            import CSV.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/import">Ke Import CSV</Link>
                        </Button>
                        {canManage && (
                            <CreateRuleDialog categories={categories} />
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Tags className="size-4" />
                            Daftar Rule ({rules.length})
                        </CardTitle>
                        <CardDescription>
                            Keyword lebih panjang diprioritaskan saat matching.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {rules.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Tags className="text-muted-foreground/40 mb-3 size-10" />
                                <p className="text-muted-foreground text-sm">
                                    Belum ada rule. Tambahkan misalnya keyword
                                    &quot;GOJEK&quot; → kategori Transport.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {rules.map((rule) => (
                                    <div
                                        key={rule.id}
                                        className="flex items-center justify-between gap-3 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium">
                                                {rule.keyword}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                → {rule.category.name} (
                                                {rule.category.type})
                                            </p>
                                        </div>
                                        {canManage && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    destroy(
                                                        rule.id,
                                                        rule.keyword,
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ImportRules.layout = {
    breadcrumbs: [
        { title: 'Import CSV', href: '/import' },
        { title: 'Rule Kategori', href: '/import/rules' },
    ],
};
