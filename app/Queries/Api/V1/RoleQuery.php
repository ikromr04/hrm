<?php

namespace App\Queries\Api\V1;

use App\Models\Role;

class RoleQuery extends QueryProfile
{
    protected function model(): string
    {
        return Role::class;
    }

    protected function filters(): array
    {
        return [
            'id',
            'name',
            'displayName',
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
            'displayName',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'name',
            'displayName',
            'createdAt',
            'updatedAt',
        ];
    }
}
