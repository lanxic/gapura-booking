<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cloudinary_image_id')->nullable();
            $table->string('cloudinary_image_url')->nullable();
            $table->string('cloudinary_thumbnail_id')->nullable();
            $table->string('cloudinary_thumbnail_url')->nullable();
            $table->json('cloudinary_gallery_ids')->nullable();
            $table->json('cloudinary_gallery_urls')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('price_adult');
            $table->unsignedInteger('price_child')->default(0);
            $table->unsignedSmallInteger('min_qty')->default(1);
            $table->unsignedSmallInteger('max_qty')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('max_qty')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'addon_id']);
        });

        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('time_slot')->nullable();
            $table->unsignedSmallInteger('total_quota')->default(0);
            $table->unsignedSmallInteger('booked_qty')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->index(['product_id', 'date']);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['weekday', 'weekend', 'holiday']);
            $table->decimal('multiplier', 4, 2)->default(1.00);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('product_addon');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
