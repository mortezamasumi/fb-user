<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Models\User;
use Mortezamasumi\FbUser\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        $provider = Auth::getProvider();

        if (! $provider instanceof EloquentUserProvider) {
            return;
        }

        /** @var class-string<User> $model */
        $model = $provider->getModel();

        $model::afterCreate($this);
    }
}
