<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed the authorization catalog for every test that has migrated the
        // database. Tests without RefreshDatabase (unit) have no tables and are
        // skipped safely.
        if (Schema::hasTable(config('permission.table_names.roles'))) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }
}
