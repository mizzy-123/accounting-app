<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankImportBatch;
use App\Models\BankImportRule;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ImportService
{
    public function __construct(private TransactionService $transactionService) {}

    /**
     * Parse CSV mutasi bank menjadi baris preview + auto-kategori.
     *
     * Format yang didukung (header case-insensitive):
     * - date, description, amount  (amount negatif = expense, positif = income)
     * - date, description, debit, credit
     * - tanggal, deskripsi, jumlah
     *
     * @return list<array{
     *     row: int,
     *     date: string,
     *     description: string,
     *     amount: string,
     *     type: 'income'|'expense',
     *     category_id: string|null,
     *     matched_keyword: string|null,
     *     include: bool,
     *     error: string|null
     * }>
     */
    public function preview(Entity $entity, UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);
        $rules = $this->loadRules($entity);

        return collect($rows)->map(function (array $row, int $index) use ($rules) {
            $matched = $this->matchRule($row['description'], $rules, $row['type']);

            return [
                'row' => $index + 1,
                'date' => $row['date'],
                'description' => $row['description'],
                'amount' => number_format($row['amount'], 2, '.', ''),
                'type' => $row['type'],
                'category_id' => $matched['category_id'],
                'matched_keyword' => $matched['keyword'],
                'include' => $row['error'] === null,
                'error' => $row['error'],
            ];
        })->values()->all();
    }

    /**
     * Konfirmasi preview → buat batch + transaksi draft via TransactionService.
     *
     * @param  list<array{
     *     date: string,
     *     description: string,
     *     amount: numeric-string|float|int,
     *     type: 'income'|'expense',
     *     category_id: string,
     *     include?: bool
     * }>  $rows
     */
    public function confirm(
        Entity $entity,
        User $user,
        string $accountId,
        string $fileName,
        array $rows,
    ): BankImportBatch {
        $account = Account::query()
            ->forEntity($entity)
            ->active()
            ->whereKey($accountId)
            ->where('type', 'asset')
            ->first();

        if (! $account) {
            throw new InvalidArgumentException('Akun bank/kas tidak valid.');
        }

        $selected = collect($rows)->filter(fn (array $row) => ($row['include'] ?? true) === true);

        return DB::transaction(function () use ($entity, $user, $account, $fileName, $rows, $selected) {
            $batch = BankImportBatch::create([
                'entity_id' => $entity->id,
                'file_name' => $fileName,
                'imported_by' => $user->id,
                'account_id' => $account->id,
                'total_rows' => count($rows),
                'imported_rows' => 0,
                'skipped_rows' => count($rows) - $selected->count(),
                'created_at' => now(),
            ]);

            $imported = 0;

            foreach ($selected as $row) {
                $payload = [
                    'date' => $row['date'],
                    'description' => $row['description'],
                    'amount' => $row['amount'],
                    'account_id' => $account->id,
                    'category_id' => $row['category_id'],
                ];

                $transaction = match ($row['type']) {
                    'income' => $this->transactionService->createIncome($entity, $user, $payload),
                    'expense' => $this->transactionService->createExpense($entity, $user, $payload),
                    default => throw new InvalidArgumentException("Tipe baris tidak dikenali: {$row['type']}"),
                };

                $transaction->update(['bank_import_batch_id' => $batch->id]);
                $imported++;
            }

            $batch->update(['imported_rows' => $imported]);

            return $batch->fresh(['account', 'importer']);
        });
    }

    /**
     * @return list<array{date: string, description: string, amount: float, type: 'income'|'expense', error: string|null}>
     */
    public function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('File CSV tidak bisa dibaca.');
        }

        $header = fgetcsv($handle);

        if ($header === false || $header === [null] || $header === []) {
            fclose($handle);
            throw new InvalidArgumentException('File CSV kosong atau tanpa header.');
        }

        // Strip BOM dari kolom pertama
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        $map = $this->mapHeaders($header);

        $rows = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyRow($data)) {
                continue;
            }

            try {
                $rows[] = $this->normalizeRow($data, $map);
            } catch (InvalidArgumentException $e) {
                $rows[] = [
                    'date' => '',
                    'description' => implode(' ', array_filter($data)),
                    'amount' => 0.0,
                    'type' => 'expense',
                    'error' => "Baris {$line}: {$e->getMessage()}",
                ];
            }
        }

        fclose($handle);

        if ($rows === []) {
            throw new InvalidArgumentException('Tidak ada baris data di CSV.');
        }

        return $rows;
    }

    /**
     * @param  list<string|null>  $header
     * @return array{date: int, description: int, amount: int|null, debit: int|null, credit: int|null}
     */
    private function mapHeaders(array $header): array
    {
        $normalized = collect($header)->map(
            fn ($col) => strtolower(trim((string) $col)),
        );

        $find = function (array $aliases) use ($normalized): ?int {
            foreach ($aliases as $alias) {
                $index = $normalized->search($alias);
                if ($index !== false) {
                    return (int) $index;
                }
            }

            return null;
        };

        $date = $find(['date', 'tanggal', 'tgl', 'transaction_date', 'trx_date']);
        $description = $find(['description', 'deskripsi', 'keterangan', 'narration', 'memo', 'remark']);
        $amount = $find(['amount', 'jumlah', 'nominal', 'value']);
        $debit = $find(['debit', 'db', 'keluar', 'withdrawal']);
        $credit = $find(['credit', 'kredit', 'cr', 'masuk', 'deposit']);

        if ($date === null || $description === null) {
            throw new InvalidArgumentException(
                'Header CSV wajib punya kolom date/tanggal dan description/deskripsi.',
            );
        }

        if ($amount === null && ($debit === null || $credit === null)) {
            throw new InvalidArgumentException(
                'Header CSV wajib punya kolom amount/jumlah, atau pasangan debit+credit.',
            );
        }

        return compact('date', 'description', 'amount', 'debit', 'credit');
    }

    /**
     * @param  list<string|null>  $data
     * @param  array{date: int, description: int, amount: int|null, debit: int|null, credit: int|null}  $map
     * @return array{date: string, description: string, amount: float, type: 'income'|'expense', error: null}
     */
    private function normalizeRow(array $data, array $map): array
    {
        $dateRaw = trim((string) ($data[$map['date']] ?? ''));
        $description = trim((string) ($data[$map['description']] ?? ''));

        if ($dateRaw === '' || $description === '') {
            throw new InvalidArgumentException('Tanggal dan deskripsi wajib diisi.');
        }

        $date = $this->parseDate($dateRaw);

        if ($map['amount'] !== null) {
            $amountRaw = $this->parseNumber((string) ($data[$map['amount']] ?? '0'));
            if ($amountRaw == 0.0) {
                throw new InvalidArgumentException('Jumlah tidak boleh nol.');
            }
            $type = $amountRaw < 0 ? 'expense' : 'income';
            $amount = abs($amountRaw);
        } else {
            $debit = abs($this->parseNumber((string) ($data[$map['debit']] ?? '0')));
            $credit = abs($this->parseNumber((string) ($data[$map['credit']] ?? '0')));

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('Debit dan kredit tidak boleh keduanya terisi.');
            }
            if ($debit <= 0 && $credit <= 0) {
                throw new InvalidArgumentException('Debit atau kredit wajib terisi.');
            }

            $type = $debit > 0 ? 'expense' : 'income';
            $amount = $debit > 0 ? $debit : $credit;
        }

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'error' => null,
        ];
    }

    private function parseDate(string $value): string
    {
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        throw new InvalidArgumentException("Format tanggal tidak dikenali: {$value}");
    }

    private function parseNumber(string $value): float
    {
        $value = trim($value);
        $value = str_replace(['Rp', 'rp', ' '], '', $value);

        // 1.250.000,50 (ID) atau 1,250,000.50 (US)
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value)) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    /**
     * @param  list<string|null>  $data
     */
    private function isEmptyRow(array $data): bool
    {
        return collect($data)->every(fn ($cell) => trim((string) $cell) === '');
    }

    /**
     * @return list<array{keyword: string, category_id: string, category_type: string}>
     */
    private function loadRules(Entity $entity): array
    {
        return BankImportRule::query()
            ->forEntity($entity)
            ->with('category:id,type')
            ->get()
            ->map(fn (BankImportRule $rule) => [
                'keyword' => $rule->keyword,
                'category_id' => $rule->category_id,
                'category_type' => $rule->category?->type ?? 'expense',
            ])
            // Keyword lebih panjang diprioritaskan
            ->sortByDesc(fn (array $rule) => mb_strlen($rule['keyword']))
            ->values()
            ->all();
    }

    /**
     * @param  list<array{keyword: string, category_id: string, category_type: string}>  $rules
     * @return array{category_id: string|null, keyword: string|null}
     */
    private function matchRule(string $description, array $rules, string $type): array
    {
        $haystack = mb_strtolower($description);

        foreach ($rules as $rule) {
            if ($rule['category_type'] !== $type) {
                continue;
            }

            if (str_contains($haystack, mb_strtolower($rule['keyword']))) {
                return [
                    'category_id' => $rule['category_id'],
                    'keyword' => $rule['keyword'],
                ];
            }
        }

        return ['category_id' => null, 'keyword' => null];
    }
}
