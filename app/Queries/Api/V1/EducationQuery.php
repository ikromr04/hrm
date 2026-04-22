<?php

namespace App\Queries\Api\V1;

use App\Models\Education;

class EducationQuery extends QueryProfile
{
    protected function model(): string
    {
        return Education::class;
    }

    protected function filters(): array
    {
        return [
            'id',
            'institution',
            'faculty',
            'speciality',
            'form',
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
            'institution',
            'faculty',
            'speciality',
            'form',
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
            'institution',
            'faculty',
            'speciality',
            'form',
            'startedAt',
            'endedAt',
            'createdAt',
            'updatedAt',
        ];
    }
}
