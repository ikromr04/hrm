<?php

namespace App\Models;

use App\Http\Resources\Api\V1\EquipmentResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseResource(EquipmentResource::class)]
class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
