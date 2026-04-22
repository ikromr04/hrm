<?php

namespace App\Queries\Api\V1;

use App\Models\Experience;

class ExperienceQuery extends QueryProfile
{
    protected function model(): string
    {
        return Experience::class;
    }

    protected function filters(): array
    {
        return [
            'id',
            'companyName',
            'position',
            'startedAt',
            'endedAt',
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
            'companyName',
            'position',
            'startedAt',
            'endedAt',
            'createdAt',
            'updatedAt',
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'companyName',
            'position',
            'startedAt',
            'endedAt',
            'createdAt',
            'updatedAt',
        ];
    }
}
