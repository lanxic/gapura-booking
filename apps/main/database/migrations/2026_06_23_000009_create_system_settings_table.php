<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Menggantikan site_settings untuk konfigurasi operasional (PRD Section 4.6.8).
// Semua konfigurasi di sini, bukan di .env, agar bisa diubah dari Admin Portal tanpa deploy.
// Nilai sensitif (API key, secret) disimpan terenkripsi AES-256 via Laravel Encryption.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 50);   // email | whatsapp | booking | general
            $table->string('key_name', 100);
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['group_name', 'key_name']);
            $table->index('group_name');
        });

        $now = now();

        // ── Booking Rules ──────────────────────────────────────────────────
        DB::table('system_settings')->insert([
            ['group_name' => 'booking', 'key_name' => 'slot_lock_ttl_minutes',   'value' => '120',    'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'booking', 'key_name' => 'max_retry_payment',       'value' => '3',      'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'booking', 'key_name' => 'advance_booking_days_dp', 'value' => '3',      'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'booking', 'key_name' => 'full_payment_threshold_days', 'value' => '3',  'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],

            // ── General ───────────────────────────────────────────────────
            ['group_name' => 'general', 'key_name' => 'property_name', 'value' => 'Activity Booking', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'general', 'key_name' => 'timezone',      'value' => 'Asia/Jakarta',     'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'general', 'key_name' => 'currency',      'value' => 'IDR',              'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'general', 'key_name' => 'default_locale','value' => 'id',               'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'general', 'key_name' => 'logo_url',      'value' => null,               'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],

            // ── Email ─────────────────────────────────────────────────────
            ['group_name' => 'email', 'key_name' => 'smtp_host',     'value' => null,            'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'smtp_port',     'value' => '587',           'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'smtp_username', 'value' => null,            'is_encrypted' => true,  'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'smtp_password', 'value' => null,            'is_encrypted' => true,  'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'sender_name',   'value' => 'Activity Booking', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'sender_email',  'value' => null,            'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'notify_booking_confirmation', 'value' => 'true', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'notify_reminder_h1',          'value' => 'true', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'email', 'key_name' => 'notify_cancellation',         'value' => 'true', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],

            // ── WhatsApp ─────────────────────────────────────────────────
            ['group_name' => 'whatsapp', 'key_name' => 'provider',    'value' => null,  'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now], // twilio | meta
            ['group_name' => 'whatsapp', 'key_name' => 'account_sid', 'value' => null,  'is_encrypted' => true,  'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'whatsapp', 'key_name' => 'auth_token',  'value' => null,  'is_encrypted' => true,  'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'whatsapp', 'key_name' => 'from_number', 'value' => null,  'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
            ['group_name' => 'whatsapp', 'key_name' => 'is_active',   'value' => 'false', 'is_encrypted' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
