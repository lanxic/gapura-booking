<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityAddon;
use App\Models\ActivitySchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            [
                'name'             => 'Yoga Morning Flow',
                'category'         => 'indoor',
                'description'      => 'Kelas yoga pagi hari yang menyegarkan dengan instruktur berpengalaman. Cocok untuk semua level.',
                'duration_minutes' => 60,
                'min_pax'          => 1,
                'max_pax'          => 15,
                'level'            => 'beginner',
                'min_age'          => 12,
                'base_price'       => 150000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => ['equipment_provided' => true, 'dress_code' => 'Pakaian olahraga nyaman'],
                'schedules'        => [
                    ['day_of_week' => 1, 'start_time' => '07:00', 'end_time' => '08:00', 'default_capacity' => 15],
                    ['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '08:00', 'default_capacity' => 15],
                    ['day_of_week' => 5, 'start_time' => '07:00', 'end_time' => '08:00', 'default_capacity' => 15],
                    ['day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '09:00', 'default_capacity' => 20],
                ],
                'addons'           => [
                    ['name' => 'Yoga Mat Rental', 'description' => 'Sewa matras yoga premium', 'price' => 25000, 'is_active' => true],
                    ['name' => 'Towel Set', 'description' => 'Handuk kecil dan besar', 'price' => 20000, 'is_active' => true],
                ],
            ],
            [
                'name'             => 'Cooking Class: Indonesian Cuisine',
                'category'         => 'indoor',
                'description'      => 'Pelajari cara memasak hidangan khas Indonesia bersama chef profesional. Termasuk makan bersama di akhir kelas.',
                'duration_minutes' => 120,
                'min_pax'          => 2,
                'max_pax'          => 12,
                'level'            => 'beginner',
                'min_age'          => 10,
                'base_price'       => 350000,
                'status'           => 'active',
                'meta'             => ['includes_meal' => true, 'language' => 'Indonesia/English'],
                'schedules'        => [
                    ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '12:00', 'default_capacity' => 12],
                    ['day_of_week' => 0, 'start_time' => '10:00', 'end_time' => '12:00', 'default_capacity' => 12],
                ],
                'addons'           => [
                    ['name' => 'Recipe Book', 'description' => 'Buku resep masakan Indonesia', 'price' => 75000, 'is_active' => true],
                    ['name' => 'Apron & Hat Set', 'description' => 'Apron dan topi chef souvenir', 'price' => 50000, 'is_active' => true],
                ],
            ],
            [
                'name'             => 'Hiking Gunung Bunder',
                'category'         => 'outdoor',
                'description'      => 'Petualangan hiking ke Gunung Bunder dengan pemandu lokal berpengalaman. Menyaksikan sunrise yang memukau.',
                'duration_minutes' => 360,
                'min_pax'          => 4,
                'max_pax'          => 20,
                'level'            => 'intermediate',
                'min_age'          => 15,
                'base_price'       => 275000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => ['meeting_point' => 'Basecamp Gunung Bunder, Bogor', 'departure_time' => '03:00', 'what_to_bring' => ['Sepatu hiking', 'Jaket tebal', 'Air minum 2L', 'Bekal makanan']],
                'schedules'        => [
                    ['day_of_week' => 5, 'start_time' => '03:00', 'end_time' => '09:00', 'default_capacity' => 20],
                    ['day_of_week' => 6, 'start_time' => '03:00', 'end_time' => '09:00', 'default_capacity' => 20],
                ],
                'addons'           => [
                    ['name' => 'Trekking Pole Rental', 'description' => 'Sewa tongkat trekking', 'price' => 35000, 'is_active' => true],
                    ['name' => 'Porter Service', 'description' => 'Jasa porter untuk membawa perlengkapan', 'price' => 150000, 'is_active' => true],
                    ['name' => 'Breakfast Pack', 'description' => 'Paket sarapan setelah hiking', 'price' => 45000, 'is_active' => true],
                ],
            ],
            [
                'name'             => 'Pottery & Ceramics Workshop',
                'category'         => 'indoor',
                'description'      => 'Workshop membuat keramik dari tanah liat dengan tangan sendiri. Hasilnya bisa dibawa pulang setelah dibakar.',
                'duration_minutes' => 90,
                'min_pax'          => 1,
                'max_pax'          => 10,
                'level'            => 'beginner',
                'min_age'          => 8,
                'base_price'       => 250000,
                'status'           => 'active',
                'meta'             => ['materials_included' => true, 'pickup_after_days' => 7],
                'schedules'        => [
                    ['day_of_week' => 2, 'start_time' => '14:00', 'end_time' => '15:30', 'default_capacity' => 10],
                    ['day_of_week' => 4, 'start_time' => '14:00', 'end_time' => '15:30', 'default_capacity' => 10],
                    ['day_of_week' => 6, 'start_time' => '13:00', 'end_time' => '14:30', 'default_capacity' => 10],
                    ['day_of_week' => 0, 'start_time' => '13:00', 'end_time' => '14:30', 'default_capacity' => 10],
                ],
                'addons'           => [
                    ['name' => 'Extra Clay (500g)', 'description' => 'Tanah liat tambahan untuk karya kedua', 'price' => 30000, 'is_active' => true],
                    ['name' => 'Gift Wrapping', 'description' => 'Kemasan kado eksklusif untuk karya selesai', 'price' => 20000, 'is_active' => true],
                ],
            ],
            [
                'name'             => 'Stand-Up Paddleboarding (SUP)',
                'category'         => 'outdoor',
                'description'      => 'Belajar Stand-Up Paddleboarding di danau yang tenang. Instruktur bersertifikat dan peralatan lengkap disediakan.',
                'duration_minutes' => 90,
                'min_pax'          => 2,
                'max_pax'          => 8,
                'level'            => 'beginner',
                'min_age'          => 12,
                'base_price'       => 325000,
                'status'           => 'active',
                'is_featured'      => true,
                'meta'             => ['equipment_included' => true, 'life_jacket' => true, 'location' => 'Danau Situ Gunung, Sukabumi'],
                'schedules'        => [
                    ['day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '09:30', 'default_capacity' => 8],
                    ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '11:30', 'default_capacity' => 8],
                    ['day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '09:30', 'default_capacity' => 8],
                    ['day_of_week' => 0, 'start_time' => '10:00', 'end_time' => '11:30', 'default_capacity' => 8],
                ],
                'addons'           => [
                    ['name' => 'Underwater Photo Package', 'description' => 'Foto underwater + editing', 'price' => 100000, 'is_active' => true],
                    ['name' => 'Waterproof Phone Case', 'description' => 'Case anti air untuk ponsel Anda', 'price' => 25000, 'is_active' => true],
                ],
            ],
        ];

        foreach ($activities as $activityData) {
            $schedules = $activityData['schedules'];
            $addons    = $activityData['addons'];
            unset($activityData['schedules'], $activityData['addons']);

            $slug = Str::slug($activityData['name']);
            $activity = Activity::firstOrCreate(
                ['slug' => $slug],
                array_merge($activityData, ['slug' => $slug])
            );

            foreach ($schedules as $schedule) {
                ActivitySchedule::firstOrCreate(
                    ['activity_id' => $activity->id, 'day_of_week' => $schedule['day_of_week'], 'start_time' => $schedule['start_time']],
                    array_merge($schedule, ['activity_id' => $activity->id, 'is_active' => true])
                );
            }

            foreach ($addons as $addon) {
                ActivityAddon::firstOrCreate(
                    ['activity_id' => $activity->id, 'name' => $addon['name']],
                    ['price' => $addon['price'], 'is_active' => $addon['is_active']]
                );
            }
        }

        $this->command->info('Seeded ' . count($activities) . ' activities with schedules and addons.');
    }
}
