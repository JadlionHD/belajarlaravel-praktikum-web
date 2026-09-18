<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(10)->create();
        $categories = collect(['Akun', 'Jaringan', 'Aplikasi'])
            ->map(fn ($name) => Category::factory()->create(['name' => $name]));

        Ticket::factory()->count(50)
            ->recycle($users)->recycle($categories)->create()
            ->each(function ($ticket) use ($users) {
                Comment::factory()->count(2)
                    ->for($ticket)->recycle($users)->create();
            });
    }
}
