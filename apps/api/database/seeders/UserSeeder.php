<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin',    'email' => 'superadmin@amartha.test', 'role' => UserRole::SuperAdmin],
            ['name' => 'Admin Utama',    'email' => 'admin@amartha.test',      'role' => UserRole::Admin],
            ['name' => 'Staff Scanner',  'email' => 'scanner@amartha.test',    'role' => UserRole::Scanner],
            ['name' => 'Budi Santoso',   'email' => 'customer@amartha.test',   'role' => UserRole::Customer],
            ['name' => 'Siti Rahayu',    'email' => 'customer2@amartha.test',  'role' => UserRole::Customer],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'role'              => $data['role'],
                    'is_active'         => true,
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('  Users seeded (password: password)');
        $this->command->table(
            ['Role', 'Email'],
            collect($users)->map(fn ($u) => [$u['role']->value, $u['email']])->toArray()
        );
    }
}
