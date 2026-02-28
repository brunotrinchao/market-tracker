<?php

namespace App\Filament\Resources\ShoppingLists\RelationManagers;

use App\Models\InvoiceItem;
use App\Models\Market;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ShoppingListItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produtos da lista';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $query->addSelect([
                    'best_price' => InvoiceItem::query()
                        ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
                        ->whereColumn('mp.product_id', 'shopping_list_items.product_id')
                        ->select('invoice_items.unit_price')
                        ->orderBy('invoice_items.unit_price')
                        ->limit(1),
                    'best_market_id' => Market::query()
                        ->join('market_products as mp', 'markets.id', '=', 'mp.market_id')
                        ->join('invoice_items as ii', 'ii.market_product_id', '=', 'mp.id')
                        ->whereColumn('mp.product_id', 'shopping_list_items.product_id')
                        ->select('markets.id')
                        ->orderBy('ii.unit_price')
                        ->limit(1),
                    'best_market_name' => Market::query()
                        ->join('market_products as mp', 'markets.id', '=', 'mp.market_id')
                        ->join('invoice_items as ii', 'ii.market_product_id', '=', 'mp.id')
                        ->whereColumn('mp.product_id', 'shopping_list_items.product_id')
                        ->select('markets.name')
                        ->orderBy('ii.unit_price')
                        ->limit(1),
                ]);
            })
            ->columns([
                ImageColumn::make('product.image')
                    ->label('Imagem')
                    ->defaultImageUrl('https://placehold.co/64x64/e5e7eb/6b7280?text=P')
                    ->imageSize(36)
                    ->square(),
                TextColumn::make('product.name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold'),
                TextColumn::make('quantity')
                    ->label('Qtd')
                    ->numeric(decimalPlaces: 3),
                TextColumn::make('best_price')
                    ->label('Menor preço')
                    ->money('BRL')
                    ->placeholder('-'),
                TextColumn::make('best_market_name')
                    ->label('Onde comprar')
                    ->badge()
                    ->color('success')
                    ->action(
                        Action::make('openMarketMap')
                            ->label('Ver no mapa')
                            ->modalHeading('Localização do supermercado')
                            ->modalWidth(Width::FiveExtraLarge)
                            ->modalSubmitAction(false)
                            ->modalContent(function ($record): View {
                                $market = null;

                                if ($record->best_market_id) {
                                    $market = Market::query()
                                        ->with('addresses')
                                        ->find($record->best_market_id);
                                }

                                $address = $market?->addresses?->first();

                                return view('filament.resources.shopping-lists.relation-managers.market-location-map', [
                                    'market' => [
                                        'id' => $market?->id,
                                        'name' => $market?->name,
                                        'address' => $address
                                            ? "{$address->street}, {$address->number}, {$address->neighborhood}, {$address->city} - {$address->state}"
                                            : null,
                                        'lat' => $address?->latitude !== null ? (float) $address->latitude : null,
                                        'lng' => $address?->longitude !== null ? (float) $address->longitude : null,
                                    ],
                                ]);
                            }),
                    )
                    ->placeholder('Sem histórico'),
                TextColumn::make('estimated_subtotal')
                    ->label('Subtotal estimado')
                    ->state(function ($record): ?float {
                        if ($record->best_price === null) {
                            return null;
                        }

                        return ((float) $record->quantity) * ((float) $record->best_price);
                    })
                    ->money('BRL')
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('addProducts')
                    ->label('Adicionar produtos à lista')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Adicionar produtos à lista')
                    ->modalSubmitActionLabel('Adicionar produtos')
                    ->form([
                        Repeater::make('items')
                            ->label('Produtos')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->required()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produto')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data): void {
                        $items = collect($data['items'] ?? [])
                            ->filter(fn (array $item): bool => filled($item['product_id'] ?? null) && filled($item['quantity'] ?? null));

                        $owner = $this->getOwnerRecord();

                        $items->each(function (array $item) use ($owner): void {
                            $existing = $owner->items()
                                ->where('product_id', $item['product_id'])
                                ->first();

                            if ($existing) {
                                $existing->update([
                                    'quantity' => (float) $existing->quantity + (float) $item['quantity'],
                                ]);

                                return;
                            }

                            $owner->items()->create([
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                            ]);
                        });

                        Notification::make()
                            ->title('Produtos adicionados à lista')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('best_market_id')
                    ->label('Supermercado')
                    ->options(fn () => Market::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return $query->having('best_market_id', '=', (int) $value);
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Excluir'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
