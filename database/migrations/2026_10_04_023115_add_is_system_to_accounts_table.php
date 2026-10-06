<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        $systemNames = [
            'Kas',
            'Bank',
            'E-Wallet',
            'Piutang',
            'Hutang',
            'Hutang Kartu Kredit',
            'Hutang Usaha',
            'Modal',
            'Owner Draw',
            'Pendapatan',
            'Pendapatan Pelepasan Aset',
            'Beban',
            'Aset Tetap',
            'Akumulasi Penyusutan',
            'Beban Penyusutan',
            'Kerugian Pelepasan Aset',
        ];

        DB::table('accounts')
            ->whereIn('name', $systemNames)
            ->update(['is_system' => true]);

        $extraAccounts = [
            ['name' => 'Aset Tetap', 'type' => 'asset'],
            ['name' => 'Akumulasi Penyusutan', 'type' => 'asset'],
            ['name' => 'Beban Penyusutan', 'type' => 'expense'],
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
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
