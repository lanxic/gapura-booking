<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','tenant_admin','scanner','customer') NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'tenant_admin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin','scanner','customer') NOT NULL DEFAULT 'customer'");
    }
};
