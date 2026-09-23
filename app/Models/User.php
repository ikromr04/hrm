<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Mirrors the column default, so a freshly created user counts as working
     * before it is reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
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
        'avatar',
        'avatar_original',
        'sex',
        'status',
        'status_changed_at',
        'status_note',
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
        // Why someone was let go is not for the page payload by default.
        'status_note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_changed_at' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Both columns hold a path on the public disk, but every reader wants a
     * URL, so they hand one out. The path itself is still reachable through
     * getRawOriginal(), which is what deleting the file needs.
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (?string $path) => self::publicUrl($path));
    }

    protected function avatarOriginal(): Attribute
    {
        return Attribute::get(fn (?string $path) => self::publicUrl($path));
    }

    private static function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Still working here, as opposed to transferred or fired.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Private data (passport, address, contacts). Only load it for viewers
     * who are allowed to see it.
     */
    public function details(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * What the employee does; one or several, public. Access rights are roles.
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class)->withTimestamps()->orderBy('name');
    }

    /**
     * None, one or several departments (public, like positions).
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withPivot('is_head')->withTimestamps()->orderBy('name');
    }

    /**
     * Languages the employee speaks, each with a level; public, like positions.
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class)->withPivot('level')->withTimestamps()->orderBy('name');
    }

    /**
     * Private, like details.
     */
    public function children(): HasMany
    {
        return $this->hasMany(UserChild::class)->orderBy('birth_date');
    }

    /**
     * Where the employee studied, earliest first; private, like details.
     */
    public function educations(): HasMany
    {
        return $this->hasMany(UserEducation::class)->orderBy('started_year')->orderBy('id');
    }

    /**
     * Previous jobs, the latest first; private, like details.
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(UserWorkExperience::class)->orderByDesc('started_year')->orderByDesc('started_month')->orderByDesc('id');
    }

    /**
     * Company hardware this person holds right now. The units belong to the
     * company and are handed out from the equipment section; the profile only
     * shows what is currently on them.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'holder_user_id')->orderBy('equipment_type_id')->orderBy('id');
    }
}
