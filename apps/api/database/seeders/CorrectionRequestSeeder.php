<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $scanner    = DB::table('users')->where('email', 'scanner@amartha.test')->first();
        $kasir      = DB::table('users')->where('email', 'kasir@amartha.test')->first();
        $supervisor = DB::table('users')->where('email', 'supervisor@amartha.test')->first();
        $orderDp    = DB::table('orders')->where('booking_code', 'AMT-DP300001')->first();
        $orderPaid  = DB::table('orders')->where('booking_code', 'AMT-FULL0001')->first();

        if (! $scanner || ! $kasir || ! $supervisor || ! $orderDp || ! $orderPaid) {
            $this->command->warn('  CorrectionRequestSeeder dilewati — data prerequisite (users/orders) tidak lengkap.');
            return;
        }

        $ticket = DB::table('tickets')
            ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
            ->where('order_items.order_id', $orderPaid->id)
            ->select('tickets.*')
            ->first();

        DB::table('correction_requests')->insert([
            // PENDING — diajukan scanner, belum di-review
            [
                'requested_by'    => $scanner->id,
                'target_type'     => 'ticket',
                'target_id'       => $ticket?->id ?? 1,
                'reason'          => 'Tiket ter-scan ganda akibat sinyal terputus saat proses scan. Mohon reset status tiket menjadi unused.',
                'old_value'       => json_encode(['status' => 'used']),
                'requested_value' => json_encode(['status' => 'unused']),
                'status'          => 'pending',
                'reviewed_by'     => null,
                'reviewed_at'     => null,
                'review_notes'    => null,
                'created_at'      => now()->subMinutes(30),
            ],
            // APPROVED — diajukan kasir, sudah di-approve supervisor
            [
                'requested_by'    => $kasir->id,
                'target_type'     => 'payment',
                'target_id'       => 1,
                'reason'          => 'Tamu membayar lebih dari nominal sisa tagihan. Mohon koreksi amount payment dari 300.000 menjadi 259.000.',
                'old_value'       => json_encode(['amount' => 300000]),
                'requested_value' => json_encode(['amount' => 259000]),
                'status'          => 'approved',
                'reviewed_by'     => $supervisor->id,
                'reviewed_at'     => now()->subMinutes(10),
                'review_notes'    => 'Dikonfirmasi, koreksi dieksekusi. Kelebihan bayar Rp 41.000 dikembalikan ke tamu.',
                'created_at'      => now()->subHour(),
            ],
            // REJECTED — diajukan kasir, ditolak supervisor
            [
                'requested_by'    => $kasir->id,
                'target_type'     => 'order',
                'target_id'       => $orderDp->id,
                'reason'          => 'Minta batalkan order ini karena tamu salah pesan tanggal.',
                'old_value'       => json_encode(['status' => 'dp_paid']),
                'requested_value' => json_encode(['status' => 'cancelled']),
                'status'          => 'rejected',
                'reviewed_by'     => $supervisor->id,
                'reviewed_at'     => now()->subMinutes(45),
                'review_notes'    => 'Ditolak. Pembatalan order harus melalui admin dengan verifikasi refund policy. Silakan eskalasi ke admin.',
                'created_at'      => now()->subHours(2),
            ],
        ]);

        $this->command->info('  Correction requests seeded:');
        $this->command->table(
            ['Target', 'Status', 'Diajukan oleh'],
            [
                ['ticket #' . ($ticket?->id ?? 1), 'pending',  'scanner@amartha.test'],
                ['payment #1',                      'approved', 'kasir@amartha.test'],
                ['order #' . $orderDp->id,          'rejected', 'kasir@amartha.test'],
            ]
        );
    }
}
