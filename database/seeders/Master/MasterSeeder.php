<?php

namespace Database\Seeders\Master;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $records = [];

        // Real Departments
        $departments = [
            'Human Resources', 'Finance', 'IT', 'Marketing', 'Sales',
            'Legal', 'Procurement', 'Administration', 'Customer Support', 'Operations',
            'R&D', 'Product Management', 'Business Development', 'Logistics', 'Quality Assurance',
            'Compliance', 'Public Relations', 'Security', 'Engineering', 'Training',
            'Payroll', 'Inventory', 'Facility Management', 'Data Analytics', 'Production',
            'Content Writing', 'Digital Marketing', 'Event Management', 'Internal Audit', 'Medical Services'
        ];

        // Real Designations
        $designations = [
            'Software Engineer', 'HR Manager', 'Accountant', 'Marketing Executive', 'Sales Representative',
            'Project Manager', 'Team Lead', 'QA Analyst', 'UI/UX Designer', 'Business Analyst',
            'DevOps Engineer', 'Network Administrator', 'Operations Manager', 'Legal Advisor', 'Receptionist'
        ];

        // Real Employment Types
        $employmentTypes = [
            'Full-Time', 'Part-Time', 'Contract', 'Internship', 'Freelance',
            'Temporary', 'Remote', 'On-Site', 'Consultant', 'Probation'
        ];

        // Real Skills
        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React', 'Node.js', 'HTML', 'CSS', 'SQL', 'MySQL',
            'PostgreSQL', 'MongoDB', 'Docker', 'Git', 'AWS', 'Azure', 'Linux', 'Python', 'Java', 'C#',
            'C++', 'Flutter', 'Kotlin', 'Swift', 'Figma', 'Adobe XD', 'REST API', 'GraphQL', 'Agile',
            'Scrum', 'JIRA', 'CI/CD', 'Unit Testing', 'SEO', 'Digital Marketing', 'Google Ads', 'Copywriting',
            'Public Speaking', 'Team Management', 'Problem Solving', 'Leadership', 'Communication',
            'Time Management', 'Critical Thinking', 'Machine Learning', 'Data Analysis', 'Excel',
            'Power BI', 'Technical Writing'
        ];

        // locations
        $locations = [
            'Onsite', 'Hybrid', 'Remote'
        ];

        foreach ($departments as $name) {
            $records[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'type_id' => 1,
                'description' => "$name Department",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($designations as $name) {
            $records[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'type_id' => 2,
                'description' => "$name Designation",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($employmentTypes as $name) {
            $records[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'type_id' => 3,
                'description' => "$name Employment Type",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($skills as $name) {
            $records[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'type_id' => 4,
                'description' => "$name Skill",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($locations as $name) {
            $records[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'type_id' => 5,
                'description' => "$name Skill",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('masters')->insert($records);
    }
}
