<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    /**
     * Categories, exactly as the design's "Категории техники" names them, each
     * with the drawing it is shown by. A category added later picks its own in
     * the directory.
     */
    public const TYPES = [
        'Ноутбуки' => 'laptop',
        'Мониторы' => 'monitor',
        'Телефоны' => 'smartphone',
        'Печать' => 'printer',
        'Периферия' => 'headphones',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $name => $icon) {
            EquipmentType::firstOrCreate(['name' => $name], ['icon' => $icon]);
        }
    }
}
