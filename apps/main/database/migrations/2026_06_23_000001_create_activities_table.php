<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tabel produk lama dari domain PRD v3 sebelum membuat schema baru
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('product_addon');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::enableForeignKeyConstraints();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['aktivitas'])->default('aktivitas');
            $table->enum('category', ['indoor', 'outdoor']);
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('min_pax')->default(1);
            $table->unsignedSmallInteger('max_pax');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index('type');
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('public_id')->nullable();
            $table->string('provider')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->string('unit')->default('pax');
            $table->unsignedSmallInteger('max_qty')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('default_capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'day_of_week']);
        });

        Schema::create('product_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('product_schedules')->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->unsignedBigInteger('price');
            $table->enum('status', ['available', 'full', 'blocked', 'cancelled'])->default('available');
            $table->timestamps();

            $table->unique(['product_id', 'date', 'start_time']);
            $table->index(['product_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slots');
        Schema::dropIfExists('product_schedules');
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('products');
    }
};
