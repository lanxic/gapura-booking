<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('discount_type', ['flat', 'percent']);
            $table->unsignedBigInteger('discount_value'); // IDR jika flat, persentase jika percent
            $table->enum('badge', ['early_bird', 'flash_sale', 'weekend_special', 'group_discount', 'bundle_package'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'start_date', 'end_date']);
        });

        // Relasi many-to-many antara offer dan activity
        Schema::create('offer_activities', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->primary(['offer_id', 'activity_id']);
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('discount_type', ['flat', 'percent']);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('min_amount')->default(0);
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_single_use')->default(false); // true = 1x per customer
            $table->timestamp('expired_at');
            $table->timestamps();

            $table->index(['code', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('offer_activities');
        Schema::dropIfExists('offers');
    }
};
