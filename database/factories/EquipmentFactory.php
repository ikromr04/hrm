<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Real models per category, each with the word the list puts in front:
     * "Ноутбук Dell Latitude 5440", "МФУ Kyocera M2540". The word belongs to
     * the model rather than the category — "Периферия" holds both a headset
     * and a keyboard.
     */
    private const MODELS = [
        'Ноутбуки' => [
            ['Ноутбук', 'HP', 'ProBook 450 G9'], ['Ноутбук', 'Lenovo', 'ThinkPad E14'],
            ['Ноутбук', 'Acer', 'Aspire 5'], ['Ноутбук', 'Dell', 'Latitude 5440'],
        ],
        'Мониторы' => [
            ['Монитор', 'Dell', 'P2422H'], ['Монитор', 'Samsung', 'S24R350'],
            ['Монитор', 'LG', '24MK430'], ['Монитор', 'AOC', '24B2XH'],
        ],
        'Телефоны' => [
            ['Телефон', 'Samsung', 'Galaxy A54'], ['Телефон', 'Xiaomi', 'Redmi 12'], ['Телефон', 'Grandstream', 'GXP1625'],
        ],
        'Печать' => [
            ['МФУ', 'Kyocera', 'M2540'], ['МФУ', 'Canon', 'i-SENSYS MF445dw'], ['Принтер', 'HP', 'LaserJet Pro M404dn'],
        ],
        'Периферия' => [
            ['Гарнитура', 'Jabra', 'Evolve 40'], ['Клавиатура', 'Logitech', 'K120'],
            ['Мышь', 'A4Tech', 'OP-720'], ['Мышь', 'Defender', 'MB-160'],
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_type_id' => EquipmentType::factory(),
            'name' => fake()->words(2, true),
            'maker' => null,
            'serial_number' => strtoupper(fake()->bothify('#?#?#?#')),
            'inventory_number' => 'EV-'.fake()->unique()->numerify('####'),
            'status' => 'stock',
        ];
    }

    /**
     * A real model from the given category, named the way the list shows it:
     * "Ноутбук Dell Latitude 5440".
     */
    public function ofType(EquipmentType $type): static
    {
        return $this->state(function () use ($type) {
            $models = self::MODELS[$type->name] ?? [[$type->name, null, fake()->word()]];
            [$kind, $maker, $model] = fake()->randomElement($models);

            return [
                'equipment_type_id' => $type->id,
                'name' => trim("{$kind} {$maker} {$model}"),
                'maker' => $maker,
            ];
        });
    }

    /** Handed to someone, on a date since they joined. */
    public function issuedTo(int $userId, ?string $since = null): static
    {
        return $this->state(fn () => [
            'status' => 'issued',
            'holder_user_id' => $userId,
            'issued_at' => fake()->dateTimeBetween($since ?? '-5 years', '-1 month'),
        ]);
    }

    public function inRepair(): static
    {
        return $this->state(fn () => ['status' => 'repair']);
    }

    public function writtenOff(): static
    {
        return $this->state(fn () => [
            'status' => 'written_off',
            'written_off_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }
}
