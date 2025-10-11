<?php

namespace Mortezamasumi\FbUser\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return $this->avatar ? asset('storage/'.$this->avatar) : url('/fb-essentials-assets/avatar.png');
    }

    public function getEmailForPasswordReset()
    {
        return match (config('fb-auth.auth_type')) {
            AuthType::Mobile => $this->mobile,
            AuthType::User => $this->username,
            default => $this->email,
        };
    }

    /**
     * Scope a query to search for a user's full name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereFullName(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Scope a query to order by a user's full name (last name, then first name).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFullName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query
            ->orderBy('users.last_name', $direction)
            ->orderBy('users.first_name', $direction);
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

    /**
     * called after create in edit user
     */
    public static function afterCreate(CreateRecord $livewire): void
    {
        //
    }

    /**
     * called after save in edit user
     */
    public static function afterSave(EditRecord $livewire): void
    {
        //
    }
}
