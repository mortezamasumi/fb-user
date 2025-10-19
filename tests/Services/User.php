<?php

namespace Mortezamasumi\FbUser\Tests\Services;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mortezamasumi\FbUser\Models\User as ModelsUser;

#[UseFactory(UserFactory::class)]
class User extends ModelsUser
{
    use HasFactory;

    protected $guard_name = 'web';

    public static function extraFormSection(): array
    {
        return [
            TextInput::make('profile.some_data')->required(),
        ];
    }
}
