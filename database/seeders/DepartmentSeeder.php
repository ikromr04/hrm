<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Top-level departments and the units under them.
     *
     * The real hierarchy is not settled yet; this grouping follows the names
     * and is meant to be corrected here.
     */
    public const TREE = [
        'Департамент управления человеческими ресурсами' => [],
        'Департамент аналитики и статистики' => [
            'Отдел аналитики',
            'Отдел анализа производителей',
        ],
        'Департамент маркетинга' => [
            'Отдел цифрового маркетинга',
            'Отдел Дизайна',
            'Отдел развития продуктового портфеля',
        ],
        'Департамент финансового контроля и аудита' => [
            'Отдел мониторинга и финансового контроля Эволет Европы',
            'Отдел управления расходами Эволет Таджикистан',
            'Отдел платежной реконсиляции',
        ],
        'Департамент регистрации и документации' => [
            'Отдел регистрации',
            'Отдел составления досье',
            'Отдел Товарных Знаков',
            'Научный отдел',
        ],
        'Департамент инновации и автоматизации' => [
            'Отдел автоматизации рабочих процессов',
            'Отдел Веб-разработок',
            'Отдел мониторинга и оптимизации рабочих процессов',
        ],
        'Департамент развития' => [
            'Отдел управления проектов',
        ],
        'Департамент контрактного производства' => [
            'Отдел контрактного производства Тдж',
            'Отдел планирование производство и логистики',
        ],
    ];

    public function run(): void
    {
        foreach (self::TREE as $name => $children) {
            $parent = Department::updateOrCreate(['name' => $name], ['parent_id' => null]);

            foreach ($children as $child) {
                Department::updateOrCreate(['name' => $child], ['parent_id' => $parent->id]);
            }
        }
    }
}
