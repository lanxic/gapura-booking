<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityAddon;
use App\Models\ActivitySchedule;
use App\Models\ActivitySlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NightSafariSeeder extends Seeder
{
    public function run(): void
    {
        // ── Activity ──────────────────────────────────────────────────────────
        $slug     = 'night-safari-safari-malam';
        $activity = Activity::firstOrCreate(
            ['slug' => $slug],
            [
                'name'             => 'Night Safari (Safari Malam)',
                'slug'             => $slug,
                'category'         => 'outdoor',
                'description'      => 'NIGHT SAFARI DOMESTIK + DINNER',
                'duration_minutes' => 120,
                'min_pax'          => 1,
                'max_pax'          => 75,
                'level'            => 'beginner',
                'min_age'          => 3,
                'base_price'       => 600000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => [
                    'location'   => 'Bali Safari & Marine Park, Jl. Prof. Dr. Ida Bagus Mantra No.km, Serongga, Kec. Gianyar, Kabupaten Gianyar, Bali 80551, Indonesia',
                    'highlights' => [
                        'Nikmati Safari Malam eksklusif di Bali Safari & Marine Park',
                        'Makan malam spesial dengan pemandangan satwa liar',
                        'Pertunjukan Api Afrika yang memukau',
                        'Pengalaman tak terlupakan bersama keluarga',
                    ],
                    'includes' => [
                        'Walk-in Safari (Aktivitas dilakukan bersama pengunjung lainnya)',
                        '1x Night Safari Journey (Aktivitas dilakukan bersama pengunjung lainnya)',
                        'Dinner',
                        'The African Rhythm of Fire Dance (Kegiatan Opsional)',
                        'Welcome Drink',
                    ],
                    'excludes' => [
                        'Transportasi dari dan ke lokasi',
                        'Pengeluaran pribadi',
                        'Aktivitas berbayar lainnya di dalam taman',
                    ],
                    'what_to_expect' => [
                        'Perjalanan safari malam melewati habitat satwa liar asli',
                        'Makan malam prasmanan dengan menu pilihan',
                        'Pertunjukan tari api yang spektakuler',
                        'Berjalan-jalan di dalam trem yang mengitari kandang satwa',
                    ],
                    'what_to_bring' => [
                        'Show Mobile Ticket: Ticketing counter',
                    ],
                    'cancellation_policy' => 'Pembatalan dilakukan minimal 24 jam sebelum jadwal untuk mendapatkan refund penuh.',
                ],
            ]
        );

        $this->command->info("Activity: {$activity->name} — " . ($activity->wasRecentlyCreated ? 'created' : 'already exists'));

        // ── Schedules ─────────────────────────────────────────────────────────
        $schedules = [
            // Fri–Sun 19:00–21:00
            ['day_of_week' => 5, 'start_time' => '19:00', 'end_time' => '21:00', 'default_capacity' => 75],
            ['day_of_week' => 6, 'start_time' => '19:00', 'end_time' => '21:00', 'default_capacity' => 75],
            ['day_of_week' => 0, 'start_time' => '19:00', 'end_time' => '21:00', 'default_capacity' => 75],
        ];

        foreach ($schedules as $s) {
            ActivitySchedule::firstOrCreate(
                ['activity_id' => $activity->id, 'day_of_week' => $s['day_of_week'], 'start_time' => $s['start_time']],
                array_merge($s, ['activity_id' => $activity->id, 'is_active' => true])
            );
        }

        // ── Slots (next 60 days) ───────────────────────────────────────────────
        $activity->load('schedules');
        $start   = Carbon::today();
        $end     = Carbon::today()->addDays(60);
        $created = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dow = $date->dayOfWeek;

            foreach ($activity->schedules->where('is_active', true)->where('day_of_week', $dow) as $schedule) {
                $slot = ActivitySlot::firstOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'date'        => $date->toDateString(),
                        'start_time'  => $schedule->start_time,
                    ],
                    [
                        'schedule_id'  => $schedule->id,
                        'end_time'     => $schedule->end_time,
                        'capacity'     => $schedule->default_capacity,
                        'booked_count' => 0,
                        'price'        => $activity->base_price,
                        'status'       => 'available',
                    ]
                );

                if ($slot->wasRecentlyCreated) $created++;
            }
        }

        $this->command->info("Slots created: {$created}");

        // ── Add-ons ───────────────────────────────────────────────────────────
        $addons = [
            ['name' => 'Wildlife Photo Package',   'price' => 150000, 'unit' => 'pax', 'max_qty' => 10],
            ['name' => 'Premium Dinner Upgrade',   'price' => 200000, 'unit' => 'pax', 'max_qty' => 10],
            ['name' => 'Souvenir Package',         'price' =>  85000, 'unit' => 'item', 'max_qty' =>  5],
            ['name' => 'Private Tram Experience',  'price' => 500000, 'unit' => 'session', 'max_qty' =>  1],
        ];

        foreach ($addons as $addon) {
            ActivityAddon::firstOrCreate(
                ['activity_id' => $activity->id, 'name' => $addon['name']],
                [
                    'activity_id' => $activity->id,
                    'price'       => $addon['price'],
                    'unit'        => $addon['unit'],
                    'max_qty'     => $addon['max_qty'],
                    'is_active'   => true,
                ]
            );
        }

        $this->command->info('Add-ons seeded: ' . count($addons));
    }
}
