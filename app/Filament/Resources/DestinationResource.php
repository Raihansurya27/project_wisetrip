<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Filament\Resources\DestinationResource\RelationManagers;
use App\Models\Destination;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;
use Laravolt\Indonesia\Facade as Indonesia;
use Laravolt\Indonesia\Models\Provinsi;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('name'),
                    Forms\Components\Select::make('status')
                        ->options(['active', 'inactive']),
                    TinyEditor::make('description')
                        ->setRelativeUrls(false)
                        ->setConvertUrls(true)
                        ->setRemoveScriptHost(false),
                ])
                    ->columnSpan(['xl' => 2, 'default' => 'full']),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Location')
                        ->schema([

                            Forms\Components\Select::make('provinsi_id')
                                ->options(Provinsi::all()->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->afterStateUpdated(fn ($set) => $set('kecamatan_id', null) & $set('kelurahan_id', null) & $set('kota_id', null))
                                ->label('Provinsi')
                                ->dehydrated(false),
                            Forms\Components\Select::make('kota_id')
                                ->options(function ($get) {
                                    if ($get('provinsi_id') != null) {
                                        return Indonesia::findProvince($get('provinsi_id'), ['cities'])->cities->pluck('name', 'id');
                                    }
                                    return null;
                                })
                                ->dehydrated(false)
                                ->afterStateUpdated(fn ($set) => $set('kecamatan_id', null) & $set('kelurahan_id', null))
                                ->reactive()
                                ->required()
                                ->searchable()
                                ->label('Kota'),

                            Forms\Components\Select::make('kecamatan_id')
                                ->options(function ($get) {
                                    if ($get('kota_id') != null) {
                                        return Indonesia::findCity($get('kota_id'), ['districts'])->districts->pluck('name', 'id');
                                    }
                                    return null;
                                })
                                ->afterStateUpdated(fn ($set) => $set('kelurahan_id', null))
                                ->dehydrated(false)
                                ->reactive()
                                ->required()
                                ->searchable()
                                ->label('Kecamatan'),
                            Forms\Components\Select::make('kelurahan_id')
                                ->options(function ($get) {
                                    if ($get('kecamatan_id') != null) {
                                        return Indonesia::findDistrict($get('kecamatan_id'), ['villages'])->villages->pluck('name', 'id');
                                    }
                                    return null;
                                })
                                ->required()
                                ->searchable()
                                ->label('Kelurahan'),
                        ])
                        ->columns(1)
                        ->columnSpan('xs')
                        ->label('Location Information')
                        ->inlineLabel(true)
                ])->columnSpan(['default' => 'full', 'xl' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('kelurahan.name')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
