<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        /** @disregard */
        Auth::getProvider()->getModel()::afterCreate($this);
    }
}
