<?php

namespace App\Filament\Resources\ShoppingLists\Pages;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use App\Models\InvoiceItem;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewShoppingList extends ViewRecord
{
    protected static string $resource = ShoppingListResource::class;

    protected static ?string $title = 'Detalhes da lista de compra';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('shareShoppingList')
                ->label('Compartilhar lista')
                ->icon('heroicon-o-share')
                ->color('success')
                ->modalHeading('Compartilhar lista')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fechar')
                ->modalContent(function (): HtmlString {
                    $shareToken = $this->ensureShareToken();
                    $publicUrl = route('shared-shopping-lists.show', ['token' => $shareToken]);
                    $googleCalendarUrl = $this->buildGoogleCalendarUrl($publicUrl);
                    $appleCalendarUrl = route('shared-shopping-lists.calendar.ics', ['token' => $shareToken]);
                    $text = $this->buildShareText();
                    $encoded = urlencode($text);
                    $jsonText = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $publicUrlEncoded = urlencode($publicUrl);

                    $html = <<<HTML
<div style="display:grid;gap:12px;">
    <p style="font-size:13px;color:#6b7280;margin:0;">
        Escolha onde compartilhar. Em iPhone, o botao "Compartilhar..." abre WhatsApp, Notas e outros apps.
    </p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" onclick='(async()=>{const text={$jsonText}; if (navigator.share) { try { await navigator.share({ text }); } catch(e) {} } else { alert("Compartilhamento nativo nao suportado neste navegador."); }})()' style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;background:#111827;color:#fff;cursor:pointer;">
            Compartilhar...
        </button>
        <a href="https://wa.me/?text={$encoded}" target="_blank" style="padding:8px 12px;border-radius:8px;border:1px solid #16a34a;background:#16a34a;color:#fff;text-decoration:none;">
            WhatsApp
        </a>
        <a href="{$googleCalendarUrl}" target="_blank" style="padding:8px 12px;border-radius:8px;border:1px solid #2563eb;background:#2563eb;color:#fff;text-decoration:none;">
            Google Agenda
        </a>
        <a href="{$appleCalendarUrl}" target="_blank" style="padding:8px 12px;border-radius:8px;border:1px solid #0f766e;background:#0f766e;color:#fff;text-decoration:none;">
            Apple Calendar (.ics)
        </a>
        <a href="{$publicUrl}" target="_blank" style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#111827;text-decoration:none;">
            Abrir link público
        </a>
        <button type="button" onclick='(async()=>{const text={$jsonText}; try { await navigator.clipboard.writeText(text); alert("Texto copiado."); } catch(e) { alert("Nao foi possivel copiar."); }})()' style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#111827;cursor:pointer;">
            Copiar texto
        </button>
        <button type="button" onclick='(async()=>{const url=decodeURIComponent("{$publicUrlEncoded}"); try { await navigator.clipboard.writeText(url); alert("Link público copiado."); } catch(e) { alert("Nao foi possivel copiar o link."); }})()' style="padding:8px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#111827;cursor:pointer;">
            Copiar link público
        </button>
    </div>
    <input readonly value="{$publicUrl}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#f9fafb;" />
    <textarea readonly style="width:100%;min-height:280px;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">{$text}</textarea>
</div>
HTML;

                    return new HtmlString($html);
                }),
            Action::make('editShoppingList')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->slideOver()
                ->modalHeading('Editar lista de compra')
                ->fillForm(fn (): array => [
                    'name' => $this->record->name,
                    'shopping_date' => $this->record->shopping_date,
                    'notes' => $this->record->notes,
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Nome da lista')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('shopping_date')
                        ->label('Data'),
                    Textarea::make('notes')
                        ->label('Observacoes')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->record->update($data);
                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }

    private function buildShareText(): string
    {
        $items = $this->record->items()->with('product')->get();
        $groupedByMarket = [];
        foreach ($items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $offer = $this->resolveBestOfferForProduct((int) $product->id);

            $marketId = (string) ($offer['market_id'] ?? 'sem_mercado');
            $marketName = $offer['market_name'] ?? 'SEM SUPERMERCADO';
            $marketAddress = $offer['market_address'] ?? '-';
            $unitPrice = $offer['unit_price'] ?? null;
            $subtotal = $unitPrice !== null ? ((float) $item->quantity * (float) $unitPrice) : null;

            $groupedByMarket[$marketId] ??= [
                'name' => $marketName,
                'address' => $marketAddress,
                'items' => [],
            ];

            $groupedByMarket[$marketId]['items'][] = sprintf(
                '[ ] %s | %s | %s',
                $product->name,
                $this->formatQuantity((float) $item->quantity),
                $subtotal !== null ? $this->formatMoney($subtotal) : '-'
            );
        }

        if ($groupedByMarket === []) {
            return 'Lista vazia.';
        }

        $blocks = [];
        foreach ($groupedByMarket as $marketGroup) {
            $block = [];
            $block[] = '## ' . $marketGroup['name'];
            $block[] = $marketGroup['address'];
            $block[] = '';
            $block[] = $this->record->notes ? "Observações: {$this->record->notes}" : null;
            $block[] = '';
            $block[] = '';
            $block = array_merge($block, $marketGroup['items']);

            $blocks[] = implode(PHP_EOL, $block);
        }

        return implode(PHP_EOL . PHP_EOL . '--------------------------------------------' . PHP_EOL . PHP_EOL, $blocks);
    }

    private function ensureShareToken(): string
    {
        if (filled($this->record->share_token)) {
            return (string) $this->record->share_token;
        }

        $this->record->update([
            'share_token' => Str::random(40),
        ]);

        $this->record->refresh();

        return (string) $this->record->share_token;
    }

    private function buildGoogleCalendarUrl(string $publicUrl): string
    {
        $date = $this->record->shopping_date
            ? Carbon::parse($this->record->shopping_date)
            : Carbon::parse($this->record->created_at ?? now());

        $start = $date->copy()->startOfDay();
        $end = $start->copy()->addDay();

        $params = [
            'action' => 'TEMPLATE',
            'text' => 'Lista de compras - ' . $this->record->name,
            'dates' => $start->format('Ymd') . '/' . $end->format('Ymd'),
            'details' => 'Checklist público: ' . $publicUrl,
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    private function resolveBestOfferForProduct(int $productId): ?array
    {
        $offer = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets as m', 'mp.market_id', '=', 'm.id')
            ->leftJoin('addresses as a', 'a.market_id', '=', 'm.id')
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
            ])
            ->orderBy('invoice_items.unit_price')
            ->first();

        if (! $offer) {
            return null;
        }

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
        ];
    }

    private function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatQuantity(float $value): string
    {
        return number_format($value, 3, ',', '.');
    }
}
