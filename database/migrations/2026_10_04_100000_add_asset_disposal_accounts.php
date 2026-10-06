<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $extraAccounts = [
            ['name' => 'Pendapatan Pelepasan Aset', 'type' => 'revenue'],
            ['name' => 'Kerugian Pelepasan Aset', 'type' => 'expense'],
        ];

        foreach (DB::table('entities')->pluck('id') as $entityId) {
            foreach ($extraAccounts as $account) {
                $exists = DB::table('accounts')
                    ->where('entity_id', $entityId)
                    ->where('name', $account['name'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('accounts')->insert([
                    'id' => (string) Str::uuid(),
                    'entity_id' => $entityId,
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'is_active' => true,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('name', ['Pendapatan Pelepasan Aset', 'Kerugian Pelepasan Aset'])
            ->where('is_system', true)
            ->delete();
    }
};
