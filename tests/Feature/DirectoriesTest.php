<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DirectoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_only_admins_can_open_or_change_directories()
    {
        $this->get('/directories/roles')->assertRedirect('/login');

        $employee = User::factory()->create();
        $this->actingAs($employee);

        $this->get('/directories/roles')->assertForbidden();
        $this->get('/directories/positions')->assertForbidden();
        $this->get('/directories/departments')->assertForbidden();
        $this->post('/directories/positions', ['name' => 'Хакер'])->assertForbidden();
        $this->assertSame(0, Position::count());

        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('auth.can.manageDirectories', false));
        $this->actingAs($this->admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('auth.can.manageDirectories', true));
    }

    public function test_admin_sees_each_directory_with_employee_counts()
    {
        $position = Position::create(['name' => 'Переводчик']);
        User::factory(2)->create()->each(fn (User $u) => $u->positions()->attach($position));
        $this->actingAs($this->admin);

        $this->get('/directories')->assertRedirect('/directories/roles');
        $this->get('/directories/roles')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('directories/roles')
            ->has('items', 24)
            ->where('items', fn ($items) => collect($items)->firstWhere('name', 'admin')['protected'] === true)
        );
        $this->get('/directories/positions')->assertInertia(fn (Assert $page) => $page
            ->component('directories/positions')
            ->where('items.0.name', 'Переводчик')
            ->where('items.0.users_count', 2)
        );
        $this->get('/directories/departments')->assertInertia(fn (Assert $page) => $page->component('directories/departments'));
    }

    public function test_roles_get_a_generated_key_that_survives_renaming()
    {
        $this->actingAs($this->admin);

        $this->post('/directories/roles', ['title' => 'Главный бухгалтер'])->assertSessionHasNoErrors();
        $role = Role::firstWhere('title', 'Главный бухгалтер');
        $this->assertSame('glavnyy-buhgalter', $role->name);

        $this->put("/directories/roles/{$role->id}", ['title' => 'Главный бухгалтер компании'])->assertSessionHasNoErrors();
        $this->assertSame('glavnyy-buhgalter', $role->fresh()->name);
        $this->assertSame('Главный бухгалтер компании', $role->fresh()->title);

        $this->post('/directories/roles', ['title' => 'Главный бухгалтер компании'])->assertSessionHasErrors('title');
    }

    public function test_deleting_a_role_removes_it_from_employees_but_admin_is_protected()
    {
        $this->actingAs($this->admin);
        $intern = Role::findByName('intern');
        $employee = User::factory()->create();
        $employee->assignRole($intern);

        $this->delete("/directories/roles/{$intern->id}")->assertSessionHasNoErrors();
        $this->assertNull(Role::find($intern->id));
        $this->assertCount(0, $employee->fresh()->roles);

        $this->delete('/directories/roles/'.Role::findByName('admin')->id)->assertForbidden();
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_positions_can_be_added_renamed_and_deleted()
    {
        $this->actingAs($this->admin);

        $this->post('/directories/positions', ['name' => 'Юрист'])->assertSessionHasNoErrors();
        $position = Position::firstWhere('name', 'Юрист');
        $employee = User::factory()->create();
        $employee->positions()->attach($position);

        $this->put("/directories/positions/{$position->id}", ['name' => 'Юрисконсульт'])->assertSessionHasNoErrors();
        $this->assertSame('Юрисконсульт', $position->fresh()->name);

        $this->post('/directories/positions', ['name' => 'Юрисконсульт'])->assertSessionHasErrors('name');
        $this->post('/directories/positions', ['name' => ''])->assertSessionHasErrors('name');

        $this->delete("/directories/positions/{$position->id}")->assertSessionHasNoErrors();
        $this->assertSame(0, Position::count());
        $this->assertCount(0, $employee->fresh()->positions);
    }

    public function test_departments_can_be_nested_but_not_in_a_cycle()
    {
        $this->actingAs($this->admin);

        $this->post('/directories/departments', ['name' => 'Департамент', 'parent_id' => null])->assertSessionHasNoErrors();
        $parent = Department::firstWhere('name', 'Департамент');
        $this->post('/directories/departments', ['name' => 'Отдел', 'parent_id' => $parent->id])->assertSessionHasNoErrors();
        $child = Department::firstWhere('name', 'Отдел');
        $this->assertSame($parent->id, $child->parent_id);

        $this->put("/directories/departments/{$parent->id}", ['name' => 'Департамент', 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');
        $this->put("/directories/departments/{$parent->id}", ['name' => 'Департамент', 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');
        $this->assertNull($parent->fresh()->parent_id);

        $this->put("/directories/departments/{$child->id}", ['name' => 'Отдел продаж', 'parent_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($child->fresh()->parent_id);
        $this->assertSame('Отдел продаж', $child->fresh()->name);
    }

    public function test_deleting_a_department_moves_its_sub_departments_up()
    {
        $this->actingAs($this->admin);
        $root = Department::create(['name' => 'Корень']);
        $middle = Department::create(['name' => 'Середина', 'parent_id' => $root->id]);
        $leaf = Department::create(['name' => 'Лист', 'parent_id' => $middle->id]);
        $employee = User::factory()->create();
        $employee->departments()->attach($middle);

        $this->delete("/directories/departments/{$middle->id}")->assertSessionHasNoErrors();

        $this->assertNull(Department::find($middle->id));
        $this->assertSame($root->id, $leaf->fresh()->parent_id);
        $this->assertCount(0, $employee->fresh()->departments);
    }
}
