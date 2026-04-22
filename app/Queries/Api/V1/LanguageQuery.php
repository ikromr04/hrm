<?php

namespace App\Queries\Api\V1;

use App\Models\Language;

class LanguageQuery extends QueryProfile
{
    protected function model(): string
    {
        return Language::class;
    }

    protected function filters(): array
    {
        return [
            'id',
            'name',
            'level',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function includes(): array
    {
        return [
            'users',
        ];
    }

    protected function sorts(): array
    {
        return [
            'id',
            'name',
            'level',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'name',
            'level',
            'createdAt',
            'updatedAt',
        ];
    }
}
