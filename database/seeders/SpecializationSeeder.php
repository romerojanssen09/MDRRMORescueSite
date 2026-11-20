<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $defaults = [
            ['name' => 'Fire & Rescue', 'description' => 'Firefighting and fire-related emergencies'],
            ['name' => 'Medical Emergency', 'description' => 'Medical response and first aid'],
            ['name' => 'Search & Rescue', 'description' => 'Search and rescue operations'],
            ['name' => 'Flood Response', 'description' => 'Flood and water-related emergencies'],
            ['name' => 'Earthquake Response', 'description' => 'Earthquake and structural collapse response'],
            ['name' => 'Traffic & Accident', 'description' => 'Traffic accidents and road emergencies'],
            ['name' => 'Hazmat', 'description' => 'Hazardous materials handling'],
            ['name' => 'K9 Unit', 'description' => 'Search and rescue with trained dogs'],
        ];

        foreach ($defaults as $spec) {
            DB::table('specializations')->insertOrIgnore([
                'name' => $spec['name'],
                'description' => $spec['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
