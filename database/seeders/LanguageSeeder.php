<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public const LANGUAGES = [
        'Таджикский',
        'Русский',
        'Английский',
        'Узбекский',
        'Персидский',
        'Немецкий',
        'Турецкий',
        'Французский',
        'Китайский',
        'Арабский',
    ];

    public function run(): void
    {
        foreach (self::LANGUAGES as $name) {
            Language::firstOrCreate(['name' => $name]);
        }
    }
}
