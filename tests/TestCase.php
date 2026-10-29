<?php

namespace Tests;

use App\Modules\Fondasi\Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /** Peran & izin selalu ada (docs/05). */
    protected bool $seed = true;

    protected string $seeder = RolePermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Tidak ada request keluar tanpa Http::fake() (docs/13).
        Http::preventStrayRequests();
        $this->withoutVite();
    }
}
