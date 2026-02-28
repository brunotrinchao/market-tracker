<?php

namespace App\Filament\Resources\Markets;

use App\Filament\Resources\Markets\Pages\CreateMarket;
use App\Filament\Resources\Markets\Pages\EditMarket;
use App\Filament\Resources\Markets\Pages\ListMarkets;
use App\Filament\Resources\Markets\Pages\ViewMarket;
use App\Filament\Resources\Markets\RelationManagers\MarketProductsRelationManager;
use App\Filament\Resources\Markets\Schemas\MarketForm;
use App\Filament\Resources\Markets\Schemas\MarketInfolist;
use App\Filament\Resources\Markets\Tables\MarketsTable;
use App\Models\Market;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MarketResource extends Resource
{
    protected static ?string $model = Market::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Mercados';

    protected static ?string $modelLabel = 'Mercado';

    protected static ?string $pluralModelLabel = 'Mercados';

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MarketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MarketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MarketProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarkets::route('/'),
            'create' => CreateMarket::route('/create'),
            'view' => ViewMarket::route('/{record}'),
            'edit' => EditMarket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }
}
