<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            Project::factory()->create([
                'owner_id' => $users->random()->id,
            ]);
        }
    }
}
