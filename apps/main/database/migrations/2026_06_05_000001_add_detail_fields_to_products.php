<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('location')->nullable()->after('description');
            $table->string('opening_hours', 100)->nullable()->after('location');
            $table->text('meeting_point')->nullable()->after('opening_hours');
            $table->boolean('instant_confirmation')->default(true)->after('meeting_point');
            $table->json('highlights')->nullable()->after('instant_confirmation');
            $table->text('usage_instructions')->nullable()->after('highlights');
            $table->text('cancellation_policy')->nullable()->after('usage_instructions');
            $table->text('terms_conditions')->nullable()->after('cancellation_policy');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->text('description')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'location', 'opening_hours', 'meeting_point', 'instant_confirmation',
                'highlights', 'usage_instructions', 'cancellation_policy', 'terms_conditions',
            ]);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
