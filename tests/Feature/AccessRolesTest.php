<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Who may hand out access to the system.
 *
 * An administrator can do everything in it, which is why appointing one is not
 * theirs to do: otherwise any of them could promote a colleague, or strip the
 * person who appointed them. That side of things belongs to a system
 * administrator alone.
 */
class AccessRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function sysadmin(): User
    {
        return User::factory()->create()->assignRole('sysadmin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function card(User $employee, array $overrides = []): array
    {
        return [
            'surname' => $employee->surname,
            'name' => $employee->name,
            'sex' => $employee->sex,
            'roles' => $employee->roles->pluck('name')->all(),
            'positions' => [],
            'departments' => [],
            ...$overrides,
        ];
    }

    public function test_a_system_administrator_passes_every_check_like_an_administrator()
    {
        $this->actingAs($this->sysadmin());

        $this->get('/directories')->assertRedirect('/directories/roles');
        $this->get('/employees')->assertOk();
    }

    public function test_an_administrator_cannot_appoint_another_one()
    {
        $colleague = User::factory()->create()->assignRole('analyst');

        $this->actingAs($this->admin())
            ->put("/employees/{$colleague->id}/personal", $this->card($colleague, ['roles' => ['analyst', 'admin']]))
            ->assertSessionHasErrors('roles.1');

        $this->assertFalse($colleague->refresh()->hasRole('admin'));
    }

    public function test_a_system_administrator_gives_and_takes_the_rights_away()
    {
        $colleague = User::factory()->create()->assignRole('analyst');
        $sysadmin = $this->sysadmin();

        $this->actingAs($sysadmin)
            ->put("/employees/{$colleague->id}/personal", $this->card($colleague, ['roles' => ['analyst', 'admin']]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($colleague->refresh()->hasRole('admin'));

        $this->actingAs($sysadmin)
            ->put("/employees/{$colleague->id}/personal", $this->card($colleague, ['roles' => ['analyst']]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($colleague->refresh()->hasRole('admin'));
    }

    public function test_an_administrator_cannot_touch_the_roles_of_another_one()
    {
        $other = User::factory()->create();
        $other->assignRole(['admin', 'analyst']);

        // Not even an ordinary role: their card is not an administrator's to edit.
        $this->actingAs($this->admin())
            ->put("/employees/{$other->id}/personal", $this->card($other, ['roles' => ['admin', 'analyst', 'translator']]))
            ->assertSessionHasErrors('roles');

        $this->assertFalse($other->refresh()->hasRole('translator'));

        // And what is left of their access stays where it is.
        $this->actingAs($this->admin())
            ->put("/employees/{$other->id}/personal", $this->card($other, ['roles' => ['analyst']]))
            ->assertSessionHasErrors('roles');

        $this->assertTrue($other->refresh()->hasRole('admin'));
    }

    public function test_the_rest_of_an_administrators_card_is_still_editable()
    {
        $other = User::factory()->create(['surname' => 'Азимов']);
        $other->assignRole('admin');

        // Only the roles are out of reach; the card itself is ordinary work.
        $this->actingAs($this->admin())
            ->put("/employees/{$other->id}/personal", $this->card($other, ['surname' => 'Азимова']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Азимова', $other->refresh()->surname);
    }

    public function test_nobody_takes_their_own_access_away()
    {
        $sysadmin = $this->sysadmin();

        $this->actingAs($sysadmin)
            ->put("/employees/{$sysadmin->id}/personal", $this->card($sysadmin, ['roles' => []]))
            ->assertSessionHasErrors('roles');

        $this->assertTrue($sysadmin->refresh()->hasRole('sysadmin'));
    }

    public function test_only_whoever_may_hand_access_out_is_told_so()
    {
        // What the forms go by: an administrator is not offered the access roles
        // at all, so nothing is picked that the request would only refuse.
        $this->actingAs($this->sysadmin())
            ->get('/employees/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.manageAccess', true));

        $this->actingAs($this->admin())
            ->get('/employees/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.manageAccess', false));
    }

    public function test_neither_access_role_can_be_deleted_from_the_directory()
    {
        $this->actingAs($this->sysadmin());

        foreach (['sysadmin', 'admin'] as $name) {
            $role = Role::findByName($name);

            $this->delete("/directories/roles/{$role->id}")->assertForbidden();
            $this->assertNotNull(Role::find($role->id));
        }
    }
}
