<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_working_and_former_employees_with_details()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(77, User::count());
        $this->assertSame(77, UserDetail::count());
        // 1 admin + 67 working + 6 fired + 3 transferred.
        $this->assertSame(68, User::active()->count());
        $this->assertSame(6, User::where('status', 'fired')->count());
        $this->assertSame(3, User::where('status', 'transferred')->count());
        $this->assertSame(1, User::role('admin')->count());
        $this->assertSame(0, User::whereNull('surname')->orWhereNull('sex')->count());
        $this->assertSame(0, User::where('email', 'not like', '%@evolet.test')->count());
        $this->assertSame(0, UserDetail::whereNull('nationality')->count());

        $this->assertSame(0, User::has('children')->whereHas('details', fn ($q) => $q->where('marital_status', 'single'))->count());

        User::with('details')->get()->each(fn (User $user) => $this->assertContains(
            $user->details->nationality,
            $user->sex === 'female' ? ['таджичка', 'узбечка', 'русская'] : ['таджик', 'узбек', 'русский'],
        ));
    }

    public function test_roles_are_seeded_with_russian_titles_and_every_employee_has_one()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(24, Role::count());
        $this->assertSame('Администратор', Role::findByName('admin')->title);
        $this->assertSame('Руководитель Департамента', Role::findByName('department-head')->title);

        $this->assertSame(0, User::doesntHave('roles')->count());
        // Many-to-many: some employees hold a second position, never more.
        $this->assertSame(0, User::has('roles', '>', 2)->count());
        $this->assertSame(['admin'], User::role('admin')->first()->getRoleNames()->all());
        $this->assertSame(2, User::role('department-head')->count());
        $this->assertSame(6, User::role('division-head')->count());
    }

    public function test_admin_can_log_in_and_passes_every_authorization_check()
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::firstWhere('email', 'admin@evolet.test');
        $employee = User::firstWhere('email', 'd.nazarova@evolet.test');

        $this->assertTrue(Auth::validate(['email' => 'admin@evolet.test', 'password' => 'password']));
        $this->assertTrue(Gate::forUser($admin)->allows('viewPrivateDetails', $employee));
        $this->assertFalse(Gate::forUser($employee)->allows('viewPrivateDetails', $admin));
    }

    public function test_seeder_can_run_twice_without_duplicates()
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(77, User::count());
        $this->assertSame(77, UserDetail::count());
        $this->assertSame(1, User::role('admin')->count());
    }
}
