<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name'           => 'Adventure Hub',
                'slug'           => 'adventure',
                'invoice_prefix' => 'ADV',
                'is_active'      => true,
                'settings'       => ['primary_color' => '#3b82f6'],
            ],
        ];

        foreach ($tenants as $data) {
            Tenant::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('  Tenants seeded: ' . implode(', ', array_column($tenants, 'slug')));
    }
}
