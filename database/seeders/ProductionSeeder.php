<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\EquipmentType;
use App\Models\Language;
use App\Models\Position;
use App\Support\Access;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * What the company's own installation starts with — and nothing made up.
 *
 * No people and no hardware: those are the demo (UserSeeder), and a real
 * installation fills them in by hand. The one account it cannot do without, the
 * system administrator, is not created here either, since a seeder has nobody to
 * ask for a name and a password: that is `php artisan hrm:sysadmin`.
 *
 * It is run on the first day and may be run again after every update, so it is
 * written to be harmless the second time. Two kinds of things are in it:
 *
 *  - What the code decides — the rights and the system administrator's position.
 *    Those are brought in line with the code on every run; an update that adds a
 *    right needs exactly this.
 *
 *  - What the company decides — its positions, departments, job titles,
 *    languages and equipment categories. The lists in the seeders are the
 *    company's own, so they are worth starting from, but from the first day on
 *    they are kept in «Справочники». Each is therefore filled only while it is
 *    still empty: a second run must not bring back a department somebody
 *    deleted, undo a renaming, or hand a position the rights taken from it.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // The rights first: a position cannot be given one that is not a row yet.
        // The only thing here that ever removes anything — a right that has left
        // the catalogue in the code, which no page can show any more.
        $this->call(PermissionSeeder::class);

        $this->systemAdministrator();

        // Every position besides that one is the company's to keep.
        if (Role::query()->whereNot('name', Access::SOLE_ROLE)->doesntExist()) {
            $this->call(RoleSeeder::class);
        }

        if (Department::query()->doesntExist()) {
            $this->call(DepartmentSeeder::class);
        }

        if (Position::query()->doesntExist()) {
            $this->call(PositionSeeder::class);
        }

        if (Language::query()->doesntExist()) {
            $this->call(LanguageSeeder::class);
        }

        if (EquipmentType::query()->doesntExist()) {
            $this->call(EquipmentTypeSeeder::class);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * The one position the system cannot work without. It may be renamed in the
     * directory, so an existing one is left exactly as it is.
     */
    private function systemAdministrator(): void
    {
        Role::firstOrCreate(
            ['name' => Access::SOLE_ROLE, 'guard_name' => 'web'],
            ['title' => RoleSeeder::ROLES[Access::SOLE_ROLE]],
        );
    }
}
