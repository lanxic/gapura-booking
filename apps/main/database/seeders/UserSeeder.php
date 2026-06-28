<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenantAdventure = Tenant::where('slug', 'adventure')->first();
        $tenantWellness  = Tenant::where('slug', 'wellness')->first();

        $globalUsers = [
            ['name' => 'Super Admin',  'email' => 'superadmin@amartha.test', 'role' => UserRole::SuperAdmin,  'tenant_id' => null],
            ['name' => 'Budi Santoso', 'email' => 'customer@amartha.test',   'role' => UserRole::Customer,    'tenant_id' => null],
            ['name' => 'Siti Rahayu', 'email' => 'customer2@amartha.test',  'role' => UserRole::Customer,    'tenant_id' => null],
        ];

        $tenantUsers = [];

        if ($tenantAdventure) {
            $tenantUsers[] = ['name' => 'Admin Adventure', 'email' => 'admin@adventure.test', 'role' => UserRole::TenantAdmin, 'tenant_id' => $tenantAdventure->id];
            $tenantUsers[] = ['name' => 'Scanner Adventure', 'email' => 'scanner@adventure.test', 'role' => UserRole::Scanner, 'tenant_id' => $tenantAdventure->id];
        }

        if ($tenantWellness) {
            $tenantUsers[] = ['name' => 'Admin Wellness', 'email' => 'admin@wellness.test', 'role' => UserRole::TenantAdmin, 'tenant_id' => $tenantWellness->id];
        }

        $allUsers = array_merge($globalUsers, $tenantUsers);

        foreach ($allUsers as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'role'              => $data['role'],
                    'tenant_id'         => $data['tenant_id'],
                    'is_active'         => true,
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('  Users seeded (password: password)');
        $this->command->table(
            ['Role', 'Email', 'Tenant'],
            collect($allUsers)->map(fn ($u) => [
                $u['role']->value,
                $u['email'],
                $u['tenant_id'] ? ('ID:' . $u['tenant_id']) : 'global',
            ])->toArray()
        );
    }
}
