<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotSeeder extends Seeder
{
   
    public function run(): void
    {
        $slots = [
            [
                'name' => 'Утренняя доставка (08:00-10:00)',
                'capacity' => 10,
                'remaining' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Дневная доставка (12:00-14:00)',
                'capacity' => 15,
                'remaining' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Вечерняя доставка (18:00-20:00)',
                'capacity' => 8,
                'remaining' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Склад А - окно 1',
                'capacity' => 5,
                'remaining' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Склад А - окно 2',
                'capacity' => 5,
                'remaining' => 0, // Этот слот полностью занят
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('slots')->insert($slots);
    }
}
