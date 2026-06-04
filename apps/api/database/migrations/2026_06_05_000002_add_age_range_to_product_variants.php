<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedSmallInteger('adult_min_age')->default(3)->after('description');
            $table->unsignedSmallInteger('adult_max_age')->default(99)->after('adult_min_age');
            $table->unsignedSmallInteger('child_min_age')->default(3)->after('adult_max_age');
            $table->unsignedSmallInteger('child_max_age')->default(12)->after('child_min_age');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['adult_min_age', 'adult_max_age', 'child_min_age', 'child_max_age']);
        });
    }
};
