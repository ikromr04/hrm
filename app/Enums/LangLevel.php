<?php

namespace App\Enums;

enum LangLevel: string
{
    case А1 = 'А1';
    case А2 = 'А2';
    case В1 = 'В1';
    case В2 = 'В2';
    case C1 = 'C1';
    case C2 = 'C2';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
