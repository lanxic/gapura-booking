<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        // Seed default values
        $now = now();
        DB::table('site_settings')->insert([
            // General
            ['group' => 'general', 'key' => 'app_name',        'value' => 'Amartha eTicket',                  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general', 'key' => 'app_description', 'value' => 'Platform ticketing wisata Amartha', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general', 'key' => 'logo_url',        'value' => null,                               'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general', 'key' => 'favicon_url',     'value' => null,                               'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general', 'key' => 'contact_email',   'value' => 'info@amartha-eticket.com',         'created_at' => $now, 'updated_at' => $now],
            // Payment
            ['group' => 'payment', 'key' => 'full_payment',     'value' => 'true',             'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payment', 'key' => 'down_payment',     'value' => 'true',             'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payment', 'key' => 'dp_percentages',   'value' => '[30,50,70]',       'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payment', 'key' => 'midtrans_enabled', 'value' => 'true',             'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payment', 'key' => 'cash_enabled',     'value' => 'true',             'created_at' => $now, 'updated_at' => $now],
            // Notifications
            ['group' => 'notifications', 'key' => 'email_order',       'value' => 'true',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'notifications', 'key' => 'email_payment',     'value' => 'true',  'created_at' => $now, 'updated_at' => $now],
            ['group' => 'notifications', 'key' => 'whatsapp_enabled',  'value' => 'false', 'created_at' => $now, 'updated_at' => $now],
            // Legal
            ['group' => 'legal', 'key' => 'privacy_policy',    'value' => null, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'legal', 'key' => 'terms_of_service',  'value' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
