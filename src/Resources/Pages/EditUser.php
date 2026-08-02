<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Models\User;
use Mortezamasumi\FbUser\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $provider = Auth::getProvider();

        if (! $provider instanceof EloquentUserProvider) {
            return;
        }

        /** @var class-string<User> $model */
        $model = $provider->getModel();

        $model::afterSave($this);
    }
}
