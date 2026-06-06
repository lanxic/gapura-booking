<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows before adding the constraint — keep the one with
        // the highest booked_qty (or the latest id if equal) per (product_id, date, time_slot).
        DB::statement("
            DELETE a FROM availability_slots a
            INNER JOIN availability_slots b
                ON  a.product_id = b.product_id
                AND a.date       = b.date
                AND (a.time_slot = b.time_slot OR (a.time_slot IS NULL AND b.time_slot IS NULL))
                AND a.id < b.id
        ");

        // Add a generated (virtual) column so that NULL time_slot participates in
        // the unique index as an empty string — MySQL unique indexes treat each NULL
        // as distinct, so without this, multiple null-slot rows for the same
        // product+date would be allowed.
        if (!Schema::hasColumn('availability_slots', 'time_slot_key')) {
            Schema::table('availability_slots', function (Blueprint $table) {
                $table->string('time_slot_key', 50)
                    ->storedAs("COALESCE(time_slot, '')")
                    ->after('time_slot');
            });
        }

        // Create the unique index FIRST so MySQL has an alternative index covering
        // product_id before we drop the old one — MySQL requires at least one index
        // on a foreign key column at all times.
        $indexes = collect(DB::select("SHOW INDEX FROM availability_slots"))->pluck('Key_name');

        if (!$indexes->contains('availability_slots_unique_slot')) {
            Schema::table('availability_slots', function (Blueprint $table) {
                $table->unique(['product_id', 'date', 'time_slot_key'], 'availability_slots_unique_slot');
            });
        }

        if ($indexes->contains('availability_slots_product_id_date_index')) {
            Schema::table('availability_slots', function (Blueprint $table) {
                $table->dropIndex(['product_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('availability_slots', function (Blueprint $table) {
            $table->index(['product_id', 'date']);
        });

        Schema::table('availability_slots', function (Blueprint $table) {
            $table->dropUnique('availability_slots_unique_slot');
            $table->dropColumn('time_slot_key');
        });
    }
};
