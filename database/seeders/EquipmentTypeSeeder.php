<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    /** Categories, exactly as the design's "Категории техники" names them. */
    public const TYPES = [
        'Ноутбуки',
        'Мониторы',
        'Телефоны',
        'Печать',
        'Периферия',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $name) {
            EquipmentType::firstOrCreate(['name' => $name]);
        }
    }
}
