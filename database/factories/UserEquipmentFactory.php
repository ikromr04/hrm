<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserEquipment>
 */
class UserEquipmentFactory extends Factory
{
    /** Models that fit each kind of hardware, by directory name. */
    private const MODELS = [
        'Ноутбук' => ['HP ProBook 450 G9, i5 / 16 ГБ', 'Lenovo ThinkPad E14, i5 / 8 ГБ', 'Acer Aspire 5, i3 / 8 ГБ', 'Dell Vostro 3520, i5 / 16 ГБ'],
        'Персональный компьютер' => ['Сборка: i5-12400, 16 ГБ, SSD 512 ГБ', 'Dell OptiPlex 3000, i5 / 8 ГБ', 'HP ProDesk 400 G7, i5 / 16 ГБ'],
        'Монитор' => ['Dell P2422H, 24"', 'Samsung S24R350, 24"', 'LG 22MK400H, 22"', 'AOC 24B2XH, 24"'],
        'Клавиатура' => ['Logitech K120', 'A4Tech KR-85', 'Defender Element HB-520'],
        'Мышь' => ['Logitech B100', 'A4Tech OP-720', 'Defender Optimum MB-160'],
        'Принтер' => ['HP LaserJet Pro M404dn', 'Canon i-SENSYS LBP223dw', 'Brother HL-L2340DW'],
        'Сканер' => ['Canon LiDE 300', 'Epson Perfection V19'],
        'МФУ' => ['HP LaserJet MFP M428fdn', 'Canon i-SENSYS MF445dw', 'Xerox WorkCentre 3335'],
        'Телефон' => ['Panasonic KX-TS2350', 'Grandstream GXP1625, IP'],
        'Гарнитура' => ['Jabra Evolve 20', 'Logitech H340', 'Plantronics Blackwire 3220'],
        'ИБП' => ['APC Back-UPS BX650LI, 650 ВА', 'Powercom RPT-600A, 600 ВА'],
        'Планшет' => ['Samsung Galaxy Tab A8', 'Lenovo Tab M10'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'equipment_type_id' => EquipmentType::factory(),
            'description' => null,
            'inventory_number' => 'ИНВ-'.fake()->unique()->numerify('######'),
        ];
    }

    /**
     * A unit of the given kind, described by a model that suits it.
     */
    public function ofType(EquipmentType $type): static
    {
        return $this->state(fn () => [
            'equipment_type_id' => $type->id,
            'description' => isset(self::MODELS[$type->name]) ? fake()->randomElement(self::MODELS[$type->name]) : null,
        ]);
    }
}
