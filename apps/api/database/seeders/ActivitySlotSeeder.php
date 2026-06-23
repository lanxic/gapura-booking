<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivitySchedule;
use App\Models\ActivitySlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ActivitySlotSeeder extends Seeder
{
    public function run(): void
    {
        $activities = Activity::with('schedules')->get();

        if ($activities->isEmpty()) {
            $this->command->warn('Tidak ada aktivitas. Jalankan ActivitySeeder terlebih dahulu.');
            return;
        }

        $start   = Carbon::today();
        $end     = Carbon::today()->addDays(30);
        $created = 0;

        foreach ($activities as $activity) {
            $schedules = $activity->schedules->where('is_active', true);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dow = $date->dayOfWeek;

                foreach ($schedules->where('day_of_week', $dow) as $schedule) {
                    ActivitySlot::firstOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'date'        => $date->toDateString(),
                            'start_time'  => $schedule->start_time,
                        ],
                        [
                            'schedule_id' => $schedule->id,
                            'end_time'    => $schedule->end_time,
                            'capacity'    => $schedule->default_capacity,
                            'booked_count'=> 0,
                            'price'       => $activity->base_price,
                            'status'      => 'available',
                        ]
                    );
                    $created++;
                }
            }
        }

        $this->command->info("Seeded {$created} activity slots untuk 30 hari ke depan.");
    }
}
