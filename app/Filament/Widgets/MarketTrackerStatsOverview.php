<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Market;
use App\Models\Product;
use App\Models\ShoppingList;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketTrackerStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::query()->count();
        $totalMarkets = Market::query()->count();
        $totalInvoices = Invoice::query()->count();
        $shoppingLists = ShoppingList::query()->count();

        return [
            Stat::make('Produtos cadastrados', (string) $totalProducts)
                ->description('Base de itens monitorados')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Mercados mapeados', (string) $totalMarkets)
                ->description('Locais com histórico de preço')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
            Stat::make('Notas importadas', (string) $totalInvoices)
                ->description('Histórico consolidado')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
            Stat::make('Listas de compra', (string) $shoppingLists)
                ->description('Listas criadas para planejamento')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),
        ];
    }
}
