<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', $this->demoData());
    }

    /**
     * Placeholder figures from the design mock-up.
     *
     * Leave, recruitment and org-structure modules do not exist yet, so the
     * dashboard is fed static data. Replace each block with real queries as
     * the corresponding module lands.
     *
     * @return array<string, mixed>
     */
    private function demoData(): array
    {
        return [
            'stats' => [
                ['key' => 'employees', 'label' => 'Сотрудников', 'value' => '248', 'note' => '+6 за месяц', 'tone' => 'success'],
                ['key' => 'vacancies', 'label' => 'Открытых вакансий', 'value' => '12', 'note' => '3 срочные', 'tone' => 'warning'],
                ['key' => 'absent', 'label' => 'Отсутствуют сегодня', 'value' => '9', 'note' => '3,6% штата', 'tone' => 'neutral'],
                ['key' => 'turnover', 'label' => 'Текучесть за год', 'value' => '7,4%', 'note' => '−1,2 п.п.', 'tone' => 'success'],
            ],
            'pendingRequests' => [
                'total' => 17,
                'items' => [
                    ['id' => 1, 'name' => 'Фарход Рахимов', 'details' => 'Ежегодный отпуск · 6–19 окт · 14 дней'],
                    ['id' => 2, 'name' => 'Мадина Каримова', 'details' => 'Больничный · 22–24 сен · 3 дня'],
                    ['id' => 3, 'name' => 'Алишер Шарипов', 'details' => 'Командировка · 29 сен – 2 окт · Худжанд'],
                    ['id' => 4, 'name' => 'Нигина Саидова', 'details' => 'Отгул · 25 сен · 1 день'],
                    ['id' => 5, 'name' => 'Тимур Юсупов', 'details' => 'Ежегодный отпуск · 13–24 окт · 10 дней'],
                ],
            ],
            'absentToday' => [
                ['id' => 1, 'name' => 'Рустам Азимов', 'details' => 'Отпуск до 3 окт', 'status' => 'Отпуск', 'tone' => 'info'],
                ['id' => 2, 'name' => 'Зарина Хасанова', 'details' => 'Больничный', 'status' => 'Болеет', 'tone' => 'danger'],
                ['id' => 3, 'name' => 'Бехруз Мирзоев', 'details' => 'Командировка, Москва', 'status' => 'В пути', 'tone' => 'warning'],
                ['id' => 4, 'name' => 'Ориф Давлатов', 'details' => 'Удалённо', 'status' => 'Удалённо', 'tone' => 'success'],
            ],
            'probationAlert' => [
                'title' => '3 испытательных срока заканчиваются',
                'description' => 'В течение 14 дней — нужны решения руководителей',
            ],
            'departments' => [
                ['name' => 'Разработка', 'count' => 64],
                ['name' => 'Продажи', 'count' => 42],
                ['name' => 'Операции', 'count' => 38],
                ['name' => 'Поддержка', 'count' => 31],
                ['name' => 'Финансы', 'count' => 22],
                ['name' => 'Маркетинг', 'count' => 19],
                ['name' => 'HR и админ.', 'count' => 14],
            ],
            'events' => [
                ['date' => '2026-09-24', 'title' => 'Онбординг: 4 новых сотрудника', 'details' => '10:00 · Переговорная 2'],
                ['date' => '2026-09-27', 'title' => 'Дни рождения: Нигина С., Сухроб К.', 'details' => '2 человека'],
                ['date' => '2026-10-01', 'title' => 'Старт квартальной оценки', 'details' => 'Цикл Q4 · 186 сотрудников'],
                ['date' => '2026-10-05', 'title' => 'Выплата зарплаты за сентябрь', 'details' => 'Ведомость готова на 92%'],
            ],
        ];
    }
}
