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

        $data = [
            [
                'name' => 'Super Admin',
                'description' => 'System owner or developer with full access',
                'slug' => 'super-admin',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'HR Manager',
                'description' => 'Responsible for managing overall HR operations',
                'slug' => 'hr-manager',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'HR Officer',
                'description' => 'Handles employee records, leaves, and attendance',
                'slug' => 'hr-officer',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'Recruiter',
                'description' => 'Manages job postings, applicant tracking, and hiring processes',
                'slug' => 'recruiter',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'Payroll Officer',
                'description' => 'Handles payroll processing and related finance tasks',
                'slug' => 'payroll-officer',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'Employee',
                'description' => 'Standard employee with self-service access',
                'slug' => 'employee',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'Department Head',
                'description' => 'Leads specific departments and oversees team performance',
                'slug' => 'department-head',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
            [
                'name' => 'IT Support',
                'description' => 'Manages system access, accounts, and technical issues',
                'slug' => 'it-support',
                'guard_name' => 'api',
                'created_by' => 1,
                'created_at' => $timestamp,
            ],
        ];

        DB::table('roles')->insert($data);
        Schema::enableForeignKeyConstraints();
    }
}
