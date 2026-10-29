<?php

namespace Database\Seeders;

use App\Modules\Fondasi\Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        if (app()->environment('local')) {
            $this->call(DevUserSeeder::class);
        }
    }
}
