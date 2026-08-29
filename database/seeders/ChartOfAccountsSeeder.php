<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed chart of accounts & default categories per entity.
     *
     * Catatan:
     * - Akun type `asset` / `liability` bisa dipilih di form transaksi biasa.
     * - `Pendapatan` & `Beban` dipakai otomatis oleh TransactionService (jangan diganti namanya).
     * - `Modal` & `Owner Draw` dipakai transfer antar entity + jurnal manual.
     */
    public function run(): void
    {
        Entity::query()->each(function (Entity $entity): void {
            $this->seedAccounts($entity);
            $this->seedCategories($entity);
        });

        $this->command?->info('Chart of accounts & categories seeder selesai.');
    }

    private function seedAccounts(Entity $entity): void
    {
        $accounts = [
            // Dompet / aset — muncul di form income, expense, transfer
            ['name' => 'Kas', 'type' => 'asset'],
            ['name' => 'Bank', 'type' => 'asset'],
            ['name' => 'E-Wallet', 'type' => 'asset'],
            ['name' => 'Piutang', 'type' => 'asset'],

            // Kewajiban — bisa dipilih saat expense (bayar pakai hutang) / transfer pelunasan
            ['name' => 'Hutang', 'type' => 'liability'],
            ['name' => 'Hutang Kartu Kredit', 'type' => 'liability'],
            ['name' => 'Hutang Usaha', 'type' => 'liability'],

            // Ekuitas — jurnal manual & transfer antar entity
            ['name' => 'Modal', 'type' => 'equity'],
            ['name' => 'Owner Draw', 'type' => 'equity'],

            // Lawan otomatis double-entry (jangan rename)
            ['name' => 'Pendapatan', 'type' => 'revenue'],
            ['name' => 'Beban', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'entity_id' => $entity->id,
                    'name' => $account['name'],
                ],
                [
                    'type' => $account['type'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedCategories(Entity $entity): void
    {
        $categories = [
            ['name' => 'Gaji/Fee', 'type' => 'income'],
            ['name' => 'Lainnya', 'type' => 'income'],
            ['name' => 'Transport', 'type' => 'expense'],
            ['name' => 'Makan', 'type' => 'expense'],
            ['name' => 'Utilities', 'type' => 'expense'],
            ['name' => 'Operasional', 'type' => 'expense'],
            ['name' => 'Belanja', 'type' => 'expense'],
            ['name' => 'Hiburan', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                [
                    'entity_id' => $entity->id,
                    'name' => $category['name'],
                ],
                ['type' => $category['type']],
            );
        }
    }
}
