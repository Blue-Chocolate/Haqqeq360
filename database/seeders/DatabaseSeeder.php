<?php

namespace Database\Seeders;

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
        $this->call([
        UserSeeder::class,        // First - needed for instructor_id
        RoleSeeder::class,        // For roles
        CategoriesSeeder::class,  // Second - needed for category_id
        CourseSeeder::class,      // Uses instructor_id
        BootcampSeeder::class,    // Uses instructor_id
        // ProgramsSeeder::class,    // Uses category_id
        EvaluationSeeder::class
    ]);
       User::factory()->create([
    'first_name' => 'Test',
    'second_name' => 'User',
    'email' => 'test@example.com',
]);
    }
}
