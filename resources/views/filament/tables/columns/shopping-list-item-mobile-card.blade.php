@php
    /** @var \App\Models\ShoppingListItem $record */
    $record = $getRecord();
    $productName = (string) ($record->product?->name ?? 'Produto');
    $quantity = number_format((float) ($record->quantity ?? 0), 3, ',', '.');
    $unitPrice = $record->selected_market_price ?? $record->best_price;
    $unitPriceLabel = $unitPrice !== null ? 'R$ ' . number_format((float) $unitPrice, 2, ',', '.') : '-';
    $marketName = (string) ($record->selected_market_name ?? $record->best_market_name ?? 'Sem histórico');
    $neighborhood = (string) ($record->selected_market_neighborhood ?? $record->best_market_neighborhood ?? '-');
    $subtotal = $unitPrice !== null ? ((float) $record->quantity * (float) $unitPrice) : null;
    $subtotalLabel = $subtotal !== null ? 'R$ ' . number_format((float) $subtotal, 2, ',', '.') : '-';
@endphp

<div style="width:100%;min-height:155px;margin:6px 0 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:16px;box-shadow:0 2px 6px rgba(15,23,42,.06);display:flex;flex-direction:column;justify-content:space-between;">
    <div>
        <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;">Produto</p>
        <p style="margin:6px 0 0;font-size:18px;font-weight:600;color:#111827;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px;">{{ $productName }}</p>
    </div>

    <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #d1d5db;">
        <p style="margin:0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Quantidade:</span> <span style="font-weight:700;color:#1d4ed8;">{{ $quantity }}</span></p>
        <p style="margin:4px 0 0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Menor preço:</span> <span style="font-weight:700;color:#047857;">{{ $unitPriceLabel }}</span></p>
        <p style="margin:4px 0 0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Onde comprar:</span> {{ $marketName }}</p>
        <p style="margin:4px 0 0;font-size:12px;color:#6b7280;"><span style="font-weight:500;">Bairro:</span> {{ $neighborhood }}</p>
        <p style="margin:4px 0 0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Subtotal:</span> <span style="font-weight:700;color:#111827;">{{ $subtotalLabel }}</span></p>
    </div>
</div>
