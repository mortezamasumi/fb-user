<?php

namespace Mortezamasumi\FbUser\Resources\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withTrashed()
                ->unless(
                    Auth::user()->hasRole('super_admin'),
                    fn (Builder $query) => $query->role(roles: ['super_admin'], without: true)
                ))
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('fb-user::fb-user.table.avatar'))
                    ->circular()
                    ->disk('public')
                    ->visibility('public'),
                TextColumn::make('reverse_name')
                    ->label(__('fb-user::fb-user.table.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('username')
                    ->label(__('fb-user::fb-user.table.username'))
                    ->searchable()
                    ->sortable()
                    ->visible(config('fb-profile.username_required')),
                TextColumn::make('email')
                    ->label(__('fb-user::fb-user.table.email'))
                    ->searchable()
                    ->sortable()
                    ->visible(config('fb-profile.email_required')),
                TextColumn::make('mobile')
                    ->label(__('fb-user::fb-user.table.mobile'))
                    ->searchable()
                    ->sortable()
                    ->localeDigit()
                    ->visible(config('fb-profile.mobile_required')),
                TextColumn::make('roles.name')
                    ->label(__('fb-user::fb-user.table.roles'))
                    ->badge(),
                ToggleColumn::make('active')
                    ->label(__('fb-user::fb-user.table.active'))
                    ->disabled(fn (?Model $record) => $record?->hasRole('super_admin')),
                TextColumn::make('created_by')
                    ->label(__('Created by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->sortable()
                    ->jDate()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_by')
                    ->label(__('Updated by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated at'))
                    ->sortable()
                    ->jDate()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active_users')
                    ->label(__('fb-user::fb-user.table.active_users'))
                    ->placeholder(__('fb-user::fb-user.table.active_users'))
                    ->trueLabel(__('fb-user::fb-user.table.all_users'))
                    ->falseLabel(__('fb-user::fb-user.table.inactive_users'))
                    ->queries(
                        true: fn (Builder $query) => $query,
                        false: fn (Builder $query) => $query->where('active', false),
                        blank: fn (Builder $query) => $query->where('active', true),
                    ),
                SelectFilter::make('roles')
                    ->label(__('fb-user::fb-user.table.roles'))
                    ->multiple()
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->when(
                                ! Auth::user()->hasRole('super_admin'),
                                fn (Builder $query) => $query->where('name', '<>', 'super_admin')
                            )
                    )
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (?Model $record) => ! $record->trashed()),
                RestoreAction::make()->recordTitle(fn (?Model $record): ?string => $record->name),
                ForceDeleteAction::make()->recordTitle(fn (?Model $record): ?string => $record->name),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('active-deactive-users')
                    ->label(__('fbase::fbase.resource.active.action_label'))
                    ->modalHeading(__('fbase::fbase.resource.active.action_heading'))
                    ->modalWidth('sm')
                    ->deselectRecordsAfterCompletion()
                    ->schema([
                        Toggle::make('active')->label(__('fbase::fbase.resource.active.form_label'))
                    ])
                    ->action(
                        fn (Collection $records, array $data) => $records
                            ->each(
                                function (Model $record) use ($data) {
                                    $result = array_filter($record->roles->toArray(), fn ($item) => isset($item['name']) && $item['name'] === 'super_admin');

                                    if (empty($result)) {
                                        $record->update(['active' => $data['active']]);
                                    }
                                }
                            )
                    ),
                DeleteBulkAction::make()
                    ->action(
                        fn (Collection $records) => $records
                            ->each(function (Model $record) {
                                $result = array_filter($record->roles->toArray(), fn ($item) => isset($item['name']) && $item['name'] === 'super_admin');

                                if (empty($result)) {
                                    $record->delete();
                                }
                            })
                    ),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->defaultSort('last_name', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }
}
