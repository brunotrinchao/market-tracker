@php
    /** @var \App\Models\Category $record */
    $record = $getRecord();
    $name = (string) ($record->name ?? '-');
    $slug = (string) ($record->slug ?? '-');
    $productsCount = (int) ($record->products_count ?? $record->products()->count());
    $updatedAt = $record->updated_at?->format('d/m/Y H:i') ?? '-';
@endphp

<div style="width:100%;min-height:138px;margin:6px 0 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:16px;box-shadow:0 2px 6px rgba(15,23,42,.06);display:flex;flex-direction:column;justify-content:space-between;">
    <div>
        <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;">Categoria</p>
        <p style="margin:6px 0 0;font-size:18px;font-weight:600;color:#111827;">{{ $name }}</p>
    </div>
    <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #d1d5db;">
        <p style="margin:0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Slug:</span> {{ $slug }}</p>
        <p style="margin:4px 0 0;font-size:13px;color:#4b5563;"><span style="font-weight:500;color:#6b7280;">Produtos:</span> <span style="font-weight:700;color:#1d4ed8;">{{ $productsCount }}</span></p>
        <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">Atualizada: {{ $updatedAt }}</p>
    </div>
</div>
