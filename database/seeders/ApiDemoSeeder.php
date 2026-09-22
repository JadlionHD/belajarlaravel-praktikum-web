<?php

namespace Database\Seeders;

use App\Models\Category;
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

        Category::firstOrCreate(['name' => 'Jaringan']);
    }
}
