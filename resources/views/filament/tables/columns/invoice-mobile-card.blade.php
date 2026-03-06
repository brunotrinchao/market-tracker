@php
    /** @var \App\Models\Invoice $record */
    $record = $getRecord();
    $marketName = (string) ($record->market?->name ?? 'Sem mercado');
    $issuedAt = $record->issued_at?->format('d/m/Y H:i') ?? '-';
    $total = 'R$ ' . number_format((float) ($record->total_amount ?? 0), 2, ',', '.');
    $itemsCount = (int) ($record->items_count ?? $record->items()->count());
    $accessKey = (string) ($record->access_key ?? '-');

    $initials = collect(explode(' ', trim($marketName)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<div style="width:100%;min-height:150px;margin:6px 0 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:16px;box-shadow:0 2px 6px rgba(15,23,42,.06);display:flex;flex-direction:column;justify-content:space-between;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
        <div style="min-width:0;">
            <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;line-height:1.25;">{{ $issuedAt }}</p>
            <p style="margin:6px 0 0;font-size:18px;font-weight:600;color:#111827;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px;">{{ $marketName }}</p>
        </div>
        <span style="display:inline-flex;width:40px;height:40px;flex-shrink:0;align-items:center;justify-content:center;border-radius:999px;background:#6b7280;color:#fff;font-size:14px;font-weight:700;">
            {{ $initials !== '' ? $initials : 'NF' }}
        </span>
    </div>

    <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #d1d5db;">
        <p style="margin:0;font-size:14px;color:#4b5563;">
            <span style="font-weight:500;color:#6b7280;">Itens:</span>
            <span style="font-weight:700;color:#1f2937;">{{ $itemsCount }}</span>
        </p>
        <p style="margin:4px 0 0;font-size:14px;color:#4b5563;">
            <span style="font-weight:500;color:#6b7280;">Valor:</span>
            <span style="font-weight:700;color:#047857;">{{ $total }}</span>
        </p>
        <p style="margin:4px 0 0;font-size:12px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <span style="font-weight:500;">Chave:</span> {{ $accessKey }}
        </p>
    </div>
</div>
