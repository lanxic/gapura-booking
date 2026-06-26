<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hapus role lama (supervisor, kasir) yang sudah tidak digunakan di PRD v4.0.
 * Role yang tersisa: super_admin, admin, scanner, customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ubah user yang masih punya role lama ke admin
        DB::table('users')
            ->whereIn('role', ['supervisor', 'kasir'])
            ->update(['role' => 'admin']);

        // Ubah kolom enum
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','scanner','customer') NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','supervisor','kasir','scanner','customer') NOT NULL DEFAULT 'customer'");
    }
};
