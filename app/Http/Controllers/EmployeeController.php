<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    private const PER_PAGE = 10;

    private const STATUSES = ['active', 'probation', 'leave', 'dismissed'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $all = $this->demoEmployees();

        // Dropdown filters narrow every tab, so the tab counters follow them.
        $filtered = $all
            ->when($filters['department'] ?? null, fn (Collection $c, string $v) => $c->where('department', $v))
            ->when($filters['position'] ?? null, fn (Collection $c, string $v) => $c->where('position', $v))
            ->when($filters['location'] ?? null, fn (Collection $c, string $v) => $c->where('location', $v));

        $counts = ['all' => $filtered->count()]
            + collect(self::STATUSES)->mapWithKeys(fn (string $s) => [$s => $filtered->where('status', $s)->count()])->all();

        $rows = $filtered
            ->when($filters['status'] ?? null, fn (Collection $c, string $v) => $c->where('status', $v))
            ->sortBy('name')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $employees = new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values(),
            $rows->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'counts' => $counts,
            'filters' => [
                'status' => $filters['status'] ?? null,
                'department' => $filters['department'] ?? null,
                'position' => $filters['position'] ?? null,
                'location' => $filters['location'] ?? null,
            ],
            'options' => [
                'departments' => $all->pluck('department')->unique()->sort()->values(),
                'positions' => $all->pluck('position')->unique()->sort()->values(),
                'locations' => $all->pluck('location')->unique()->sort()->values(),
            ],
            'summary' => [
                'people' => $all->count(),
                'departments' => $all->pluck('department')->unique()->count(),
            ],
        ]);
    }

    /**
     * Placeholder directory until the employees module has its own tables.
     * Replace with an Eloquent query (the filters above map one-to-one to
     * where clauses and ->paginate()).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function demoEmployees(): Collection
    {
        $rows = [
            ['Фарход Рахимов', 'f.rakhimov', 'Senior Backend-разработчик', 'Разработка', 'active', '2021-03-12', 'Душанбе'],
            ['Мадина Каримова', 'm.karimova', 'Менеджер по продажам', 'Продажи', 'active', '2022-07-04', 'Душанбе'],
            ['Алишер Шарипов', 'a.sharipov', 'Frontend-разработчик', 'Разработка', 'probation', '2026-08-01', 'Душанбе'],
            ['Нигина Саидова', 'n.saidova', 'Бухгалтер', 'Финансы', 'active', '2020-01-15', 'Душанбе'],
            ['Рустам Азимов', 'r.azimov', 'Руководитель склада', 'Операции', 'leave', '2019-09-22', 'Худжанд'],
            ['Зарина Хасанова', 'z.khasanova', 'Специалист поддержки', 'Поддержка', 'active', '2023-05-10', 'Душанбе'],
            ['Тимур Юсупов', 't.yusupov', 'Product-дизайнер', 'Разработка', 'probation', '2026-07-15', 'Душанбе'],
            ['Бехруз Мирзоев', 'b.mirzoev', 'Аккаунт-менеджер', 'Продажи', 'active', '2024-02-03', 'Худжанд'],
            ['Шахноза Ибрагимова', 'sh.ibragimova', 'Маркетолог', 'Маркетинг', 'active', '2022-11-19', 'Душанбе'],
            ['Ориф Давлатов', 'o.davlatov', 'DevOps-инженер', 'Разработка', 'dismissed', '2021-04-08', 'Душанбе'],
            ['Сухроб Каримов', 's.karimov', 'Руководитель разработки', 'Разработка', 'active', '2018-06-01', 'Душанбе'],
            ['Азиз Нуров', 'a.nurov', 'Руководитель продаж', 'Продажи', 'active', '2017-10-16', 'Душанбе'],
            ['Лола Рашидова', 'l.rashidova', 'Финансовый директор', 'Финансы', 'active', '2016-02-01', 'Душанбе'],
            ['Джамшед Олимов', 'j.olimov', 'Директор по операциям', 'Операции', 'active', '2017-03-20', 'Душанбе'],
            ['Парвина Юнусова', 'p.yunusova', 'Руководитель поддержки', 'Поддержка', 'active', '2019-05-13', 'Душанбе'],
            ['Фируза Алиева', 'f.alieva', 'Руководитель маркетинга', 'Маркетинг', 'active', '2020-08-24', 'Душанбе'],
            ['Дилноза Назарова', 'd.nazarova', 'HR-директор', 'HR и админ.', 'active', '2018-01-09', 'Душанбе'],
            ['Далер Сафаров', 'd.safarov', 'Backend-разработчик', 'Разработка', 'active', '2023-09-04', 'Душанбе'],
            ['Малика Холова', 'm.kholova', 'QA-инженер', 'Разработка', 'leave', '2022-03-14', 'Душанбе'],
            ['Комрон Шарифов', 'k.sharifov', 'Медицинский представитель', 'Продажи', 'active', '2021-11-01', 'Бохтар'],
            ['Нилуфар Раджабова', 'n.radzhabova', 'Медицинский представитель', 'Продажи', 'probation', '2026-08-18', 'Худжанд'],
            ['Умед Одинаев', 'u.odinaev', 'Логист', 'Операции', 'active', '2020-06-29', 'Душанбе'],
            ['Гулноза Кодирова', 'g.kodirova', 'Кладовщик', 'Операции', 'active', '2024-04-15', 'Худжанд'],
            ['Парвиз Султонов', 'p.sultonov', 'Специалист поддержки', 'Поддержка', 'active', '2025-01-20', 'Душанбе'],
            ['Тахмина Турсунова', 't.tursunova', 'Бухгалтер', 'Финансы', 'leave', '2021-07-05', 'Душанбе'],
            ['Хуршед Каюмов', 'kh.kayumov', 'Контент-менеджер', 'Маркетинг', 'dismissed', '2023-02-27', 'Душанбе'],
            ['Ситора Бобоева', 's.boboeva', 'Специалист по кадрам', 'HR и админ.', 'active', '2022-10-10', 'Душанбе'],
            ['Некруз Абдуллоев', 'n.abdulloev', 'Системный администратор', 'HR и админ.', 'probation', '2026-06-30', 'Душанбе'],
            ['Мунира Гафурова', 'm.gafurova', 'Медицинский представитель', 'Продажи', 'active', '2023-12-04', 'Бохтар'],
            ['Зафар Шукуров', 'z.shukurov', 'Водитель-экспедитор', 'Операции', 'active', '2019-03-18', 'Душанбе'],
        ];

        $managers = [
            'Разработка' => 'Сухроб Каримов',
            'Продажи' => 'Азиз Нуров',
            'Финансы' => 'Лола Рашидова',
            'Операции' => 'Джамшед Олимов',
            'Поддержка' => 'Парвина Юнусова',
            'Маркетинг' => 'Фируза Алиева',
            'HR и админ.' => 'Дилноза Назарова',
        ];

        return collect($rows)->map(fn (array $row, int $index) => [
            'id' => $index + 1,
            'name' => $row[0],
            'email' => "{$row[1]}@evolet.test",
            'position' => $row[2],
            'department' => $row[3],
            'status' => $row[4],
            'hired_at' => $row[5],
            'location' => $row[6],
            // Heads of department have no manager in this demo.
            'manager' => $managers[$row[3]] === $row[0] ? null : $managers[$row[3]],
        ]);
    }
}
