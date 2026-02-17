<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $notes = [
            [
                'title' => 'Project Ideas',
                'content' => "Build a personal finance tracker\nCreate a recipe sharing app\nMake a habit tracker with streaks",
            ],
            [
                'title' => 'Meeting Notes - Feb 10',
                'content' => "Discussed Q1 roadmap priorities.\nAgreed to ship the new dashboard by end of month.\nNeed to follow up with design team on the onboarding flow.",
            ],
            [
                'title' => 'Books to Read',
                'content' => "Designing Data-Intensive Applications\nThe Pragmatic Programmer\nClean Architecture\nRefactoring by Martin Fowler",
            ],
            [
                'title' => null,
                'content' => 'Remember to renew the domain before March 1st.',
            ],
            [
                'title' => 'Git Commands Cheat Sheet',
                'content' => "git rebase -i HEAD~3\ngit stash pop\ngit log --oneline --graph\ngit cherry-pick <commit-hash>",
            ],
            [
                'title' => 'Grocery List',
                'content' => "Olive oil\nGarlic\nChicken thighs\nBasil\nParmesan",
            ],
        ];

        foreach ($notes as $data) {
            $user->notes()->create($data);
        }
    }
}
