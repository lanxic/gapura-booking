<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('site_settings')->insert([
            ['group' => 'email', 'key' => 'mailer',       'value' => 'smtp',                    'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'host',         'value' => 'localhost',               'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'port',         'value' => '1025',                    'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'username',     'value' => '',                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'password',     'value' => '',                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'encryption',   'value' => '',                        'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'from_address', 'value' => 'noreply@amartha-eticket.com', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'email', 'key' => 'from_name',    'value' => 'Amartha eTicket',         'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('group', 'email')->delete();
    }
};
