<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public const EMAIL_DOMAIN = 'evolet.test';

    private const MALE_NAMES = [
        'Фарход', 'Алишер', 'Рустам', 'Бехруз', 'Тимур', 'Сухроб', 'Ориф', 'Джамшед', 'Азиз', 'Шерзод',
        'Далер', 'Парвиз', 'Умед', 'Комрон', 'Зафар', 'Фирдавс', 'Манучехр', 'Исмоил', 'Баходур', 'Хуршед',
        'Некруз', 'Сорбон', 'Фаридун', 'Мехрон', 'Шахбоз', 'Дилшод', 'Искандар', 'Рахмон', 'Файзулло', 'Абдулло',
    ];

    private const FEMALE_NAMES = [
        'Мадина', 'Нигина', 'Зарина', 'Шахноза', 'Дилноза', 'Парвина', 'Фируза', 'Лола', 'Малика', 'Нилуфар',
        'Гулноза', 'Тахмина', 'Мунира', 'Ситора', 'Нодира', 'Мехрангез', 'Шабнам', 'Зулфия', 'Фарангис', 'Манижа',
        'Сурайё', 'Гулчехра', 'Шахло', 'Мавзуна', 'Дилафруз', 'Бахор', 'Сабохат', 'Замира', 'Нозанин', 'Хилола',
    ];

    /** Male forms; the female form adds "а". */
    private const SURNAMES = [
        'Рахимов', 'Каримов', 'Шарипов', 'Саидов', 'Азимов', 'Хасанов', 'Юсупов', 'Мирзоев', 'Давлатов', 'Назаров',
        'Олимов', 'Нуров', 'Рашидов', 'Сафаров', 'Одинаев', 'Ибрагимов', 'Шарифов', 'Холов', 'Раджабов', 'Кодиров',
        'Султонов', 'Турсунов', 'Каюмов', 'Зоиров', 'Бобоев', 'Абдуллоев', 'Джураев', 'Гафуров', 'Шукуров', 'Алиев',
    ];

    /** Father names ending in a consonant, so "-ович" / "-овна" always fits. */
    private const FATHER_NAMES = [
        'Фарход', 'Рустам', 'Джамшед', 'Азиз', 'Шерзод', 'Далер', 'Парвиз', 'Умед', 'Зафар', 'Хуршед',
        'Исмоил', 'Рахмон', 'Саид', 'Карим', 'Олим', 'Баходур', 'Некруз', 'Фаридун', 'Сайфиддин', 'Нуриддин',
    ];

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sex = fake()->randomElement(['male', 'female']);
        $female = $sex === 'female';
        $name = fake()->randomElement($female ? self::FEMALE_NAMES : self::MALE_NAMES);
        $surname = fake()->randomElement(self::SURNAMES).($female ? 'а' : '');

        return [
            'name' => $name,
            'surname' => $surname,
            'patronymic' => fake()->randomElement(self::FATHER_NAMES).($female ? 'овна' : 'ович'),
            'avatar' => null,
            'sex' => $sex,
            'email' => self::corporateEmail($name, $surname),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * "Фарход Рахимов" -> f.rahimov@evolet.test, with a number appended on clashes.
     */
    public static function corporateEmail(string $name, string $surname): string
    {
        // Batches are built before anything is inserted, so remember what was handed out.
        static $issued = [];

        $local = Str::lower(Str::ascii(Str::substr($name, 0, 1), 'ru').'.'.Str::ascii($surname, 'ru'));
        $email = "{$local}@".self::EMAIL_DOMAIN;

        for ($i = 2; isset($issued[$email]) || User::where('email', $email)->exists(); $i++) {
            $email = "{$local}{$i}@".self::EMAIL_DOMAIN;
        }

        return $issued[$email] = $email;
    }
}
