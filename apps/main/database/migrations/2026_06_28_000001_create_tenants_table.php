<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // subdomain: {slug}.domain.com
            $table->string('domain')->nullable()->unique(); // custom domain (opsional)
            $table->string('logo_url')->nullable();
            $table->string('invoice_prefix', 10)->unique(); // e.g. TNA, TNB
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // storefront config, branding, dll
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
