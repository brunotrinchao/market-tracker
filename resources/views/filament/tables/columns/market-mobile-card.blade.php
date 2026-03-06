@php
    /** @var \App\Models\Market $record */
    $record = $getRecord();
    $name = (string) ($record->name ?? 'Sem nome');
    $logo = $record->logo;
    $address = $record->addresses->first();
    $location = $address ? trim(implode(' - ', array_filter([$address->city, $address->state]))) : 'Endereço não informado';
    $productsCount = (int) ($record->market_products_count ?? 0);
    $invoicesCount = (int) ($record->invoices_count ?? 0);
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<div style="width:100%;min-height:150px;margin:6px 0 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:16px;box-shadow:0 2px 6px rgba(15,23,42,.06);display:flex;flex-direction:column;justify-content:space-between;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
        <div style="min-width:0;">
            <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;">Mercado</p>
            <p style="margin:6px 0 0;font-size:18px;font-weight:600;color:#111827;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px;">{{ $name }}</p>
            <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">{{ $location }}</p>
        </div>
        @if(filled($logo))
            <img
                src="{{ $logo }}"
                alt="Logo do mercado"
                style="width:40px;height:40px;flex-shrink:0;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb;background:#fff;"
            >
        @else
            <span style="display:inline-flex;width:40px;height:40px;flex-shrink:0;align-items:center;justify-content:center;border-radius:999px;background:#6b7280;color:#fff;font-size:14px;font-weight:700;">
                {{ $initials !== '' ? $initials : 'M' }}
            </span>
        @endif
    </div>
    <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #d1d5db;">
        <p style="margin:0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Produtos:</span> <span style="font-weight:700;color:#1d4ed8;">{{ $productsCount }}</span></p>
        <p style="margin:4px 0 0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Notas:</span> <span style="font-weight:700;color:#047857;">{{ $invoicesCount }}</span></p>
    </div>
</div>
