<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\EquipmentType;
use App\Models\Language;
use App\Models\Position;
use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use App\Models\UserEducation;
use App\Models\UserEquipment;
use App\Models\UserWorkExperience;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const EMPLOYEES = 67;

    /** People who have left: [status, how many, notes to pick from]. */
    public const FORMER = [
        ['fired', 6, ['По собственному желанию', 'По соглашению сторон', 'Истечение срока трудового договора', null]],
        ['transferred', 3, ['Эволет Европа', 'Эволет Узбекистан', 'Эволет Казахстан']],
    ];

    /**
     * Mock accounts for local development: one admin, 67 working employees
     * and a few who were fired or transferred.
     * Every account uses the password "password".
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->callOnce(RoleSeeder::class);
        $this->callOnce(DepartmentSeeder::class);
        $this->callOnce(PositionSeeder::class);
        $this->callOnce(LanguageSeeder::class);

        $admin = $this->account(['name' => 'Некруз', 'surname' => 'Абдуллоев', 'patronymic' => 'Саидович', 'sex' => 'male', 'email' => 'admin@evolet.test']);
        $admin->assignRole('admin');

        // Employees that are easy to remember when logging in.
        $fixed = [
            ['name' => 'Дилноза', 'surname' => 'Назарова', 'patronymic' => 'Рустамовна', 'sex' => 'female', 'email' => 'd.nazarova@evolet.test'],
            ['name' => 'Сухроб', 'surname' => 'Каримов', 'patronymic' => 'Азизович', 'sex' => 'male', 'email' => 's.karimov@evolet.test'],
            ['name' => 'Фарход', 'surname' => 'Рахимов', 'patronymic' => 'Джамшедович', 'sex' => 'male', 'email' => 'f.rakhimov@evolet.test'],
            ['name' => 'Мадина', 'surname' => 'Каримова', 'patronymic' => 'Умедовна', 'sex' => 'female', 'email' => 'm.karimova@evolet.test'],
            ['name' => 'Алишер', 'surname' => 'Шарипов', 'patronymic' => 'Далерович', 'sex' => 'male', 'email' => 'a.sharipov@evolet.test'],
        ];

        foreach ($fixed as $attributes) {
            $this->account($attributes);
        }

        $missing = self::EMPLOYEES - User::active()->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->count();

        if ($missing > 0) {
            User::factory($missing)->has(UserDetail::factory(), 'details')->create();
        }

        foreach (self::FORMER as [$status, $count, $notes]) {
            $missing = $count - User::where('status', $status)->count();

            if ($missing > 0) {
                User::factory($missing)
                    ->has(UserDetail::factory(), 'details')
                    ->state(fn () => [
                        'status' => $status,
                        'status_changed_at' => fake()->dateTimeBetween('-2 years', '-1 week'),
                        'status_note' => fake()->randomElement($notes),
                    ])
                    ->create();
            }
        }

        $this->assignRoles();
        $this->assignDepartments();
        $this->assignPositions();
        $this->assignDepartmentHeads();
        $this->assignLanguages();
        $this->addChildren();
        $this->addEducation();
        $this->addWorkExperience();
        $this->addEquipment();
    }

    /**
     * A workplace set for most of the working staff: a computer first, then the
     * peripherals that go with it, plus the odd printer or headset.
     */
    private function addEquipment(): void
    {
        $types = EquipmentType::all()->keyBy('name');

        if ($types->isEmpty()) {
            return;
        }

        // The mouse and keyboard only make sense next to a desktop computer.
        $desktopExtras = ['Монитор', 'Клавиатура', 'Мышь'];
        $occasional = ['Гарнитура', 'ИБП', 'МФУ', 'Планшет', 'Принтер'];

        User::doesntHave('equipment')->where('status', 'active')->get()->each(function (User $user) use ($types, $desktopExtras, $occasional) {
            if (! fake()->boolean(85)) {
                return;
            }

            $laptop = fake()->boolean(35);
            $names = $laptop
                ? ['Ноутбук', ...fake()->randomElements($desktopExtras, fake()->numberBetween(0, 1))]
                : ['Персональный компьютер', ...$desktopExtras];
            $names = [...$names, ...fake()->randomElements($occasional, fake()->numberBetween(0, 2))];

            foreach (array_unique($names) as $name) {
                if (! isset($types[$name])) {
                    continue;
                }

                UserEquipment::factory()->for($user)->ofType($types[$name])->create();
            }
        });
    }

    /**
     * About two in three worked somewhere before joining: one or two jobs
     * between their studies and their start date here.
     */
    private function addWorkExperience(): void
    {
        User::doesntHave('workExperiences')->with(['details', 'educations'])->get()->each(function (User $user) {
            $hired = $user->details?->hired_at;
            $from = $user->educations->min('graduated_year') ?? ($user->details?->birth_date?->year ?? 1990) + 22;

            if (! $hired || ! fake()->boolean(65) || $from >= $hired->year - 1) {
                return;
            }

            $year = $from;
            $month = fake()->numberBetween(6, 10);

            foreach (range(1, fake()->numberBetween(1, 2)) as $ignored) {
                if ($year * 12 + $month >= $hired->year * 12 + $hired->month - 12) {
                    break;
                }

                $job = UserWorkExperience::factory()->for($user)->between($year, $month, $hired->year, $hired->month)->create();
                // The next job starts a month or a few after this one ends.
                $next = $job->ended_year * 12 + $job->ended_month + fake()->numberBetween(1, 4) - 1;
                [$year, $month] = [intdiv($next, 12), $next % 12 + 1];
            }
        });
    }

    /**
     * Everyone has studied somewhere; about one in five went on to a second
     * degree or course.
     */
    private function addEducation(): void
    {
        User::doesntHave('educations')->with('details')->get()->each(function (User $user) {
            $first = UserEducation::factory()->for($user)->forBirthDate($user->details?->birth_date)->create();

            if (fake()->boolean(20) && $first->graduated_year) {
                UserEducation::factory()->for($user)->state(fn () => [
                    'started_year' => $first->graduated_year + fake()->numberBetween(0, 5),
                ])->state(fn (array $a) => [
                    'graduated_year' => (int) date('Y') >= $a['started_year'] + 2 ? $a['started_year'] + 2 : null,
                ])->create();
            }
        });
    }

    /**
     * Nearly everyone speaks Tajik and Russian; many know some English, and a
     * few speak another language as well.
     */
    private function assignLanguages(): void
    {
        $languages = Language::all()->keyBy('name');
        $level = fn (array $weights) => fake()->randomElement(array_merge(...array_map(
            fn (string $level, int $weight) => array_fill(0, $weight, $level),
            array_keys($weights),
            $weights,
        )));

        User::doesntHave('languages')->get()->each(function (User $user) use ($languages, $level) {
            $spoken = [
                'Таджикский' => $level(['advanced' => 9, 'intermediate' => 1]),
                'Русский' => $level(['advanced' => 6, 'intermediate' => 3, 'beginner' => 1]),
            ];

            if (fake()->boolean(65)) {
                $spoken['Английский'] = $level(['advanced' => 2, 'intermediate' => 4, 'beginner' => 4]);
            }

            if (fake()->boolean(20)) {
                $other = fake()->randomElement(array_diff(LanguageSeeder::LANGUAGES, array_keys($spoken)));
                $spoken[$other] = $level(['advanced' => 3, 'intermediate' => 3, 'beginner' => 4]);
            }

            $user->languages()->sync(collect($spoken)->mapWithKeys(fn (string $lvl, string $name) => [$languages[$name]->id => ['level' => $lvl]]));
        });
    }

    /**
     * Married employees get 0–3 children, born after the parent turned 20.
     */
    private function addChildren(): void
    {
        User::doesntHave('children')
            ->whereHas('details', fn ($q) => $q->where('marital_status', 'married'))
            ->with('details')
            ->get()
            ->each(function (User $user) {
                $earliest = $user->details->birth_date?->copy()->addYears(20)->max(now()->subYears(25));

                if (! $earliest || $earliest->isFuture()) {
                    return;
                }

                $count = fake()->numberBetween(0, 3);

                UserChild::factory($count)
                    ->for($user)
                    ->ofFamily($user->surname)
                    ->state(fn () => ['birth_date' => fake()->dateTimeBetween($earliest, '-1 month')])
                    ->create();

                // Childless here means HR asked and the answer was none, not
                // that the card was left blank — the profile tells them apart.
                $user->details->update(['has_children' => $count > 0]);
            });
    }

    /**
     * The holder of a unit's head position leads it; units without one get a
     * working member who holds a head role, if there is any. Larger units
     * sometimes get a second head: a department can have several.
     */
    private function assignDepartmentHeads(): void
    {
        $headPositions = array_flip(PositionSeeder::HEADS);

        Department::whereDoesntHave('heads')->get()->each(function (Department $department) use ($headPositions) {
            $members = $department->users()->active()->with(['positions', 'roles'])->get();

            $head = isset($headPositions[$department->name])
                ? $members->first(fn (User $u) => $u->positions->contains('name', $headPositions[$department->name]))
                : null;
            $head ??= $members->first(fn (User $u) => $u->hasAnyRole(['department-head', 'division-head']));

            if (! $head) {
                return;
            }

            $heads = [$head->id];

            if ($members->count() >= 5 && fake()->boolean(35)) {
                $heads[] = $members->where('id', '!=', $head->id)->random()->id;
            }

            $department->users()->syncWithoutDetaching(array_fill_keys($heads, ['is_head' => true]));
        });
    }

    /**
     * Every head title goes to someone in the unit it leads, preferring people
     * with a head role there. Everyone else gets one regular title, about one
     * in eight a second one. The admin account holds no position.
     */
    private function assignPositions(): void
    {
        $titles = Position::all()->keyBy('name');
        $everyone = fn () => User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));
        // Head posts go to people who still work here.
        $employees = fn () => $everyone()->active();

        foreach (PositionSeeder::HEADS as $title => $departmentName) {
            if ($titles[$title]->users()->exists()) {
                continue;
            }

            $department = Department::firstWhere('name', $departmentName);
            $head = $employees()
                ->whereHas('departments', fn ($q) => $q->whereKey($department->id))
                ->whereDoesntHave('positions', fn ($q) => $q->whereIn('name', array_keys(PositionSeeder::HEADS)))
                ->with('roles')
                ->get()
                ->sortByDesc(fn (User $u) => $u->hasAnyRole(['department-head', 'division-head']))
                ->first();

            if (! $head) {
                // Nobody suitable in that unit yet: move in someone who leads nothing else.
                $head = $employees()
                    ->doesntHave('positions')
                    // Nobody ends up in more than two departments.
                    ->has('departments', '<', 2)
                    ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['department-head', 'division-head']))
                    ->inRandomOrder()
                    ->first();
                $head?->departments()->syncWithoutDetaching([$department->id]);
            }

            $head?->positions()->attach($titles[$title]);
        }

        $regular = collect(PositionSeeder::OTHERS);

        $everyone()->doesntHave('positions')->get()->each(function (User $user) use ($titles, $regular) {
            $pool = $user->sex === 'female' ? $regular : $regular->reject(fn ($t) => $t === 'Уборщица');
            $picked = $pool->random(fake()->boolean(12) ? 2 : 1);

            $user->positions()->attach($titles->whereIn('name', $picked->all())->pluck('id'));
        });
    }

    /**
     * Heads sit in the unit they lead; everyone else is in one unit, a few in
     * two, and a few in none. The admin belongs to no department.
     */
    private function assignDepartments(): void
    {
        $top = Department::whereNull('parent_id')->orderBy('id')->get();
        $units = Department::whereNotNull('parent_id')->orderBy('id')->get();
        $all = $top->concat($units);

        User::doesntHave('departments')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->with('roles')
            ->orderBy('id')
            ->get()
            ->each(function (User $user) use ($top, $units, $all) {
                $roles = $user->roles->pluck('name');

                $departments = match (true) {
                    $roles->contains('department-head') => [$top->random()],
                    $roles->contains('division-head') => [$units->random()],
                    fake()->boolean(7) => [],
                    fake()->boolean(9) => $all->random(2)->all(),
                    default => [fake()->boolean(80) ? $units->random() : $top->random()],
                };

                $user->departments()->sync(collect($departments)->pluck('id'));
            });
    }

    /**
     * A couple of department heads, a few division heads, everyone else spread
     * over the remaining roles. About one in six also holds a second regular
     * position (roles are many-to-many).
     */
    private function assignRoles(): void
    {
        $regular = array_keys(array_diff_key(RoleSeeder::ROLES, array_flip(['admin', 'department-head', 'division-head'])));
        $pool = [...array_fill(0, 2, 'department-head'), ...array_fill(0, 6, 'division-head')];

        User::doesntHave('roles')->orderBy('id')->get()->each(function (User $user, int $index) use ($pool, $regular) {
            $first = $pool[$index] ?? fake()->randomElement($regular);
            $roles = [$first];

            if (fake()->boolean(17)) {
                $roles[] = fake()->randomElement(array_values(array_diff($regular, [$first])));
            }

            $user->assignRole($roles);
        });
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function account(array $attributes): User
    {
        $user = User::updateOrCreate(
            ['email' => $attributes['email']],
            User::factory()->raw($attributes),
        );

        if (! $user->details()->exists()) {
            UserDetail::factory()->for($user)->create();
        }

        return $user;
    }
}
