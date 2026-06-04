<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            VoucherSeeder::class,
            OrderSeeder::class,
            ActivityLogSeeder::class,
            CorrectionRequestSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✓ Seeding selesai. Akun testing:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['super_admin', 'superadmin@amartha.test', 'password'],
                ['admin',       'admin@amartha.test',      'password'],
                ['supervisor',  'supervisor@amartha.test', 'password'],
                ['kasir',       'kasir@amartha.test',      'password'],
                ['scanner',     'scanner@amartha.test',    'password'],
                ['customer',    'customer@amartha.test',   'password'],
                ['customer',    'customer2@amartha.test',  'password'],
            ]
        );
    }
}
