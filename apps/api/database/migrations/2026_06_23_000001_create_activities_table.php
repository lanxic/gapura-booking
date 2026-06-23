<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('category', ['indoor', 'outdoor']);
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('min_pax')->default(1);
            $table->unsignedSmallInteger('max_pax');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('meta')->nullable(); // include/exclude list, what_to_bring, cancellation_policy
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
        });

        Schema::create('activity_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('public_id')->nullable(); // cloudinary/r2/s3 identifier
            $table->string('provider')->nullable(); // cloudinary | cloudflare_r2 | aws_s3
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('activity_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->string('unit')->default('pax'); // pax | item | session
            $table->unsignedSmallInteger('max_qty')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday … 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('default_capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['activity_id', 'day_of_week']);
        });

        Schema::create('activity_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('activity_schedules')->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->unsignedBigInteger('price');
            $table->enum('status', ['available', 'full', 'blocked', 'cancelled'])->default('available');
            $table->timestamps();

            $table->unique(['activity_id', 'date', 'start_time']);
            $table->index(['activity_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_slots');
        Schema::dropIfExists('activity_schedules');
        Schema::dropIfExists('activity_addons');
        Schema::dropIfExists('activity_media');
        Schema::dropIfExists('activities');
    }
};
