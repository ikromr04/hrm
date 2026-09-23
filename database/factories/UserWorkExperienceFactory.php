<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserWorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserWorkExperience>
 */
class UserWorkExperienceFactory extends Factory
{
    /** Organization => country. */
    private const ORGANIZATIONS = [
        'ОАО «Ориёнбанк»' => 'Таджикистан',
        'ЗАО «Бонки Эсхата»' => 'Таджикистан',
        'ООО «Тcell»' => 'Таджикистан',
        'ООО «Фарм Дистрибьюшн»' => 'Таджикистан',
        'ГУ «Республиканский центр лекарственных средств»' => 'Таджикистан',
        'ООО «Шифобахш»' => 'Таджикистан',
        'ООО «Nika Pharm»' => 'Узбекистан',
        'ООО «Фармстандарт»' => 'Россия',
        'ТОО «Европа-Фарм»' => 'Казахстан',
        'Представительство «Эволет» в Европе' => 'Германия',
    ];

    private const POSITIONS = [
        'Менеджер по продажам', 'Бухгалтер', 'Фармацевт', 'Медицинский представитель', 'Специалист по регистрации',
        'Переводчик', 'Маркетолог', 'Дизайнер', 'Аналитик', 'Офис-менеджер', 'Программист', 'Специалист отдела кадров',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = fake()->randomElement(array_keys(self::ORGANIZATIONS));
        $started = fake()->numberBetween(2008, 2020);

        return [
            'user_id' => User::factory(),
            'organization' => $organization,
            'position' => fake()->randomElement(self::POSITIONS),
            'country' => self::ORGANIZATIONS[$organization],
            'started_month' => fake()->numberBetween(1, 12),
            'started_year' => $started,
            'ended_month' => fake()->numberBetween(1, 12),
            'ended_year' => $started + fake()->numberBetween(1, 4),
        ];
    }

    /**
     * A job that starts in the given month and lasts 1–4 years, ending no
     * later than a month before `$until` (the next job or joining Evolet).
     */
    public function between(int $fromYear, int $fromMonth, int $untilYear, int $untilMonth): static
    {
        return $this->state(function () use ($fromYear, $fromMonth, $untilYear, $untilMonth) {
            $start = $fromYear * 12 + $fromMonth - 1;
            $limit = $untilYear * 12 + $untilMonth - 2;
            $end = min($limit, $start + fake()->numberBetween(12, 48));

            return [
                'started_month' => $start % 12 + 1,
                'started_year' => intdiv($start, 12),
                'ended_month' => $end % 12 + 1,
                'ended_year' => intdiv($end, 12),
            ];
        });
    }
}
