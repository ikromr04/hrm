<?php

namespace App\Queries\Api\V1;

use App\Models\Equipment;

class EquipmentQuery extends QueryProfile
{
    protected function model(): string
    {
        return Equipment::class;
    }

    protected function filters(): array
    {
        return [
            'id',
            'name',
            'description',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function includes(): array
    {
        return [
            'user',
        ];
    }

    protected function sorts(): array
    {
        return [
            'id',
            'name',
            'description',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'name',
            'description',
            'createdAt',
            'updatedAt',
        ];
    }
}
