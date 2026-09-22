<?php

namespace App\Models;

use Database\Factories\UserEducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEducation extends Model
{
    /** @use HasFactory<UserEducationFactory> */
    use HasFactory;

    protected $table = 'user_educations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution',
        'faculty',
        'specialty',
        'started_year',
        'graduated_year',
        'diploma_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
