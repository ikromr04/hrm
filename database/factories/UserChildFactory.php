<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserChild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserChild>
 */
class UserChildFactory extends Factory
{
    private const BOYS = ['Амир', 'Юсуф', 'Сомон', 'Мухаммад', 'Идрис', 'Одил', 'Сино', 'Далер', 'Азиз', 'Бахтовар'];

    private const GIRLS = ['Сумая', 'Асал', 'Мехрона', 'Рухшона', 'Самира', 'Мадина', 'Нигора', 'Зарина', 'Малика', 'Ойша'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->randomElement([...self::BOYS, ...self::GIRLS]),
            'birth_date' => fake()->dateTimeBetween('-25 years', '-1 month'),
        ];
    }

    /**
     * A child carrying the family surname, in the form that matches the child's sex:
     * a son of "Каримова" is "Каримов Амир".
     */
    public function ofFamily(string $surname): static
    {
        return $this->state(function () use ($surname) {
            $girl = fake()->boolean();
            $base = preg_replace('/а$/u', '', $surname);

            return ['full_name' => ($girl ? $base.'а' : $base).' '.fake()->randomElement($girl ? self::GIRLS : self::BOYS)];
        });
    }
}
