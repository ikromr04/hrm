<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Language;
use App\Models\Position;
use App\Models\User;
use App\Support\Access;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * What the company's own installation starts with: the rights, the positions
 * and the directories — and not one invented person or laptop.
 */
class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_brings_the_rights_the_positions_and_the_directories()
    {
        $this->seed(ProductionSeeder::class);

        $this->assertEqualsCanonicalizing(Access::keys(), Permission::pluck('name')->all());

        $this->assertSame(count(RoleSeeder::ROLES), Role::count());
        $this->assertSame('Системный администратор', Role::findByName(Access::SOLE_ROLE)->title);
        // The system administrator holds nothing; every other position may look around.
        $this->assertSame(0, Role::findByName(Access::SOLE_ROLE)->permissions()->count());
        $this->assertEqualsCanonicalizing(Access::defaults(), Role::findByName('analyst')->permissions->pluck('name')->all());

        $this->assertSame(count(DepartmentSeeder::TREE, COUNT_RECURSIVE), Department::count());
        $this->assertSame(count(PositionSeeder::HEADS) + count(PositionSeeder::OTHERS), Position::count());
        $this->assertSame(count(LanguageSeeder::LANGUAGES), Language::count());
        $this->assertSame(count(EquipmentTypeSeeder::TYPES), EquipmentType::count());
    }

    public function test_it_invents_no_people_and_no_hardware()
    {
        $this->seed(ProductionSeeder::class);

        // Not even the administrator: that one is `php artisan hrm:sysadmin`.
        $this->assertSame(0, User::count());
        $this->assertSame(0, Equipment::count());
    }

    public function test_a_second_run_leaves_what_the_company_changed_alone()
    {
        $this->seed(ProductionSeeder::class);

        // Somebody has been at work in «Справочники» since the first day.
        Role::findByName('intern')->delete();
        Role::findByName('analyst')->syncPermissions([]);
        Role::findByName(Access::SOLE_ROLE)->update(['title' => 'Главный администратор']);
        Department::firstWhere('name', 'Отдел Дизайна')->update(['name' => 'Отдел дизайна и вёрстки', 'parent_id' => null]);
        Position::firstWhere('name', 'Уборщица')->delete();
        Language::firstWhere('name', 'Арабский')->delete();
        EquipmentType::firstWhere('name', 'Печать')->delete();

        $counts = fn () => [Role::count(), Department::count(), Position::count(), Language::count(), EquipmentType::count()];
        $before = $counts();

        $this->seed(ProductionSeeder::class);

        $this->assertSame($before, $counts());
        $this->assertNull(Role::firstWhere('name', 'intern'));
        // Rights taken from a position stay taken.
        $this->assertSame(0, Role::findByName('analyst')->permissions()->count());
        $this->assertSame('Главный администратор', Role::findByName(Access::SOLE_ROLE)->title);
        $this->assertNull(Department::firstWhere('name', 'Отдел Дизайна'));
        $this->assertNull(Department::firstWhere('name', 'Отдел дизайна и вёрстки')->parent_id);
    }

    public function test_a_second_run_brings_a_right_added_by_an_update()
    {
        $this->seed(ProductionSeeder::class);

        // As the database looked before the update that introduced this right.
        Permission::findByName('employees.fire')->delete();

        $this->seed(ProductionSeeder::class);

        $this->assertTrue(Permission::where('name', 'employees.fire')->exists());
    }

    public function test_the_system_administrator_position_is_restored_if_it_is_missing()
    {
        $this->seed(ProductionSeeder::class);

        Role::findByName(Access::SOLE_ROLE)->delete();

        $this->seed(ProductionSeeder::class);

        $this->assertTrue(Role::where('name', Access::SOLE_ROLE)->exists());
        $this->assertSame(count(RoleSeeder::ROLES), Role::count());
    }

    public function test_a_plain_db_seed_in_production_is_the_careful_one()
    {
        $this->app['env'] = 'production';

        // In production the command asks before it runs; --force is the answer.
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(count(RoleSeeder::ROLES), Role::count());
    }
}
