<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Password;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo kullanıcı oluştur veya bul
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Kullanıcı',
                'password' => bcrypt('password123'),
            ]
        );

        // Demo şifreler oluştur
        $passwords = [
            [
                'title' => 'Gmail',
                'url' => 'https://gmail.com',
                'username' => 'demo@gmail.com',
                'password' => 'MyGmailPassword123!',
            ],
            [
                'title' => 'Facebook',
                'url' => 'https://facebook.com',
                'username' => 'demo@facebook.com',
                'password' => 'FacebookPass456!',
            ],
            [
                'title' => 'Twitter',
                'url' => 'https://twitter.com',
                'username' => '@demouser',
                'password' => 'TwitterSecret789!',
            ],
            [
                'title' => 'Netflix',
                'url' => 'https://netflix.com',
                'username' => 'demo@netflix.com',
                'password' => 'NetflixWatch2024!',
            ],
            [
                'title' => 'GitHub',
                'url' => 'https://github.com',
                'username' => 'demouser',
                'password' => 'GitHubCode2024!',
            ],
        ];

        foreach ($passwords as $passwordData) {
            Password::create([
                'user_id' => $user->id,
                'title' => $passwordData['title'],
                'url' => $passwordData['url'],
                'username' => $passwordData['username'],
                'password' => $passwordData['password'],
            ]);
        }
    }
}
