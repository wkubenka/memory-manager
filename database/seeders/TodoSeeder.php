<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $todos = [
            [
                'title' => 'Review pull request #42',
                'is_completed' => true,
                'completed_at' => now()->subDays(2),
            ],
            [
                'title' => 'Update project dependencies',
                'is_completed' => false,
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Write tests for user registration',
                'is_completed' => false,
                'due_date' => now()->addDay(),
            ],
            [
                'title' => 'Schedule dentist appointment',
                'is_completed' => false,
            ],
            [
                'title' => 'Pay electricity bill',
                'is_completed' => true,
                'completed_at' => now()->subDay(),
            ],
            [
                'title' => 'Water the plants',
                'is_completed' => false,
                'recurrence' => 'weekly',
            ],
            [
                'title' => 'Take out the trash',
                'is_completed' => false,
                'due_date' => now(),
                'recurrence' => 'weekly',
            ],
        ];

        foreach ($todos as $data) {
            $user->todos()->create($data);
        }
    }
}
