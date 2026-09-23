<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Language;
use App\Models\Position;
use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use App\Models\UserEducation;
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
     * A fleet of hardware: a workplace set handed to most of the working staff,
     * plus spares on the shelf, a few units at the service and some written off.
     */
    private function addEquipment(): void
    {
        $types = EquipmentType::all()->keyBy('name');

        if ($types->isEmpty()) {
            return;
        }

        // Peripherals only make sense next to a desktop computer.
        $desktopExtras = ['Мониторы', 'Периферия'];
        $occasional = ['Печать', 'Телефоны'];

        User::doesntHave('equipment')->where('status', 'active')->with('details')->get()
            ->each(function (User $user) use ($types, $desktopExtras, $occasional) {
                if (! fake()->boolean(85)) {
                    return;
                }

                $names = fake()->boolean(35)
                    ? ['Ноутбуки', ...fake()->randomElements($desktopExtras, fake()->numberBetween(0, 1))]
                    : ['Ноутбуки', ...$desktopExtras];
                $names = [...$names, ...fake()->randomElements($occasional, fake()->numberBetween(0, 2))];
                $since = $user->details?->hired_at?->toDateString();

                foreach (array_unique($names) as $name) {
                    Equipment::factory()->ofType($types[$name])->issuedTo($user->id, $since)->create();
                }
            });

        // Not everything is in someone's hands: spares on the shelf, a few units
        // away at the service, and some already out of the fleet.
        $spread = [['stock', 26], ['repair', 8], ['written_off', 12]];

        foreach ($spread as [$status, $count]) {
            foreach (range(1, $count) as $ignored) {
                $factory = Equipment::factory()->ofType($types->random());

                $factory = match ($status) {
                    'repair' => $factory->inRepair(),
                    'written_off' => $factory->writtenOff(),
                    default => $factory,
                };

                $factory->create();
            }
        }

        $this->addEquipmentHistory();
    }

    /**
     * Where each unit has been and what has been done to it, so a card opens on
     * a life rather than on a blank page: a spell on the shelf after it was
     * bought, the handover that followed, and the odd visit to a repair shop.
     *
     * Documents are left alone: a row without the file behind it would only
     * give the card a link that leads nowhere.
     */
    private function addEquipmentHistory(): void
    {
        Equipment::doesntHave('assignments')->with('type')->get()->each(function (Equipment $unit) {
            $bought = $unit->purchased_at?->toDateString() ?? fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d');
            $issued = $unit->issued_at?->toDateString();

            // On the shelf from the day it arrived until somebody took it.
            $unit->assignments()->create([
                'issued_at' => $bought,
                'returned_at' => $issued ?? ($unit->status === 'stock' ? null : $unit->written_off_at?->toDateString() ?? now()->toDateString()),
                'condition_on_return' => $issued ? 'Новое, в упаковке' : null,
            ]);

            if ($issued !== null) {
                $unit->assignments()->create([
                    'holder_user_id' => $unit->holder_user_id,
                    'holder_department_id' => $unit->holder_department_id,
                    'issued_at' => $issued,
                    'act_number' => '№ '.fake()->numerify('###').'-'.fake()->numberBetween(1, 9),
                ]);
            }

            // Away at a contractor right now, or looked after some time ago.
            if ($unit->status === 'repair') {
                $unit->repairs()->create([
                    'kind' => fake()->randomElement(self::REPAIRS),
                    'started_at' => fake()->dateTimeBetween('-2 months', '-3 days'),
                    'contractor' => fake()->randomElement(self::CONTRACTORS),
                ]);

                return;
            }

            for ($visit = fake()->numberBetween(0, 2); $visit > 0; $visit--) {
                $started = fake()->dateTimeBetween($bought, '-1 month');

                $unit->repairs()->create([
                    'kind' => fake()->randomElement(self::REPAIRS),
                    'started_at' => $started,
                    'ended_at' => (clone $started)->modify('+'.fake()->numberBetween(1, 6).' days'),
                    'contractor' => fake()->randomElement(self::CONTRACTORS),
                    'cost' => fake()->numberBetween(2, 30) * 20,
                    'note' => fake()->randomElement(['Плановое ТО', 'Износ детали', 'По заявке сотрудника', null]),
                ]);
            }
        });
    }

    /** @var list<string> */
    private const REPAIRS = [
        'Чистка и замена термопасты',
        'Замена аккумулятора',
        'Замена клавиатуры',
        'Замена блока питания',
        'Диагностика',
        'Замена картриджа и чистка',
    ];

    /** @var list<string> */
    private const CONTRACTORS = ['Сервис «Техномир»', 'Сервис «Электрон»', 'ИП Салимов', 'Авторизованный сервис Dell'];

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
