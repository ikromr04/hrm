<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    public const TYPES = [
        'Ноутбук',
        'Персональный компьютер',
        'Монитор',
        'Клавиатура',
        'Мышь',
        'Принтер',
        'Сканер',
        'МФУ',
        'Телефон',
        'Гарнитура',
        'ИБП',
        'Планшет',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $name) {
            EquipmentType::firstOrCreate(['name' => $name]);
        }
    }
}
