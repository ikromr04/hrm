<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Resources\Api\V1\UserResource;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;

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
        return $this->belongsToMany(Department::class);
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

    public function syncRelationships(array $relationships): static
    {
        foreach ($relationships as $name => $value) {
            $this->{$name}()->sync($value);
        }

        return $this;
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

    public function storeAvatar(UploadedFile $avatar): void
    {
        $thumbnail = (new ImageManager(Driver::class))
            ->decode($avatar)
            ->cover(144, 144)
            ->encode(new WebpEncoder(quality: 90));

        $hashedName = pathinfo($avatar->hashName(), PATHINFO_FILENAME);
        $avatarName = "{$hashedName}.{$avatar->extension()}";
        $avatarPath = User::PATH_AVATAR . "/{$avatarName}";
        $thumbPath = User::PATH_AVATAR_THUMBS . "/{$hashedName}.webp";

        Storage::disk('public')->putFileAs(User::PATH_AVATAR, $avatar, $avatarName);
        Storage::disk('public')->put($thumbPath, $thumbnail);

        $this->update([
            'avatar' => $avatarPath,
            'avatar_thumb' => $thumbPath,
        ]);
    }


    public function updateAvatar(UploadedFile $avatar): void
    {
        $this->deleteAvatarFiles();
        $this->storeAvatar($avatar);
    }

    public function deleteAvatar(): void
    {
        $this->deleteAvatarFiles();
        $this->update([
            'avatar' => null,
            'avatar_thumb' => null,
        ]);
    }

    public function deleteAvatarFiles(): void
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            Storage::disk('public')->delete($this->avatar);
        }

        if ($this->avatar_thumb && Storage::disk('public')->exists($this->avatar_thumb)) {
            Storage::disk('public')->delete($this->avatar_thumb);
        }
    }
}
