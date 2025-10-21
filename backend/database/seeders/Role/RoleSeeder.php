<?php

namespace Database\Seeders\Role;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('roles')->truncate();

        $timestamp = now();
        $createdBy = 1; // fallback if seeding before users exist

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'System owner or developer with full access.',
            ],
            [
                'name' => 'HR Manager',
                'slug' => 'hr-manager',
                'description' => 'Responsible for managing overall HR operations.',
            ],
            [
                'name' => 'HR Officer',
                'slug' => 'hr-officer',
                'description' => 'Handles employee records, leaves, and attendance.',
            ],
            [
                'name' => 'Recruiter',
                'slug' => 'recruiter',
                'description' => 'Manages job postings, applicant tracking, and hiring processes.',
            ],
            [
                'name' => 'Payroll Officer',
                'slug' => 'payroll-officer',
                'description' => 'Handles payroll processing and related finance tasks.',
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Standard employee with self-service access.',
            ],
            [
                'name' => 'Department Head',
                'slug' => 'department-head',
                'description' => 'Leads specific departments and oversees team performance.',
            ],
            [
                'name' => 'IT Support',
                'slug' => 'it-support',
                'description' => 'Manages system access, accounts, and technical issues.',
            ],
        ];

        // Insert all roles
        foreach ($roles as &$role) {
            $role['guard_name'] = 'api';
            $role['created_by'] = $createdBy;
            $role['created_at'] = $timestamp;
            $role['updated_at'] = $timestamp;
        }

        DB::table('roles')->insert($roles);

        Schema::enableForeignKeyConstraints();
    }
}
