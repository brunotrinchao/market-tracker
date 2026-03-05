<?php

namespace App\Filament\Resources\ShoppingLists\RelationManagers;

use App\Models\Address;
use App\Models\InvoiceItem;
use App\Models\Market;
use App\Models\MarketProduct;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\HtmlString;
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
                $this->makeProductSelect(),
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
                    'best_market_neighborhood' => Address::query()
                        ->join('markets', 'addresses.market_id', '=', 'markets.id')
                        ->join('market_products as mp', 'markets.id', '=', 'mp.market_id')
                        ->join('invoice_items as ii', 'ii.market_product_id', '=', 'mp.id')
                        ->whereColumn('mp.product_id', 'shopping_list_items.product_id')
                        ->select('addresses.neighborhood')
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
                    ->weight('semi-bold')
                    ->action(
                        Action::make('manageShoppingListItem')
                            ->label('Gerenciar item')
                            ->slideOver()
                            ->modalHeading(fn ($record): string => 'Item da lista - ' . ($record->product->name ?? 'Produto'))
                            ->modalSubmitActionLabel('Salvar')
                            ->modalCancelActionLabel('Cancelar')
                            ->extraModalFooterActions(fn (Action $action): array => [
                                $action->makeModalSubmitAction('remove', arguments: ['remove' => true])
                                    ->label('Remover da lista')
                                    ->color('danger'),
                            ])
                            ->fillForm(fn ($record): array => [
                                'quantity' => (float) $record->quantity,
                            ])
                            ->form([
                                Placeholder::make('product_image')
                                    ->hiddenLabel()
                                    ->content(function ($record): HtmlString {
                                        $image = $record->product?->image ?: 'https://placehold.co/960x420/e5e7eb/6b7280?text=Produto';

                                        return new HtmlString("<img src=\"{$image}\" alt=\"Produto\" style=\"width:100%;max-width:100%;height:220px;object-fit:cover;border-radius:12px;\" />");
                                    }),
                                Placeholder::make('product_name')
                                    ->label('Produto')
                                    ->content(fn ($record): string => $record->product->name ?? '-'),
                                Placeholder::make('best_price_info')
                                    ->label('Menor preço')
                                    ->content(fn ($record): string => $record->best_price !== null ? 'R$ ' . number_format((float) $record->best_price, 2, ',', '.') : '-'),
                                Placeholder::make('best_market_name_info')
                                    ->label('Onde comprar')
                                    ->content(fn ($record): string => $record->best_market_name ?? '-'),
                                Placeholder::make('best_market_neighborhood_info')
                                    ->label('Bairro')
                                    ->content(fn ($record): string => $record->best_market_neighborhood ?? '-'),
                                Placeholder::make('estimated_subtotal_info')
                                    ->label('Subtotal estimado')
                                    ->content(function ($record): string {
                                        if ($record->best_price === null) {
                                            return '-';
                                        }

                                        $subtotal = ((float) $record->quantity) * ((float) $record->best_price);

                                        return 'R$ ' . number_format($subtotal, 2, ',', '.');
                                    }),
                                TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->required(),
                            ])
                            ->action(function ($record, array $data, array $arguments): void {
                                if ($arguments['remove'] ?? false) {
                                    $record->delete();

                                    Notification::make()
                                        ->title('Produto removido da lista')
                                        ->success()
                                        ->send();

                                    return;
                                }

                                $record->update([
                                    'quantity' => (float) $data['quantity'],
                                ]);

                                Notification::make()
                                    ->title('Quantidade atualizada com sucesso')
                                    ->success()
                                    ->send();
                            }),
                    ),
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
                TextColumn::make('best_market_neighborhood')
                    ->label('Bairro')
                    ->placeholder('-'),
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
                                $this->makeProductSelect()
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('quantity')
                                    ->label(fn (callable $get): string => $this->buildQuantityLabel(
                                        $this->resolveProductUnitCode($get('product_id')),
                                    ))
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
            ]);
    }

    private function makeProductSelect(): Select
    {
        return Select::make('product_id')
            ->label('Produto')
            ->relationship('product', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->label('Nome do produto')
                    ->required()
                    ->maxLength(255),
                TextInput::make('original_name')
                    ->label('Nome original (opcional)')
                    ->maxLength(255),
            ])
            ->createOptionUsing(function (array $data): int {
                $name = trim((string) ($data['name'] ?? ''));
                $originalName = trim((string) ($data['original_name'] ?? ''));

                $product = Product::query()->create([
                    'name' => $name,
                    'original_name' => $originalName !== '' ? $originalName : $name,
                ]);

                return (int) $product->getKey();
            });
    }

    private function resolveProductUnitCode(mixed $productId): ?string
    {
        if (! filled($productId)) {
            return null;
        }

        return MarketProduct::query()
            ->where('product_id', (int) $productId)
            ->orderByDesc('updated_at')
            ->value('unit');
    }

    private function buildQuantityLabel(?string $unitCode): string
    {
        if (! $unitCode) {
            return 'Quantidade';
        }

        return 'Quantidade (' . $this->formatUnitLabel($unitCode) . ')';
    }

    private function formatUnitLabel(?string $unitCode): string
    {
        if (! $unitCode) {
            return '-';
        }

        $unitCode = strtoupper(trim($unitCode));

        $map = [
            'KG' => 'Quilo (KG)',
            'G' => 'Grama (G)',
            'L' => 'Litro (L)',
            'LT' => 'Litro (LT)',
            'ML' => 'Mililitro (ML)',
            'UN' => 'Unidade (UN)',
            'CX' => 'Caixa (CX)',
            'PT' => 'Pacote (PT)',
            'FR' => 'Frasco (FR)',
        ];

        return $map[$unitCode] ?? $unitCode;
    }

}
