<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::with('owner')->get();
        $users = User::all();
        if ($projects->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            Task::factory(fake()->numberBetween(3, 12))->create([
                'project_id' => $project->id,
                'assignee_id' => fake()->optional(0.65)->passthrough($users->random()->id),
            ]);
        }
    }
}
