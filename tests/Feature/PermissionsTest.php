<?php

namespace Tests\Feature;

use App\Models\PermissionOverride;
use App\Models\User;
use App\Support\Access;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Who may do what.
 *
 * A right travels with a position, and can be given to — or taken from — one
 * person in particular. What the two ways add up to is one answer per right,
 * and that answer is what every page, route and gate goes by.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function sysadmin(): User
    {
        return User::factory()->create()->assignRole('sysadmin');
    }

    /** Somebody whose position carries exactly the given rights. */
    private function withRights(string ...$rights): User
    {
        $role = Role::create(['name' => 'test-'.Role::count(), 'title' => 'Тестовая позиция '.Role::count(), 'guard_name' => 'web']);
        $role->syncPermissions($rights);

        return User::factory()->create()->assignRole($role);
    }

    public function test_every_right_in_the_catalogue_exists_as_a_row()
    {
        $this->assertSame(Access::keys(), Permission::orderBy('id')->pluck('name')->all());

        // And nothing else: a right that leaves the code leaves the database.
        $this->assertSame(count(Access::keys()), Permission::count());
    }

    public function test_a_position_carries_looking_around_and_nothing_more()
    {
        $colleague = User::factory()->create()->assignRole('analyst');

        $this->assertTrue($colleague->can('employees.view'));
        $this->assertTrue($colleague->can('equipment.view'));
        $this->assertTrue($colleague->can('departments.view'));

        $this->assertFalse($colleague->can('employees.manage'));
        $this->assertFalse($colleague->can('employees.private'));
        $this->assertFalse($colleague->can('equipment.journal'));
        $this->assertFalse($colleague->can('directories.view'));
    }

    public function test_a_right_opens_the_page_it_names_and_only_that_page()
    {
        $this->actingAs($this->withRights('equipment.view', 'equipment.journal'));

        $this->get('/equipment')->assertOk();
        $this->get('/equipment/journal')->assertOk();

        // The fleet is theirs to read, not to change; the staff is neither.
        $this->get('/equipment/create')->assertForbidden();
        $this->get('/employees')->assertForbidden();
        $this->get('/directories/roles')->assertForbidden();
    }

    public function test_reading_a_directory_and_changing_it_are_separate_rights()
    {
        $reader = $this->withRights('directories.view');

        $this->actingAs($reader)->get('/directories/roles')->assertOk();
        $this->actingAs($reader)->post('/directories/positions', ['name' => 'Хакер'])->assertForbidden();

        $editor = $this->withRights('directories.view', 'directories.manage');
        $this->actingAs($editor)->post('/directories/positions', ['name' => 'Аналитик данных'])->assertRedirect();
    }

    public function test_moving_somebody_about_and_striking_them_out_are_separate_rights()
    {
        $colleague = User::factory()->create();
        $mover = $this->withRights('employees.view', 'employees.status');

        $this->actingAs($mover)->post("/employees/{$colleague->id}/fire", ['date' => '2026-09-01'])->assertRedirect();
        $this->actingAs($mover)->delete("/employees/{$colleague->id}")->assertForbidden();
        $this->assertNotNull(User::find($colleague->id));

        $this->actingAs($this->withRights('employees.view', 'employees.delete'))
            ->delete("/employees/{$colleague->id}")
            ->assertRedirect();
        $this->assertNull(User::find($colleague->id));
    }

    public function test_a_right_given_to_one_person_beats_their_position()
    {
        $colleague = $this->withRights('employees.view');

        $this->actingAs($colleague)->get('/equipment/journal')->assertForbidden();

        PermissionOverride::create(['user_id' => $colleague->id, 'permission' => 'equipment.journal', 'allowed' => true]);

        $this->actingAs($colleague->fresh())->get('/equipment/journal')->assertOk();
    }

    public function test_a_right_taken_from_one_person_beats_their_position_too()
    {
        $colleague = $this->withRights('employees.view', 'employees.private');

        $this->assertTrue($colleague->can('employees.private'));

        PermissionOverride::create(['user_id' => $colleague->id, 'permission' => 'employees.private', 'allowed' => false]);

        $this->assertFalse($colleague->fresh()->can('employees.private'));
        // What the position gives is untouched: the exception is about this person.
        $this->assertTrue($colleague->fresh()->can('employees.view'));
    }

    public function test_an_administrator_passes_every_check_without_a_single_right()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->assertEmpty($admin->getAllPermissions());

        foreach (Access::keys() as $key) {
            $this->assertTrue($admin->can($key), "администратор должен проходить {$key}");
        }
    }

    public function test_only_a_system_administrator_opens_the_access_page()
    {
        $role = Role::findByName('analyst');

        $this->actingAs($this->sysadmin())
            ->get('/directories/access')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('directories/access')
                ->has('sections', count(Access::SECTIONS))
                ->where('roles', fn ($roles) => collect($roles)->firstWhere('name', 'admin')['everything'] === true)
            );

        // Not even an administrator, who holds every right there is.
        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->get('/directories/access')
            ->assertForbidden();

        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->put("/directories/access/{$role->id}", ['permissions' => ['employees.manage']])
            ->assertForbidden();
    }

    public function test_the_access_page_saves_what_a_position_may_do()
    {
        $role = Role::findByName('analyst');

        $this->actingAs($this->sysadmin())
            ->put("/directories/access/{$role->id}", ['permissions' => ['employees.view', 'employees.private']])
            ->assertRedirect();

        $this->assertSame(['employees.private', 'employees.view'], $role->fresh()->permissions->pluck('name')->sort()->values()->all());

        // An unknown right is refused, and the access roles are not editable at
        // all: they answer yes to everything whatever the table holds.
        $this->actingAs($this->sysadmin())
            ->put("/directories/access/{$role->id}", ['permissions' => ['employees.everything']])
            ->assertSessionHasErrors('permissions.0');

        $this->actingAs($this->sysadmin())
            ->put('/directories/access/'.Role::findByName('admin')->id, ['permissions' => []])
            ->assertForbidden();
    }

    public function test_a_personal_exception_is_made_and_called_off_from_the_card()
    {
        $colleague = $this->withRights('employees.view');
        $sysadmin = $this->sysadmin();

        $this->actingAs($sysadmin)
            ->put("/employees/{$colleague->id}/access", ['permission' => 'equipment.journal', 'allowed' => true])
            ->assertRedirect();
        $this->assertTrue($colleague->fresh()->can('equipment.journal'));

        $this->actingAs($sysadmin)
            ->put("/employees/{$colleague->id}/access", ['permission' => 'equipment.journal', 'allowed' => false])
            ->assertRedirect();
        $this->assertFalse($colleague->fresh()->can('equipment.journal'));

        // Nothing at all means "back to whatever the position says".
        $this->actingAs($sysadmin)
            ->put("/employees/{$colleague->id}/access", ['permission' => 'equipment.journal', 'allowed' => null])
            ->assertRedirect();
        $this->assertSame(0, PermissionOverride::count());
        $this->assertFalse($colleague->fresh()->can('equipment.journal'));

        // And it is the system administrator's to make.
        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->put("/employees/{$colleague->id}/access", ['permission' => 'equipment.journal', 'allowed' => true])
            ->assertForbidden();
    }

    public function test_the_card_shows_a_system_administrator_where_each_right_comes_from()
    {
        $colleague = $this->withRights('employees.view', 'employees.private');
        PermissionOverride::create(['user_id' => $colleague->id, 'permission' => 'employees.private', 'allowed' => false]);
        PermissionOverride::create(['user_id' => $colleague->id, 'permission' => 'equipment.journal', 'allowed' => true]);

        $right = fn (array $rights, string $key) => collect($rights)->firstWhere('key', $key);

        $this->actingAs($this->sysadmin())
            ->get("/employees/{$colleague->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('access.everything', false)
                ->where('access.rights', fn ($rights) => $right($rights->all(), 'employees.private') === ['key' => 'employees.private', 'position' => true, 'override' => false]
                    && $right($rights->all(), 'equipment.journal') === ['key' => 'equipment.journal', 'position' => false, 'override' => true])
            );

        // Nobody else is shown any of it, not even an administrator.
        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->get("/employees/{$colleague->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('access', null));
    }

    public function test_everybody_reaches_their_own_card_without_a_single_right()
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->get("/employees/{$employee->id}")->assertOk();

        // Somebody else's takes the right to read the staff.
        $this->actingAs($employee)->get('/employees/'.User::factory()->create()->id)->assertForbidden();
    }

    public function test_a_position_is_created_with_its_rights_in_one_go()
    {
        $this->actingAs($this->sysadmin())
            ->post('/directories/roles', ['title' => 'Кладовщик', 'permissions' => ['equipment.view', 'equipment.manage']])
            ->assertRedirect();

        $role = Role::findByName('kladovschik');
        $this->assertSame(['equipment.manage', 'equipment.view'], $role->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_a_position_created_without_a_word_about_rights_may_look_around()
    {
        // An administrator is not offered the list, so the position starts with
        // what every position carries rather than with nothing at all.
        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->post('/directories/roles', ['title' => 'Курьер'])
            ->assertRedirect();

        $this->assertSame(Access::DEFAULTS, Role::findByName('kurer')->permissions->pluck('name')->all());
    }

    public function test_the_rights_of_a_position_are_edited_from_its_own_dialog()
    {
        $role = Role::findByName('analyst');

        $this->actingAs($this->sysadmin())
            ->put("/directories/roles/{$role->id}", ['title' => 'Аналитик', 'permissions' => ['employees.view', 'employees.private']])
            ->assertRedirect();

        $this->assertSame(['employees.private', 'employees.view'], $role->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_renaming_a_position_leaves_its_rights_where_they_are()
    {
        $role = Role::findByName('analyst');
        $held = $role->permissions->pluck('name')->sort()->values()->all();

        // An administrator renames positions all day; deciding what they open is
        // not theirs, so a list sent by one changes nothing.
        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->put("/directories/roles/{$role->id}", ['title' => 'Аналитик данных', 'permissions' => ['directories.manage']])
            ->assertRedirect();

        $this->assertSame('Аналитик данных', $role->fresh()->title);
        $this->assertSame($held, $role->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_the_position_directory_offers_the_rights_to_whoever_decides_on_them()
    {
        $this->actingAs($this->sysadmin())
            ->get('/directories/roles')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('canManageAccess', true)
                ->has('sections', count(Access::SECTIONS))
                ->where('items', fn ($items) => collect(collect($items)->firstWhere('name', 'analyst')['permissions'])->all() === Access::DEFAULTS)
            );

        $this->actingAs(User::factory()->create()->assignRole('admin'))
            ->get('/directories/roles')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canManageAccess', false));
    }

    public function test_the_search_only_offers_what_the_viewer_may_open()
    {
        $colleague = $this->withRights('equipment.view');
        User::factory()->create(['surname' => 'Рахимов', 'name' => 'Фарход']);

        $this->actingAs($colleague)
            ->getJson('/search?q='.urlencode('Рахимов'))
            ->assertOk()
            ->assertJsonCount(0, 'employees')
            ->assertJsonCount(0, 'positions');
    }
}
