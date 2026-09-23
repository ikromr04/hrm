<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Notifications\AccountCreated;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use ReflectionClass;
use Tests\TestCase;

/**
 * Putting a new colleague on the books, from the form that opens as a page of
 * its own. Its first step creates the account they sign in with and files them
 * under a role, a position and a department; the steps after it fill the rest
 * of the profile in and may each be skipped.
 */
class EmployeeCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'surname' => 'Азимова',
            'name' => 'Нилуфар',
            'patronymic' => 'Рустамовна',
            'sex' => 'female',
            'email' => 'nilufar@evolet.tj',
            'birth_date' => '1990-04-17',
            'birth_place' => 'г. Худжанд',
            'citizenship' => 'Таджикистан',
            'hired_at' => '2026-03-02',
            'roles' => [],
            'positions' => [],
            'departments' => [],
            ...$overrides,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    public function test_the_form_is_a_page_of_its_own_open_to_managers_only()
    {
        $this->actingAs($this->admin())
            ->get('/employees/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('employees/create')
                ->has('options.roles')
                ->has('options.languages')
                ->has('options.stock')
            );

        $this->actingAs(User::factory()->create())->get('/employees/create')->assertForbidden();
    }

    public function test_an_admin_adds_a_colleague_and_lands_on_their_profile()
    {
        Notification::fake();
        $position = Position::query()->firstOrFail();
        $department = Department::create(['name' => 'Отдел разработки']);

        $response = $this->actingAs($this->admin())->post('/employees', $this->payload([
            'roles' => ['specialist'],
            'positions' => [$position->id],
            'departments' => [$department->id],
        ]));

        $employee = User::firstWhere('email', 'nilufar@evolet.tj');
        $response->assertRedirect("/employees/{$employee->id}");

        $this->assertSame('Азимова', $employee->surname);
        $this->assertSame('female', $employee->sex);
        $this->assertSame('active', $employee->status);
        $this->assertSame('2026-03-02', $employee->details->hired_at->toDateString());
        // The first step also carries the personal facts of the profile's card.
        $this->assertSame('1990-04-17', $employee->details->birth_date->toDateString());
        $this->assertSame('г. Худжанд', $employee->details->birth_place);
        $this->assertSame('Таджикистан', $employee->details->citizenship);
        $this->assertTrue($employee->hasRole('specialist'));
        $this->assertSame([$position->id], $employee->positions->pluck('id')->all());
        $this->assertSame([$department->id], $employee->departments->pluck('id')->all());

        Notification::assertSentTo($employee, AccountCreated::class);
    }

    public function test_the_password_is_generated_and_mailed_rather_than_typed()
    {
        Notification::fake();

        // A password sent from the browser is ignored: the one that works is
        // the one generated here and mailed to the new colleague.
        $this->actingAs($this->admin())->post('/employees', $this->payload(['password' => 'подсунутый-пароль']));

        $employee = User::firstWhere('email', 'nilufar@evolet.tj');
        $this->assertFalse(Hash::check('подсунутый-пароль', $employee->password));

        Notification::assertSentTo($employee, AccountCreated::class, function (AccountCreated $mail) use ($employee) {
            $password = (new ReflectionClass($mail))->getProperty('password')->getValue($mail);
            $body = implode(' ', $mail->toMail($employee)->introLines);

            // Between eight and twelve characters, and it is in the letter.
            $this->assertGreaterThanOrEqual(8, strlen($password));
            $this->assertLessThanOrEqual(12, strlen($password));
            $this->assertTrue(Hash::check($password, $employee->password));

            return str_contains($body, $password) && str_contains($body, $employee->email);
        });
    }

    public function test_the_form_needs_a_name_and_a_free_address()
    {
        $taken = User::factory()->create(['email' => 'taken@evolet.tj']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/employees', [])
            ->assertSessionHasErrors(['surname', 'name', 'sex', 'email']);

        $this->actingAs($admin)->post('/employees', $this->payload(['email' => $taken->email]))
            ->assertSessionHasErrors('email');

        // A hire date in the future would read as negative service.
        $this->actingAs($admin)->post('/employees', $this->payload(['hired_at' => now()->addWeek()->toDateString()]))
            ->assertSessionHasErrors('hired_at');

        $this->assertSame(2, User::count());
    }

    public function test_the_wizard_keeps_the_new_colleague_on_the_list_to_carry_on()
    {
        Notification::fake();

        // The first step of the wizard asks to continue: it stays where it is
        // and is handed whom it has just created, to fill the rest in.
        $this->actingAs($this->admin())
            ->from('/employees')
            ->post('/employees', $this->payload(['continue' => true]))
            ->assertRedirect('/employees')
            ->assertSessionHas('employee', fn (array $employee) => $employee['name'] === 'Азимова Нилуфар');
    }

    public function test_an_admin_can_add_another_admin()
    {
        // Only admins reach this route at all, and the request guards the role
        // a second time, the way the profile's form does.
        $this->actingAs($this->admin())
            ->post('/employees', $this->payload(['roles' => ['admin']]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::firstWhere('email', 'nilufar@evolet.tj')->hasRole('admin'));
    }

    public function test_an_unknown_role_position_or_department_is_refused()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/employees', $this->payload(['roles' => ['ceo']]))->assertSessionHasErrors('roles.0');
        $this->actingAs($admin)->post('/employees', $this->payload(['positions' => [9999]]))->assertSessionHasErrors('positions.0');
        $this->actingAs($admin)->post('/employees', $this->payload(['departments' => [9999]]))->assertSessionHasErrors('departments.0');

        $this->assertSame(1, User::count());
    }

    public function test_an_ordinary_colleague_cannot_add_anybody()
    {
        $this->actingAs(User::factory()->create())
            ->post('/employees', $this->payload())
            ->assertForbidden();

        $this->assertNull(User::firstWhere('email', 'nilufar@evolet.tj'));
    }
}
