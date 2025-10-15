<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip foreign key checks for seeding
        \DB::statement('PRAGMA foreign_keys=OFF');

        $categories = [
            [
                'name' => 'Leadership',
                'description' => 'Leadership development and management skills',
                'color' => '#3B82F6', // Blue
                'sort_order' => 1,
                'created_by' => 1,
            ],
            [
                'name' => 'Technical Skills',
                'description' => 'Programming, software development, and technical expertise',
                'color' => '#10B981', // Green
                'sort_order' => 2,
                'created_by' => 1,
            ],
            [
                'name' => 'Design',
                'description' => 'UX/UI design, graphic design, and creative skills',
                'color' => '#F59E0B', // Yellow
                'sort_order' => 3,
                'created_by' => 1,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Digital marketing, content strategy, and brand management',
                'color' => '#EF4444', // Red
                'sort_order' => 4,
                'created_by' => 1,
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales techniques, customer relationship management',
                'color' => '#8B5CF6', // Purple
                'sort_order' => 5,
                'created_by' => 1,
            ],
            [
                'name' => 'Compliance',
                'description' => 'Regulatory compliance, legal requirements, and safety training',
                'color' => '#6B7280', // Gray
                'sort_order' => 6,
                'created_by' => 1,
            ],
            [
                'name' => 'Soft Skills',
                'description' => 'Communication, teamwork, and interpersonal skills',
                'color' => '#EC4899', // Pink
                'sort_order' => 7,
                'created_by' => 1,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }

        // Re-enable foreign key checks
        \DB::statement('PRAGMA foreign_keys=ON');
    }
}
