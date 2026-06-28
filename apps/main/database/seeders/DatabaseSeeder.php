<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,   // harus pertama — UserSeeder dan ProductSeeder butuh tenant_id
            UserSeeder::class,
            ProductSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✓ Seeding selesai. Akun testing (password: password):');
        $this->command->table(
            ['Role', 'Email', 'Akses'],
            [
                ['super_admin',  'superadmin@amartha.test',  'localhost/admin'],
                ['tenant_admin', 'admin@adventure.test',     'adventure.localhost/admin'],
                ['tenant_admin', 'admin@wellness.test',      'wellness.localhost/admin'],
                ['scanner',      'scanner@adventure.test',   'adventure.localhost/admin'],
                ['customer',     'customer@amartha.test',    'Storefront tenant manapun'],
                ['customer',     'customer2@amartha.test',   'Storefront tenant manapun'],
            ]
        );
    }
}
