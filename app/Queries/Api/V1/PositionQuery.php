<?php

namespace App\Queries\Api\V1;

use App\Models\Position;

class PositionQuery extends QueryProfile
{
    protected function model(): string
    {
        return Position::class;
    }

    protected function filters(): array
    {
        return [
            'name',
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
            'createdAt',
            'updatedAt',
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'name',
            'createdAt',
            'updatedAt',
        ];
    }
}
