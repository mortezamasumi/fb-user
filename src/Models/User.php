<?php

namespace Mortezamasumi\FbUser\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbProfile\Enums\GenderEnum;
use Mortezamasumi\FbUser\Traits\HasCascadeOperation;
use Spatie\Permission\Traits\HasRoles;

abstract class User extends Authenticatable implements
    FilamentUser,
    HasAvatar,
    HasName
{
    use HasFactory;
    use HasRoles;
    use SoftDeletes;
    use Notifiable;
    use HasCascadeOperation;

    protected $fillable = [
        'mobile',
        'email',
        'username',
        'email_verified_at',
        'password',
        'first_name',
        'last_name',
        'nid',
        'gender',
        'birth_date',
        'profile',
        'demography',
        'mars',
        'expiration_date',
        'active',
        'force_change_password',
        'theme',
        'theme_color',
        'avatar',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'expiration_date' => 'datetime',
            'gender' => GenderEnum::class,
            'birth_date' => 'datetime',
            'profile' => 'array',
            'demography' => 'array',
            'mars' => 'array',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($user) {
            if ($user->isDirty(config('fb-auth.auth_type')->unVerifyAttribute())) {
                $user->email_verified_at = null;
            }
        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function reverseName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->last_name} - {$this->first_name}",
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return asset('storage/'.$this->avatar);
    }

    /**
     * required for messaging, can override by app User model
     */
    public function scopeMessageTo(Builder $query): Builder
    {
        return $query;
    }

    /**
     * extra elements to user create/edit form, roles has live reaction
     */
    public static function extraFormSection(): array
    {
        return [
            //
        ];
    }
}
