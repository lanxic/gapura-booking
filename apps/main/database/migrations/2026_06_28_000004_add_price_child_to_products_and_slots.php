<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $productCols = array_column(\DB::select('DESCRIBE products'), 'Field');
        $slotCols    = array_column(\DB::select('DESCRIBE product_slots'), 'Field');

        Schema::table('products', function (Blueprint $table) use ($productCols) {
            if (!in_array('price_adult', $productCols)) {
                $table->renameColumn('base_price', 'price_adult');
            }
            if (!in_array('price_child', $productCols)) {
                $table->unsignedBigInteger('price_child')->nullable()->after('price_adult');
            }
        });

        Schema::table('product_slots', function (Blueprint $table) use ($slotCols) {
            if (!in_array('price_adult', $slotCols)) {
                $table->renameColumn('price', 'price_adult');
            }
        });

        Schema::table('product_slots', function (Blueprint $table) use ($slotCols) {
            if (!in_array('price_child', $slotCols)) {
                $table->unsignedBigInteger('price_child')->nullable()->after('price_adult');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_child');
        });

        Schema::table('product_slots', function (Blueprint $table) {
            $table->dropColumn('price_child');
            $table->renameColumn('price_adult', 'price');
        });
    }
};
