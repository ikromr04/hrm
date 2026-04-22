<?php

namespace App\Models;

use App\Http\Resources\Api\V1\EducationResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseResource(EducationResource::class)]
class Education extends Model
{
    protected $table = 'educations';

    protected $fillable = [
        'user_id',
        'institution',
        'faculty',
        'speciality',
        'form',
        'started_at',
        'ended_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
