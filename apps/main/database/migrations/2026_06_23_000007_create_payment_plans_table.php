<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Tipe pembayaran yang bisa dikonfigurasi admin (PRD Section 4.4.2)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // FULL | DP30 | DP50 | DP70
            $table->string('label', 100);
            $table->unsignedTinyInteger('percentage'); // 100 | 30 | 50 | 70
            $table->unsignedBigInteger('min_amount')->default(0); // batas minimum total booking
            $table->unsignedSmallInteger('deadline_hours')->default(18); // jam pelunasan H-1 18:00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('payment_plans')->insert([
            [
                'code'          => 'FULL',
                'label'         => 'Bayar Penuh',
                'percentage'    => 100,
                'min_amount'    => 0,
                'deadline_hours'=> 0,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'DP30',
                'label'         => 'DP 30% — Sisa Pelunasan H-1',
                'percentage'    => 30,
                'min_amount'    => 500000, // IDR 500.000 minimum total
                'deadline_hours'=> 18,
                'is_active'     => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'DP50',
                'label'         => 'DP 50% — Sisa Pelunasan H-1',
                'percentage'    => 50,
                'min_amount'    => 0,
                'deadline_hours'=> 18,
                'is_active'     => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'DP70',
                'label'         => 'DP 70% — Sisa Pelunasan H-1',
                'percentage'    => 70,
                'min_amount'    => 0,
                'deadline_hours'=> 18,
                'is_active'     => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
