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
        $customer = DB::table('users')->where('email', 'customer@amartha.test')->first();

        $booking1 = DB::table('bookings')->first();
        $booking2 = DB::table('bookings')->skip(1)->first();

        $logs = [
            [
                'user_id'      => $customer->id,
                'role'         => 'customer',
                'action'       => 'booking.create',
                'subject_type' => 'App\\Models\\Booking',
                'subject_id'   => $booking1?->id ?? 1,
                'old_value'    => null,
                'new_value'    => json_encode(['booking_code' => $booking1?->booking_code ?? 'ACT-20260626-00001', 'total_amount' => 370000]),
                'ip_address'   => '103.55.20.1',
                'user_agent'   => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17)',
                'created_at'   => now()->subHours(6),
            ],
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
                'action'       => 'activity.create',
                'subject_type' => 'App\\Models\\Activity',
                'subject_id'   => 1,
                'old_value'    => null,
                'new_value'    => json_encode(['name' => 'Safari Legend', 'is_active' => true]),
                'ip_address'   => '192.168.1.10',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHours(4),
            ],
            [
                'user_id'      => $admin->id,
                'role'         => 'admin',
                'action'       => 'invoice.paid',
                'subject_type' => 'App\\Models\\Invoice',
                'subject_id'   => $booking2?->invoice_id ?? 2,
                'old_value'    => json_encode(['status' => 'partial']),
                'new_value'    => json_encode(['status' => 'paid']),
                'ip_address'   => '192.168.1.10',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0)',
                'created_at'   => now()->subHour(),
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
                'action'       => 'booking.checkin',
                'subject_type' => 'App\\Models\\Booking',
                'subject_id'   => $booking1?->id ?? 1,
                'old_value'    => json_encode(['status' => 'confirmed']),
                'new_value'    => json_encode(['status' => 'attended']),
                'ip_address'   => '192.168.1.20',
                'user_agent'   => 'Mozilla/5.0 (Android 13; Mobile)',
                'created_at'   => now()->subMinutes(45),
            ],
        ];

        DB::table('activity_logs')->insert($logs);

        $this->command->info('  Activity logs seeded (' . count($logs) . ' entries).');
    }
}
