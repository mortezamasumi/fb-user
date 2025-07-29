<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mortezamasumi\FbUser\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        $model = $this->getModel();

        if (method_exists($model, 'afterCreate')) {
            $model::afterCreate($this);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
