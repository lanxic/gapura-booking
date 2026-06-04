<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $vouchers = [
            [
                'code'         => 'WELCOME10',
                'type'         => 'percent',
                'value'        => 10,
                'min_purchase' => 100000,
                'quota'        => 100,
                'used_count'   => 0,
                'valid_from'   => now()->toDateString(),
                'valid_until'  => now()->addMonths(3)->toDateString(),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'code'         => 'HEMAT50K',
                'type'         => 'fixed',
                'value'        => 50000,
                'min_purchase' => 300000,
                'quota'        => 50,
                'used_count'   => 3,
                'valid_from'   => now()->toDateString(),
                'valid_until'  => now()->addMonths(1)->toDateString(),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'code'         => 'SAFARI20',
                'type'         => 'percent',
                'value'        => 20,
                'min_purchase' => 500000,
                'quota'        => 30,
                'used_count'   => 0,
                'valid_from'   => now()->toDateString(),
                'valid_until'  => now()->addDays(7)->toDateString(),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'code'         => 'EXPIRED99',
                'type'         => 'percent',
                'value'        => 99,
                'min_purchase' => 0,
                'quota'        => 10,
                'used_count'   => 0,
                'valid_from'   => now()->subMonths(2)->toDateString(),
                'valid_until'  => now()->subDays(1)->toDateString(),
                'is_active'    => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('vouchers')->insert($vouchers);

        $this->command->info('  Vouchers seeded:');
        $this->command->table(
            ['Code', 'Type', 'Value', 'Min Purchase', 'Status'],
            collect($vouchers)->map(fn($v) => [
                $v['code'],
                $v['type'],
                $v['type'] === 'percent' ? $v['value'] . '%' : 'Rp ' . number_format($v['value']),
                'Rp ' . number_format($v['min_purchase']),
                $v['is_active'] ? 'aktif' : 'expired',
            ])->toArray()
        );
    }
}
