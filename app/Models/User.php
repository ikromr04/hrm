<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Resources\Api\V1\UserResource;
use Database\Seeders\EquipmentUserSeeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\UseResource;

#[UseResource(UserResource::class)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use SoftDeletes;

    public const PATH_AVATAR = 'images/avatars';
    public const PATH_AVATAR_THUMBS = 'images/avatars/thumbs';

    public const RELATIONSHIPS = [
        'details',
        'roles',
        'positions',
        'departments',
        'experiences',
        'educations',
        'equipments',
        'languages',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'login',
        'avatar',
        'avatar_thumb',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The detail that belong to the user.
     *
     * @return HasOne<UserDetail>
     */
    public function details(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * The roles that belong to the user.
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * The positions that belong to the user.
     *
     * @return BelongsToMany<Position>
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }

    /**
     * The departments that belong to the user.
     *
     * @return BelongsToMany<Department>
     */
    public function departments(): BelongsToMany
    {
        return $this
            ->belongsToMany(Department::class)
            ->withPivot('leader');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(UserExperience::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(UserEducation::class);
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    public function getAvatarAttribute(): string | null
    {
        if ($this->attributes['avatar']) {
            return asset("storage/{$this->attributes['avatar']}");
        }

        return null;
    }

    public function getAvatarThumbAttribute(): string | null
    {

        if ($this->attributes['avatar_thumb']) {
            return asset("storage/{$this->attributes['avatar_thumb']}");
        }

        return null;
    }

    public function syncRelationships(array $relationships): static
    {
        foreach ($relationships as $name => $value) {
            $this->{$name}()->sync($value);
        }

        return $this;
    }
}
