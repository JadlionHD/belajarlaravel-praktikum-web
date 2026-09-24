<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Seeder hanya untuk latihan lokal.');
        }

        foreach ([
            ['Ani', 'ani@example.test', false],
            ['Budi', 'budi@example.test', false],
            ['Admin', 'admin@example.test', true],
        ] as [$name, $email, $admin]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->password = Hash::make('LatihanWeb2!2026');
            $user->is_admin = $admin;
            $user->save();
        }

        $category = Category::firstOrCreate(['name' => 'Jaringan']);

        $ani = User::where('email', 'ani@example.test')->first();
        $budi = User::where('email', 'budi@example.test')->first();

        if ($ani && Ticket::where('user_id', $ani->id)->count() === 0) {
            for ($i = 1; $i <= 6; $i++) {
                $ticket = Ticket::create([
                    'user_id' => $ani->id,
                    'category_id' => $category->id,
                    'subject' => "Tiket Latihan Ani #{$i}",
                    'description' => "Deskripsi kendala teknis tiket #{$i} milik Ani.",
                    'status' => $i === 1 ? 'open' : ($i === 2 ? 'pending' : 'open'),
                    'is_urgent' => $i % 2 === 1,
                ]);

                $ticket->comments()->create([
                    'user_id' => $ani->id,
                    'body' => "Catatan awal tiket #{$i}.",
                ]);
            }
        }

        if ($budi && Ticket::where('user_id', $budi->id)->count() === 0) {
            $ticketBudi = Ticket::create([
                'user_id' => $budi->id,
                'category_id' => $category->id,
                'subject' => 'Tiket Privat Milik Budi',
                'description' => 'Kendala printer di ruangan Budi.',
                'status' => 'open',
                'is_urgent' => false,
            ]);

            $ticketBudi->comments()->create([
                'user_id' => $budi->id,
                'body' => 'Catatan awal Budi.',
            ]);
        }
    }
}
