<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            ['group' => 'email', 'key' => 'mailer',       'value' => 'smtp',                           'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'host',         'value' => env('MAIL_HOST', 'smtp.resend.com'), 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'port',         'value' => (string) env('MAIL_PORT', 465),   'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'username',     'value' => env('MAIL_USERNAME', 'resend'),   'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'password',     'value' => env('MAIL_PASSWORD', ''),         'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'encryption',   'value' => 'ssl',                            'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'from_address', 'value' => env('MAIL_FROM_ADDRESS', 'noreply@amartha-eticket.com'), 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'from_name',    'value' => env('MAIL_FROM_NAME', 'Amartha eTicket'), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('group', 'email')->delete();
    }
};
