<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;

class EntitySetupService
{
    /**
     * Seed chart of accounts & default categories for a new entity.
     */
    public function seedDefaults(Entity $entity): void
    {
        $this->seedAccounts($entity);
        $this->seedCategories($entity);
    }

    private function seedAccounts(Entity $entity): void
    {
        $accounts = [
            ['name' => 'Kas', 'type' => 'asset'],
            ['name' => 'Bank', 'type' => 'asset'],
            ['name' => 'E-Wallet', 'type' => 'asset'],
            ['name' => 'Piutang', 'type' => 'asset'],
            ['name' => 'Hutang', 'type' => 'liability'],
            ['name' => 'Hutang Kartu Kredit', 'type' => 'liability'],
            ['name' => 'Hutang Usaha', 'type' => 'liability'],
            ['name' => 'Modal', 'type' => 'equity'],
            ['name' => 'Owner Draw', 'type' => 'equity'],
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
