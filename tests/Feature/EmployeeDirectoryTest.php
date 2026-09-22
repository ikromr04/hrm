<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_directory_lists_real_users_ten_per_page()
    {
        User::factory(12)->create();
        $this->actingAs(User::first());

        $this->get('/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/index')
                ->has('employees.data', 10)
                ->where('employees.total', 12)
                ->where('total', 12)
                ->has('options.positions', 24)
                ->where('perPage', 10)
            );

        $this->get('/employees?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 2));
    }

    public function test_colleagues_see_public_fields_only()
    {
        $colleague = User::factory()->has(UserDetail::factory(), 'details')->create(['surname' => 'Азимова']);
        $colleague->assignRole('translator');
        UserChild::factory()->for($colleague)->create();
        $viewer = User::factory()->create(['surname' => 'Шарипов']);

        $this->actingAs($viewer)
            ->get('/employees')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.surname', 'Азимова')
                ->where('employees.data.0.roles', ['Переводчик'])
                ->where('employees.data.0.private', null)
                ->has('employees.data.0', fn (Assert $row) => $row
                    ->hasAll(['id', 'name', 'surname', 'patronymic', 'avatar', 'sex', 'email', 'roles', 'departments', 'private'])
                    ->missing('details')
                    ->missing('children')
                )
            );
    }

    public function test_employee_sees_their_own_private_details()
    {
        $user = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserChild::factory(2)->for($user)->create();

        $this->actingAs($user)
            ->get('/employees')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.private.phone', $user->details->phone)
                ->where('employees.data.0.private.sos_phone', $user->details->sos_phone)
                ->where('employees.data.0.private.hired_at', $user->details->hired_at->toDateString())
                ->has('employees.data.0.private.children', 2)
                ->missing('employees.data.0.private.passport_number')
            );
    }

    public function test_admin_sees_everyones_private_details()
    {
        $admin = User::factory()->create(['surname' => 'Яхёев']);
        $admin->assignRole('admin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create(['surname' => 'Азимов']);

        $this->actingAs($admin)
            ->get('/employees')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.surname', 'Азимов')
                ->where('employees.data.0.private.nationality', $employee->details->nationality)
                ->where('employees.data.0.private.citizenship', 'Таджикистан')
            );
    }

    public function test_search_matches_surname_name_patronymic_and_email()
    {
        User::factory()->create(['surname' => 'Шарипов', 'name' => 'Алишер', 'email' => 'a.sharipov@evolet.test']);
        User::factory()->create(['surname' => 'Назарова', 'name' => 'Дилноза', 'email' => 'd.nazarova@evolet.test']);
        $this->actingAs(User::first());

        $this->get('/employees?search=Шарип')
            ->assertInertia(fn (Assert $page) => $page
                ->has('employees.data', 1)
                ->where('employees.data.0.surname', 'Шарипов')
                ->where('filters.search', 'Шарип')
            );

        $this->get('/employees?search=d.nazarova')
            ->assertInertia(fn (Assert $page) => $page->where('employees.data.0.surname', 'Назарова'));
    }

    public function test_position_filter_accepts_several_positions()
    {
        User::factory(3)->create()->each->assignRole('intern');
        User::factory(2)->create()->each->assignRole('analyst');
        $this->actingAs(User::first());

        $this->get('/employees?position[]=intern')
            ->assertInertia(fn (Assert $page) => $page
                ->has('employees.data', 3)
                ->where('employees.data.0.roles', ['Стажер'])
                ->where('filters.position', ['intern'])
            );

        $this->get('/employees?position[]=intern&position[]=analyst')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 5));
    }

    public function test_employee_with_several_positions_shows_all_and_matches_any()
    {
        $viewer = User::factory()->create(['surname' => 'Бобоев']);
        $both = User::factory()->create(['surname' => 'Азимов']);
        $both->assignRole(['translator', 'copywriter']);
        $this->actingAs($viewer);

        $this->get('/employees')
            ->assertInertia(fn (Assert $page) => $page->where('employees.data.0.roles', ['Копирайтер', 'Переводчик']));

        foreach (['translator', 'copywriter'] as $position) {
            $this->get('/employees?position[]='.$position)
                ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)->where('employees.data.0.id', $both->id));
        }
    }

    public function test_rows_list_departments_with_their_path()
    {
        $marketing = Department::create(['name' => 'Департамент маркетинга']);
        $design = Department::create(['name' => 'Отдел Дизайна', 'parent_id' => $marketing->id]);
        $user = User::factory()->create();
        $user->departments()->attach([$design->id, $marketing->id]);

        $this->actingAs($user)
            ->get('/employees')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.departments.0.name', 'Департамент маркетинга')
                ->where('employees.data.0.departments.1.path', 'Департамент маркетинга › Отдел Дизайна')
                ->where('options.departments', [
                    ['id' => $marketing->id, 'name' => 'Департамент маркетинга', 'depth' => 0],
                    ['id' => $design->id, 'name' => 'Отдел Дизайна', 'depth' => 1],
                ])
            );
    }

    public function test_department_filter_includes_sub_departments()
    {
        $marketing = Department::create(['name' => 'Департамент маркетинга']);
        $design = Department::create(['name' => 'Отдел Дизайна', 'parent_id' => $marketing->id]);
        $finance = Department::create(['name' => 'Департамент финансов']);

        $head = User::factory()->create();
        $head->departments()->attach($marketing);
        $designer = User::factory()->create();
        $designer->departments()->attach($design);
        User::factory()->create()->departments()->attach($finance);
        $this->actingAs(User::factory()->create());

        $this->get('/employees?department[]='.$marketing->id)
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 2)->where('filters.department', [$marketing->id]));

        $this->get('/employees?department[]='.$design->id)
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)->where('employees.data.0.id', $designer->id));

        $this->get('/employees?department[]=999999')->assertSessionHasErrors('department.0');
    }

    public function test_directory_sorts_by_department()
    {
        $a = Department::create(['name' => 'Архив']);
        $b = Department::create(['name' => 'Бухгалтерия']);
        $viewer = User::factory()->create();
        $inB = User::factory()->create();
        $inB->departments()->attach($b);
        $inA = User::factory()->create();
        $inA->departments()->attach($a);
        $this->actingAs($viewer);

        // The viewer has no department and sorts first.
        $this->get('/employees?sort=department')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.1.id', $inA->id)
                ->where('employees.data.2.id', $inB->id)
            );
    }

    public function test_directory_sorts_by_public_columns()
    {
        $viewer = User::factory()->create(['surname' => 'Бобоев']);
        User::factory()->create(['surname' => 'Азимов'])->assignRole('translator');
        User::factory()->create(['surname' => 'Юсупов'])->assignRole('analyst');
        $this->actingAs($viewer);

        $this->get('/employees')
            ->assertInertia(fn (Assert $page) => $page
                ->where('sort.key', 'name')
                ->where('employees.data.0.surname', 'Азимов')
                ->where('sortable', ['name', 'position', 'department', 'sex'])
            );

        $this->get('/employees?sort=name&direction=desc')
            ->assertInertia(fn (Assert $page) => $page->where('employees.data.0.surname', 'Юсупов'));

        // Аналитик < Переводчик; the viewer without a position sorts first.
        $this->get('/employees?sort=position')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.1.roles', ['Аналитик'])
                ->where('employees.data.2.roles', ['Переводчик'])
            );
    }

    public function test_employees_cannot_sort_by_private_columns()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?sort=birth_date')->assertSessionHasErrors('sort');
        $this->get('/employees?sort=hired_at')->assertSessionHasErrors('sort');
    }

    public function test_admin_can_sort_by_private_columns()
    {
        $admin = User::factory()->has(UserDetail::factory(['birth_date' => '1990-01-01']), 'details')->create();
        $admin->assignRole('admin');
        $oldest = User::factory()->has(UserDetail::factory(['birth_date' => '1970-05-05']), 'details')->create();
        $youngest = User::factory()->has(UserDetail::factory(['birth_date' => '2001-02-02']), 'details')->create();
        UserChild::factory(3)->for($youngest)->create();

        $this->actingAs($admin);

        $this->get('/employees?sort=birth_date')
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.id', $oldest->id)
                ->where('employees.data.2.id', $youngest->id)
                ->has('sortable', 12)
            );

        $this->get('/employees?sort=children&direction=desc')
            ->assertInertia(fn (Assert $page) => $page->where('employees.data.0.id', $youngest->id));
    }

    public function test_per_page_can_be_chosen_from_the_allowed_options()
    {
        User::factory(30)->create();
        $this->actingAs(User::first());

        $this->get('/employees?per_page=25')
            ->assertInertia(fn (Assert $page) => $page
                ->where('perPage', 25)
                ->has('employees.data', 25)
                ->where('perPageOptions', [10, 25, 50, 100])
            );

        $this->get('/employees?per_page=7')->assertSessionHasErrors('per_page');
    }

    public function test_sex_filter()
    {
        User::factory(2)->create(['sex' => 'female']);
        User::factory(3)->create(['sex' => 'male']);
        $this->actingAs(User::first());

        $this->get('/employees?sex=female')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 2)->where('filters.sex', 'female'));
    }

    public function test_employees_cannot_filter_by_private_fields()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?birth_from=1990-01-01')->assertSessionHasErrors('birth_from');
        $this->get('/employees?nationality[]=таджик')->assertSessionHasErrors('nationality');
        $this->get('/employees?phone=90')->assertSessionHasErrors('phone');
        $this->get('/employees?children[]=0')->assertSessionHasErrors('children');

        $this->get('/employees')->assertInertia(fn (Assert $page) => $page
            ->where('privateAccess', false)
            ->where('options.nationalities', [])
        );
    }

    public function test_admin_can_filter_by_private_fields()
    {
        $admin = User::factory()->has(UserDetail::factory(['birth_date' => '1960-01-01', 'nationality' => 'узбек']), 'details')->create();
        $admin->assignRole('admin');
        $young = User::factory()->has(UserDetail::factory([
            'birth_date' => '2000-06-15',
            'nationality' => 'таджичка',
            'phone' => '+992901112233',
            'marital_status' => 'married',
            'hired_at' => '2024-03-01',
        ]), 'details')->create();
        UserChild::factory(2)->for($young)->create();

        $this->actingAs($admin);

        $only = fn (string $query) => $this->get("/employees?{$query}")
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)->where('employees.data.0.id', $young->id));

        $only('birth_from=1990-01-01');
        $only('nationality[]=таджичка');
        $only('phone=111 22 33');
        $only('children[]=2');
        $only('hired_from=2024-01-01&hired_to=2024-12-31');

        $this->get('/employees?children[]=0')
            ->assertInertia(fn (Assert $page) => $page->has('employees.data', 1)->where('employees.data.0.id', $admin->id));

        $this->get('/employees')->assertInertia(fn (Assert $page) => $page
            ->where('privateAccess', true)
            ->where('options.nationalities', ['таджичка', 'узбек'])
        );
    }

    public function test_unknown_position_is_rejected()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?position[]=wizard')->assertSessionHasErrors('position.0');
    }
}
