<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Services\EntitySetupService;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed chart of accounts & default categories per entity.
     */
    public function run(): void
    {
        $setup = app(EntitySetupService::class);

        Entity::query()->each(function (Entity $entity) use ($setup): void {
            $setup->seedDefaults($entity);
        });

        $this->command?->info('Chart of accounts & categories seeder selesai.');
    }
}
