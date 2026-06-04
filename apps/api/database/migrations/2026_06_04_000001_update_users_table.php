<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin', 'supervisor', 'kasir', 'scanner', 'customer'])
                ->default('customer')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
            $table->string('cloudinary_avatar_id')->nullable()->after('created_by');
            $table->string('cloudinary_avatar_url')->nullable()->after('cloudinary_avatar_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'created_by', 'cloudinary_avatar_id', 'cloudinary_avatar_url']);
        });
    }
};
