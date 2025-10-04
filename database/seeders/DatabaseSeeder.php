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
        // 1) Create default roles from enum
        foreach (UserRole::toArray() as $roleName) {
            Role::findOrCreate($roleName);
        }

        // 2) Create a sample admin user only in local environment
        if (app()->environment('local')) {
            $user = User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    // Local-only default password
                    'password' => bcrypt('password'),
                ]
            );

            // 3) Assign admin role to the sample user
            $user->syncRoles([UserRole::ADMIN->value]);
        }
    }
}
