<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case SINGLE_MAN = 'single_man';
    case SINGLE_WOMAN = 'single_woman';
    case MARRIED_MAN = 'married_man';
    case MARRIED_WOMAN = 'married_woman';


    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
