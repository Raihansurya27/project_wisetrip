<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationServiceOrderResource\Pages;
use App\Filament\Resources\DestinationServiceOrderResource\RelationManagers;
use App\Models\Destination;
use App\Models\DestinationServiceOrder;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class DestinationServiceOrderResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = DestinationServiceOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Trips';

    protected static ?string $navigationLabel = 'Orders';

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'restore', 'restore_any', 'replicate', 'reorder', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'view_all', 'accept', 'reject'];
    }

    public static function getEloquentQuery(): Builder
    {
        if (Gate::check('view_all_destination')) {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->ownedByUser(auth()->user()->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('destination_id')
                ->options(Destination::all()->pluck('name', 'id'))
                ->dehydrated(false)
                ->reactive(),
            Forms\Components\Select::make('service_id')
                ->options(function ($get) {
                    $destination = Destination::find($get('destination_id'));
                    return $destination->services->pluck('name', 'id');
                })
                ->hidden(fn($get) => $get('destination_id') == null)
                ->reactive(),
            Forms\Components\TextInput::make('quantity'),
            Forms\Components\DatePicker::make('date_booking'),
            Forms\Components\DatePicker::make('date_payment'),
            Forms\Components\FileUpload::make('payment_proof')->image(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice')
                    ->prefix('#')
                    ->inline(false),
                Tables\Columns\TextColumn::make('service.destination.name')->searchable(),
                Tables\Columns\TextColumn::make('service.name')->searchable(),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('total')->formatStateUsing(function (DestinationServiceOrder $record) {
                    return $record->service->price * $record->quantity;
                }),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('date_booking'),
                Tables\Columns\TextColumn::make('date_payment'),
                Tables\Columns\TextColumn::make('created_at')->since(),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->size('100%')
                    ->extraAttributes(['style' => 'aspect-ratio: 16/9; overflow: hidden;', 'class' => 'w-full'], true)
                    ->extraImgAttributes(['style' => 'object-fit: cover;'])
                    ->square(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('accept')
                        ->action(fn($record) => $record->accept())
                        ->requiresConfirmation()
                        ->hidden(fn($record) => $record->status == 'Accepted')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->authorize('accept'),
                    Tables\Actions\Action::make('reject')
                        ->action(fn($record) => $record->reject())
                        ->requiresConfirmation()
                        ->hidden(fn($record) => $record->status == 'Rejected')
                        ->icon('heroicon-o-x')
                        ->color('danger')
                        ->authorize('reject'),
                ]),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
        // ->contentGrid(['default' => 1]);
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
            'index' => Pages\ListDestinationServiceOrders::route('/'),
            'create' => Pages\CreateDestinationServiceOrder::route('/create'),
            'view' => Pages\ViewDestinationServiceOrder::route('/{record}'),
            'edit' => Pages\EditDestinationServiceOrder::route('/{record}/edit'),
        ];
    }
}
