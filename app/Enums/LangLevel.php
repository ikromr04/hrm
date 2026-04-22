<?php

namespace App\Enums;

enum LangLevel: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
