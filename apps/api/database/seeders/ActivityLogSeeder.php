<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = DB::table('users')->where('email', 'admin@amartha.test')->first();
        $scanner  = DB::table('users')->where('email', 'scanner@amartha.test')->first();
        $kasir    = DB::table('users')->where('email', 'kasir@amartha.test')->first();
        $customer = DB::table('users')->where('email', 'customer@amartha.test')->first();

        $order1  = DB::table('orders')->where('booking_code', 'AMT-FULL0001')->first();
        $order2  = DB::table('orders')->where('booking_code', 'AMT-DP300001')->first();
        $ticket  = DB::table('tickets')
            ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
            ->where('order_items.order_id', $order1->id)
            ->select('tickets.*')
            ->first();

        $logs = [
            [
                'user_id'      => $admin->id,
                'role'         => 'admin',
                'action'       => 'user.login',
                'subject_type' => 'App\\Models\\User',
                'subject_id'   => $admin->id,
                'old_value'    => null,
                'new_value'    => json_encode(['email' => $admin->email]),
                'ip_address'   => '192.168.1.10',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHours(5),
            ],
            [
                'user_id'      => $admin->id,
                'role'         => 'admin',
                'action'       => 'product.create',
                'subject_type' => 'App\\Models\\Product',
                'subject_id'   => 1,
                'old_value'    => null,
                'new_value'    => json_encode(['name' => 'Safari Legend', 'is_active' => true]),
                'ip_address'   => '192.168.1.10',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHours(4),
            ],
            [
                'user_id'      => $scanner->id,
                'role'         => 'scanner',
                'action'       => 'user.login',
                'subject_type' => 'App\\Models\\User',
                'subject_id'   => $scanner->id,
                'old_value'    => null,
                'new_value'    => json_encode(['email' => $scanner->email]),
                'ip_address'   => '192.168.1.20',
                'user_agent'   => 'Mozilla/5.0 (Android 13; Mobile)',
                'created_at'   => now()->subHours(2),
            ],
            [
                'user_id'      => $scanner->id,
                'role'         => 'scanner',
                'action'       => 'ticket.scan',
                'subject_type' => 'App\\Models\\Ticket',
                'subject_id'   => $ticket?->id ?? 1,
                'old_value'    => json_encode(['status' => 'unused']),
                'new_value'    => json_encode(['status' => 'used']),
                'ip_address'   => '192.168.1.20',
                'user_agent'   => 'Mozilla/5.0 (Android 13; Mobile)',
                'created_at'   => now()->subMinutes(45),
            ],
            [
                'user_id'      => $kasir->id,
                'role'         => 'kasir',
                'action'       => 'user.login',
                'subject_type' => 'App\\Models\\User',
                'subject_id'   => $kasir->id,
                'old_value'    => null,
                'new_value'    => json_encode(['email' => $kasir->email]),
                'ip_address'   => '192.168.1.30',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHours(3),
            ],
            [
                'user_id'      => $kasir->id,
                'role'         => 'kasir',
                'action'       => 'payment.collect',
                'subject_type' => 'App\\Models\\Order',
                'subject_id'   => $order2->id,
                'old_value'    => json_encode(['remaining_amount' => 259000, 'status' => 'dp_paid']),
                'new_value'    => json_encode(['remaining_amount' => 0, 'status' => 'paid']),
                'ip_address'   => '192.168.1.30',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHour(),
            ],
            [
                'user_id'      => $customer->id,
                'role'         => 'customer',
                'action'       => 'order.create',
                'subject_type' => 'App\\Models\\Order',
                'subject_id'   => $order1->id,
                'old_value'    => null,
                'new_value'    => json_encode(['booking_code' => 'AMT-FULL0001', 'total' => 370000]),
                'ip_address'   => '103.55.20.1',
                'user_agent'   => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17)',
                'created_at'   => now()->subHours(6),
            ],
        ];

        DB::table('activity_logs')->insert($logs);

        $this->command->info('  Activity logs seeded (' . count($logs) . ' entries).');
    }
}
