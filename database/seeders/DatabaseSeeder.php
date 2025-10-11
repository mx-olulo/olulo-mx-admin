<?php

namespace Database\Seeders;

use App\Enums\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // RBAC with Tenancy: team_id 필수
        // UserRole enum 기반 역할은 RoleSeeder에서 team_id와 함께 생성
        
        // Local environment only
        if (app()->environment('local')) {
            // Seed RBAC roles with team_id and scopes
            $this->call(RoleSeeder::class);
        }
    }
}
