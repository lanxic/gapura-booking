<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL tidak mendukung ALTER ADD ENUM value langsung via Blueprint,
        // harus pakai raw statement. Semua nilai existing tetap ada.
        DB::statement("ALTER TABLE payments MODIFY COLUMN gateway ENUM('midtrans', 'doku', 'cash') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN gateway ENUM('midtrans', 'cash') NOT NULL");
    }
};
