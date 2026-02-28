<?php

namespace App\Filament\Resources\ShoppingLists;

use App\Filament\Resources\ShoppingLists\Pages\CreateShoppingList;
use App\Filament\Resources\ShoppingLists\Pages\EditShoppingList;
use App\Filament\Resources\ShoppingLists\Pages\ListShoppingLists;
use App\Filament\Resources\ShoppingLists\Pages\ViewShoppingList;
use App\Filament\Resources\ShoppingLists\RelationManagers\ShoppingListItemsRelationManager;
use App\Filament\Resources\ShoppingLists\Schemas\ShoppingListForm;
use App\Filament\Resources\ShoppingLists\Schemas\ShoppingListInfolist;
use App\Filament\Resources\ShoppingLists\Tables\ShoppingListsTable;
use App\Models\ShoppingList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShoppingListResource extends Resource
{
    protected static ?string $model = ShoppingList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Listas de compra';

    protected static ?string $modelLabel = 'Lista de compra';

    protected static ?string $pluralModelLabel = 'Listas de compra';

    protected static UnitEnum|string|null $navigationGroup = 'Operacao';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ShoppingListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShoppingListsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShoppingListInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ShoppingListItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShoppingLists::route('/'),
            'create' => CreateShoppingList::route('/create'),
            'view' => ViewShoppingList::route('/{record}'),
            'edit' => EditShoppingList::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }
}
