<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $totalUsers = 50000;
        $batchSize = 1000;

        for ($i = 0; $i < $totalUsers; $i += $batchSize) {
            User::factory()->count($batchSize)->create();
        }
    }
}
