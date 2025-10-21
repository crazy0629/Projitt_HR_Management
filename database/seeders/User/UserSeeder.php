<?php

namespace Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();

        $users = [
            // Super Admin (role_id = 1)
            ['uuid' => 'U10001', 'first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'super.admin1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 1],
            ['uuid' => 'U10002', 'first_name' => 'System', 'last_name' => 'Owner', 'email' => 'super.admin2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 1],

            // HR Manager (role_id = 2)
            ['uuid' => 'U10003', 'first_name' => 'HR', 'last_name' => 'Manager1', 'email' => 'hr.manager1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 2],
            ['uuid' => 'U10004', 'first_name' => 'HR', 'last_name' => 'Manager2', 'email' => 'hr.manager2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 2],

            // HR Officer (role_id = 3)
            ['uuid' => 'U10005', 'first_name' => 'Officer', 'last_name' => 'HR1', 'email' => 'hr.officer1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 3],
            ['uuid' => 'U10006', 'first_name' => 'Officer', 'last_name' => 'HR2', 'email' => 'hr.officer2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 3],

            // Recruiter (role_id = 4)
            ['uuid' => 'U10007', 'first_name' => 'Recruiter', 'last_name' => 'One', 'email' => 'recruiter1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 4],
            ['uuid' => 'U10008', 'first_name' => 'Recruiter', 'last_name' => 'Two', 'email' => 'recruiter2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 4],

            // Payroll Officer (role_id = 5)
            ['uuid' => 'U10009', 'first_name' => 'Payroll', 'last_name' => 'One', 'email' => 'payroll1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 5],
            ['uuid' => 'U10010', 'first_name' => 'Payroll', 'last_name' => 'Two', 'email' => 'payroll2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 5],

            // Employee (role_id = 6)
            ['uuid' => 'U10011', 'first_name' => 'Adeel', 'last_name' => 'Employee', 'email' => 'employee1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 6],
            ['uuid' => 'U10012', 'first_name' => 'Zara', 'last_name' => 'Employee', 'email' => 'employee2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 6],

            // Department Head (role_id = 7)
            ['uuid' => 'U10013', 'first_name' => 'Head', 'last_name' => 'Dept1', 'email' => 'dept.head1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 7],
            ['uuid' => 'U10014', 'first_name' => 'Head', 'last_name' => 'Dept2', 'email' => 'dept.head2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 7],

            // IT Support (role_id = 8)
            ['uuid' => 'U10015', 'first_name' => 'IT', 'last_name' => 'Support1', 'email' => 'it.support1@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 8],
            ['uuid' => 'U10016', 'first_name' => 'IT', 'last_name' => 'Support2', 'email' => 'it.support2@example.com', 'password' => bcrypt('pass*&1122'), 'first_login' => 1, 'role_id' => 8],
        ];

        DB::table('users')->insert($users);

        Schema::enableForeignKeyConstraints();
    }
}
