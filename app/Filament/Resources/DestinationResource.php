<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Filament\Resources\DestinationResource\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\DestinationResource\RelationManagers\ServicesRelationManager;
use App\Models\Destination;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;
use Laravolt\Indonesia\Facade as Indonesia;
use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;

class DestinationResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Trips';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'restore', 'restore_any', 'replicate', 'reorder', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'view_all',
            'approve', 'reject'
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        if (Gate::check('view_all_destination')) {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->whereBelongsTo(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\FileUpload::make('thumbnail')
                        ->image()
                        ->multiple(false),
                    Forms\Components\TextInput::make('name'),
                    TinyEditor::make('description')
                        ->setRelativeUrls(false)
                        ->setConvertUrls(true)
                        ->setRemoveScriptHost(false),
                    Forms\Components\FileUpload::make('images')
                        ->image()
                        ->multiple()
                ])->columnSpan(['xl' => 2, 'default' => 'full']),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Location')
                        ->schema([
                            Forms\Components\Select::make('province_code')
                                ->options(Province::all()->pluck('name', 'code'))
                                ->searchable()
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($set) {
                                    $set('district_code', null);
                                    $set('city_code', null);
                                    $set('village_code', null);
                                })
                                ->formatStateUsing(function ($get, $record) {
                                    if ($get('village_code') != null) {
                                        return $record->village->district->city->province->code;
                                    }
                                })
                                ->reactive(),
                            Forms\Components\Select::make('city_code')
                                ->options(function ($get, $set, $record) {
                                    if ($record != null && $get('district_code') != null) {
                                        return City::where('province_code', $record->village->district->city->province->code)->pluck('name', 'code');
                                    }
                                    return City::where('province_code', $get('province_code'))->pluck('name', 'code');
                                })
                                ->afterStateUpdated(function ($set) {
                                    $set('district_code', null);
                                    $set('village_code', null);
                                })
                                ->formatStateUsing(function ($record, $get) {
                                    if ($get('village_code') != null) {
                                        return $record->village->district->city->code;
                                    }
                                })
                                ->dehydrated(false)
                                ->searchable()
                                ->reactive(),
                            Forms\Components\Select::make('district_code')
                                ->options(function ($get, $set, $record) {
                                    if ($record != null && $get('city_code') == null) {
                                        return District::where('city_code', $record->village->district->city->code)->pluck('name', 'code');
                                    }
                                    return District::where('city_code', $get('city_code'))->pluck('name', 'code');
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($set) {
                                    $set('village_code', null);
                                })
                                ->formatStateUsing(function ($record, $get) {
                                    if ($get('village_code') != null) {
                                        return $record->village->district->code;
                                    }
                                })
                                ->dehydrated(false)
                                ->searchable(),
                            Forms\Components\Select::make('village_code')
                                ->options(function ($get, $set, $record) {
                                    if ($record != null && $get('district_code') == null) {
                                        return Village::where('district_code', $record->village->district->code)->pluck('name', 'code');
                                    }
                                    return Village::where('district_code', $get('district_code'))->pluck('name', 'code');
                                })
                                ->required()
                                ->searchable()
                                ->label('Kelurahan'),
                        ])
                        ->columns(1)
                        ->columnSpan('xs')
                        ->label('Location Information')
                        ->inlineLabel(true),
                ])->columnSpan(['default' => 'full', 'xl' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->size('100%')
                    ->extraAttributes(['style' => 'aspect-ratio: 16/9; overflow: hidden;', 'class' => 'w-full'], true)
                    ->extraImgAttributes(['style' => 'object-fit: cover;'])
                    ->square(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Need review',
                        'danger' => 'Rejected',
                        'success' => 'Approved'
                    ]),
                Tables\Columns\TextColumn::make('kelurahan.name'),
                Tables\Columns\TextColumn::make('user.name')->hidden(!Gate::check('view_all_destination')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->action(fn ($record) => $record->approve())
                    ->requiresConfirmation()
                    ->color('success')
                    ->button()
                    ->hidden(function () {
                        return Gate::check('approve_destination');
                    }),
                Tables\Actions\Action::make('reject')
                    ->action(fn ($record) => $record->reject())
                    ->requiresConfirmation()
                    ->color('danger')
                    ->button(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ]);
    }

    public static function getRelations(): array
    {
        return [ServicesRelationManager::class, OrdersRelationManager::class,];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'view' => Pages\ViewDestination::route('/{record}'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
