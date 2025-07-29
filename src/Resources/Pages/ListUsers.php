<?php

namespace Mortezamasumi\FbUser\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\Fbase\Exports\UserExporter;
use Mortezamasumi\Fbase\Imports\UserImporter;
use Mortezamasumi\FbUser\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ExportAction::make('export-demography-info')
            //     ->label('filament-base::filament-base.export_demography_info')
            //     ->exporter(DemographyExporter::class)
            //     ->modal(false)
            //     ->modifyQueryUsing(fn($query) => $query->role(roles: ['super_admin'], without: true))
            //     ->visible(auth()->user()->can('page_Demography')),
            // ExportAction::make('export-users')
            //     ->label(__('fbase::fbase.exporter.exporter_label'))
            //     ->modalHeading(__('fbase::fbase.exporter.exporter_heading'))
            //     ->exporter(UserExporter::class)
            //     ->modifyQueryUsing(fn ($query) => $query->role(roles: ['super_admin'], without: true))
            //     ->visible(fn () => Auth::user()->can('export_users')),
            // ImportAction::make('import-users')
            //     ->label(__('fbase::fbase.importer.importer_label'))
            //     ->modalHeading(__('fbase::fbase.importer.importer_heading'))
            //     ->importer(UserImporter::class)
            //     ->visible(Auth::user()->can('create_user')),
            CreateAction::make(),
        ];
    }
}
