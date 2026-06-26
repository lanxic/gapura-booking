<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel 12 (Symfony Mailer) tidak mengenal scheme 'ssl'/'tls' —
        // harus dikosongkan agar AppServiceProvider memetakan ke null (no encryption).
        DB::table('site_settings')
            ->where('group', 'email')
            ->where('key', 'encryption')
            ->whereIn('value', ['ssl', 'tls'])
            ->update(['value' => '', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('group', 'email')
            ->where('key', 'encryption')
            ->where('value', '')
            ->update(['value' => 'ssl', 'updated_at' => now()]);
    }
};
