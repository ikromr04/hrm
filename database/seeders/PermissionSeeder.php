<?php

namespace Database\Seeders;

use App\Support\Access;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The rights themselves, as rows, so positions can be given them.
 *
 * What rights exist is decided in code (App\Support\Access); this only makes
 * sure the database knows the same list. A right that leaves the catalogue is
 * dropped here too, or it would go on hanging off positions invisibly.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Access::keys() as $key) {
            Permission::findOrCreate($key, 'web');
        }

        Permission::query()->whereNotIn('name', Access::keys())->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
