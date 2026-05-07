<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Post::factory()->count(3)->for($testUser, 'author')->create();
        Post::factory()->draft()->for($testUser, 'author')->create();
        Post::factory()->scheduled()->for($testUser, 'author')->create();

        User::factory()
            ->count(5)
            ->has(Post::factory()->count(3), 'posts')
            ->create();
    }
}
