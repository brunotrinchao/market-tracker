<?php

namespace App\Filament\Resources\ShoppingLists\RelationManagers;

use App\Models\Address;
use App\Models\InvoiceItem;
use App\Models\Market;
use App\Models\MarketProduct;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;

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
                $selects = [
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
                ];

                if ($this->hasMarketSelectionColumn()) {
                    $selects['selected_market_name'] = Market::query()
                        ->whereColumn('markets.id', 'shopping_list_items.market_id')
                        ->select('markets.name')
                        ->limit(1);
                    $selects['selected_market_neighborhood'] = Address::query()
                        ->whereColumn('addresses.market_id', 'shopping_list_items.market_id')
                        ->select('addresses.neighborhood')
                        ->limit(1);
                    $selects['selected_market_price'] = InvoiceItem::query()
                        ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
                        ->join('invoices as inv', 'invoice_items.invoice_id', '=', 'inv.id')
                        ->whereColumn('mp.product_id', 'shopping_list_items.product_id')
                        ->whereColumn('mp.market_id', 'shopping_list_items.market_id')
                        ->select('invoice_items.unit_price')
                        ->orderByDesc('inv.issued_at')
                        ->orderByDesc('invoice_items.created_at')
                        ->limit(1);
                } else {
                    $selects['selected_market_name'] = DB::raw('null');
                    $selects['selected_market_neighborhood'] = DB::raw('null');
                    $selects['selected_market_price'] = DB::raw('null');
                }

                $query->addSelect($selects);
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
                                    ->content(fn ($record): string => $record->selected_market_name ?? $record->best_market_name ?? '-'),
                                Placeholder::make('best_market_neighborhood_info')
                                    ->label('Bairro')
                                    ->content(fn ($record): string => $record->selected_market_neighborhood ?? $record->best_market_neighborhood ?? '-'),
                                Placeholder::make('estimated_subtotal_info')
                                    ->label('Subtotal estimado')
                                    ->content(function ($record): string {
                                        $unitPrice = $record->selected_market_price ?? $record->best_price;

                                        if ($unitPrice === null) {
                                            return '-';
                                        }

                                        $subtotal = ((float) $record->quantity) * ((float) $unitPrice);

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
                    ->state(fn ($record): ?string => $record->selected_market_name ?? $record->best_market_name)
                    ->action(
                        Action::make('chooseMarketForItem')
                            ->label('Selecionar mercado')
                            ->modalHeading(fn ($record): string => 'Onde comprar - ' . ($record->product->name ?? 'Produto'))
                            ->modalWidth(Width::FiveExtraLarge)
                            ->modalSubmitActionLabel('Salvar mercado')
                            ->fillForm(function ($record): array {
                                return [
                                    'market_id' => $this->hasMarketSelectionColumn()
                                        ? ($record->market_id ?: $record->best_market_id)
                                        : $record->best_market_id,
                                ];
                            })
                            ->form([
                                Radio::make('market_id')
                                    ->label('Mercados (do mais barato para o mais caro)')
                                    ->options(function ($record): array {
                                        return collect($this->getMarketOffersForProduct((int) $record->product_id))
                                            ->mapWithKeys(function (array $offer): array {
                                                $priceLabel = $offer['unit_price'] !== null
                                                    ? 'R$ ' . number_format((float) $offer['unit_price'], 2, ',', '.')
                                                    : 'Sem preço';

                                                return [
                                                    (string) $offer['market_id'] => "{$offer['market_name']} - {$priceLabel}",
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->descriptions(function ($record): array {
                                        return collect($this->getMarketOffersForProduct((int) $record->product_id))
                                            ->mapWithKeys(function (array $offer): array {
                                                $diff = ((float) ($offer['diff_percent'] ?? 0)) <= 0
                                                    ? 'Mais barato (referência)'
                                                    : number_format((float) $offer['diff_percent'], 2, ',', '.') . '% acima do mais barato';

                                                return [
                                                    (string) $offer['market_id'] => "{$offer['market_address']} | {$diff}",
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->columns(1)
                                    ->required(fn ($record): bool => ! empty($this->getMarketOffersForProduct((int) $record->product_id)))
                                    ->disabled(fn ($record): bool => empty($this->getMarketOffersForProduct((int) $record->product_id)))
                                    ->helperText(fn ($record): ?string => empty($this->getMarketOffersForProduct((int) $record->product_id))
                                        ? 'Sem histórico de preço para selecionar mercado.'
                                        : 'Clique em um mercado da lista para selecionar.'),
                            ])
                            ->action(function ($record, array $data): void {
                                if (! $this->hasMarketSelectionColumn()) {
                                    Notification::make()
                                        ->title('Atualize o banco para habilitar a seleção de mercado')
                                        ->body('Execute as migrations pendentes para criar a coluna market_id em shopping_list_items.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                if (! filled($data['market_id'] ?? null)) {
                                    Notification::make()
                                        ->title('Não há mercado disponível para este produto')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $record->update([
                                    'market_id' => (int) $data['market_id'],
                                ]);

                                Notification::make()
                                    ->title('Mercado do item atualizado com sucesso')
                                    ->success()
                                    ->send();
                            }),
                    )
                    ->placeholder('Sem histórico'),
                TextColumn::make('best_market_neighborhood')
                    ->label('Bairro')
                    ->state(fn ($record): ?string => $record->selected_market_neighborhood ?? $record->best_market_neighborhood)
                    ->placeholder('-'),
                TextColumn::make('estimated_subtotal')
                    ->label('Subtotal estimado')
                    ->state(function ($record): ?float {
                        $unitPrice = $record->selected_market_price ?? $record->best_price;

                        if ($unitPrice === null) {
                            return null;
                        }

                        return ((float) $record->quantity) * ((float) $unitPrice);
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

    private function hasMarketSelectionColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn !== null) {
            return $hasColumn;
        }

        $hasColumn = DbSchema::hasColumn('shopping_list_items', 'market_id');

        return $hasColumn;
    }

    /**
     * @return array<int, array{
     *     market_id:int,
     *     market_name:string,
     *     market_address:string,
     *     unit_price:?float,
     *     diff_percent:?float
     * }>
     */
    private function getMarketOffersForProduct(int $productId): array
    {
        $latestOffers = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets as m', 'mp.market_id', '=', 'm.id')
            ->leftJoin('addresses as a', 'a.market_id', '=', 'm.id')
            ->join('invoices as inv', 'invoice_items.invoice_id', '=', 'inv.id')
            ->where('mp.product_id', $productId)
            ->select([
                'm.id as market_id',
                'm.name as market_name',
                'a.street',
                'a.number',
                'a.neighborhood',
                'a.city',
                'a.state',
                'invoice_items.unit_price',
                'inv.issued_at',
                'invoice_items.created_at',
            ])
            ->orderBy('m.id')
            ->orderByDesc('inv.issued_at')
            ->orderByDesc('invoice_items.created_at')
            ->get()
            ->unique('market_id')
            ->values()
            ->map(function ($offer): array {
                $address = collect([
                    trim(implode(', ', array_filter([$offer->street, $offer->number]))),
                    $offer->neighborhood,
                    trim(implode(' - ', array_filter([$offer->city, $offer->state]))),
                ])
                    ->filter(fn (?string $part): bool => filled($part))
                    ->implode(', ');

                return [
                    'market_id' => (int) $offer->market_id,
                    'market_name' => (string) $offer->market_name,
                    'market_address' => $address !== '' ? $address : '-',
                    'unit_price' => $offer->unit_price !== null ? (float) $offer->unit_price : null,
                    'diff_percent' => null,
                ];
            })
            ->sortBy(function (array $offer): float {
                return $offer['unit_price'] ?? INF;
            })
            ->values();

        $cheapestPrice = $latestOffers
            ->pluck('unit_price')
            ->filter(fn ($price): bool => $price !== null)
            ->min();

        if ($cheapestPrice === null || (float) $cheapestPrice <= 0) {
            return $latestOffers->all();
        }

        return $latestOffers
            ->map(function (array $offer) use ($cheapestPrice): array {
                if ($offer['unit_price'] === null) {
                    return $offer;
                }

                $offer['diff_percent'] = round((((float) $offer['unit_price'] - (float) $cheapestPrice) / (float) $cheapestPrice) * 100, 2);

                return $offer;
            })
            ->all();
    }

}
