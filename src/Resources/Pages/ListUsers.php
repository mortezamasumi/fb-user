<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Exports\UserExporter;
use Mortezamasumi\FbUser\Imports\UserImporter;
use Mortezamasumi\FbUser\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('export-users')
                ->label(__('fb-user::fb-user.exporter.exporter_label'))
                ->modalHeading(__('fb-user::fb-user.exporter.exporter_heading'))
                ->exporter(UserExporter::class)
                ->modifyQueryUsing(fn ($query) => $query->role(roles: ['super_admin'], without: true))
                ->visible(fn () => Auth::user()->can('Export:User')),
            ImportAction::make('import-users')
                ->label(__('fb-user::fb-user.importer.importer_label'))
                ->modalHeading(__('fb-user::fb-user.importer.importer_heading'))
                ->importer(UserImporter::class)
                ->visible(Auth::user()->can('Create:User')),
            CreateAction::make(),
        ];
    }
}
