<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks (SQLite specific for local dev)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $categories = [
            [
                'name' => 'Leadership',
                'description' => 'Leadership development and management skills',
                'color' => '#3B82F6', // Blue
                'sort_order' => 1,
            ],
            [
                'name' => 'Technical Skills',
                'description' => 'Programming, software development, and technical expertise',
                'color' => '#10B981', // Green
                'sort_order' => 2,
            ],
            [
                'name' => 'Design',
                'description' => 'UX/UI design, graphic design, and creative skills',
                'color' => '#F59E0B', // Yellow
                'sort_order' => 3,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Digital marketing, content strategy, and brand management',
                'color' => '#EF4444', // Red
                'sort_order' => 4,
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales techniques, customer relationship management',
                'color' => '#8B5CF6', // Purple
                'sort_order' => 5,
            ],
            [
                'name' => 'Compliance',
                'description' => 'Regulatory compliance, legal requirements, and safety training',
                'color' => '#6B7280', // Gray
                'sort_order' => 6,
            ],
            [
                'name' => 'Soft Skills',
                'description' => 'Communication, teamwork, and interpersonal skills',
                'color' => '#EC4899', // Pink
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'color' => $category['color'],
                'is_active' => true,
                'sort_order' => $category['sort_order'],
                'created_by' => 1, // System or admin user
            ]);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
