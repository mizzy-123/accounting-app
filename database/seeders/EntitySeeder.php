<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        // Buat 2 entity default
        $personal = Entity::firstOrCreate(
            ['name' => 'Personal', 'type' => 'personal'],
        );

        $manifestasi = Entity::firstOrCreate(
            ['name' => 'Manifestasi', 'type' => 'business'],
        );

        // Assign owner ke kedua entity
        $owner = User::where('email', 'owner@manifestasi.com')->first();
        if (! $owner) {
            $owner = User::factory()->create([
                'name' => 'Owner',
                'email' => 'owner@manifestasi.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Attach owner ke Personal dan Manifestasi dengan role 'owner'
        $personal->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner'],
        ]);

        $manifestasi->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner'],
        ]);

        // Set active entity default di session tidak perlu di seeder
        // Ini akan di-handle oleh middleware saat pertama kali login

        $this->command->info('Entity seeder selesai:');
        $this->command->info("  - Entity Personal (id: {$personal->id})");
        $this->command->info("  - Entity Manifestasi (id: {$manifestasi->id})");
        $this->command->info("  - Owner: owner@manifestasi.com / password");
    }
}
