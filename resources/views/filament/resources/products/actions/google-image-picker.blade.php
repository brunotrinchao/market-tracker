<div wire:init="searchGoogleImages" style="display:grid;gap:16px;">
    <div style="display:grid;grid-template-columns:220px 1fr;gap:16px;">
        <div style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:8px;">
            <div style="margin-bottom:8px;font-size:12px;font-weight:600;color:#6b7280;">Imagem atual</div>
            <div style="width:100%;aspect-ratio:1/1;overflow:hidden;border-radius:10px;background:#f3f4f6;">
                <img
                    src="{{ $this->record->image ?: 'https://placehold.co/420x420/e5e7eb/6b7280?text=Produto' }}"
                    alt="Imagem atual do produto"
                    style="width:100%;height:100%;object-fit:cover;"
                    onerror="this.onerror=null;this.src='https://placehold.co/420x420/e5e7eb/6b7280?text=Produto';"
                />
            </div>
        </div>

        <div style="display:grid;gap:12px;">
            <div style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:12px;">
                <div style="margin-bottom:8px;font-size:14px;font-weight:700;color:#111827;">Buscar no Google</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <input
                        type="text"
                        wire:model.live="imageSearchTerm"
                        wire:keydown.enter="searchGoogleImages"
                        placeholder="Ex: {{ $this->record->name }}"
                        style="flex:1;min-width:220px;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:13px;"
                    />
                    <button
                        type="button"
                        wire:click="searchGoogleImages"
                        style="border:0;border-radius:8px;background:#2563eb;color:#fff;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;"
                    >
                        Buscar
                    </button>
                    <a
                        href="{{ 'https://www.google.com/search?tbm=isch&hl=pt-BR&q=' . urlencode($this->imageSearchTerm ?: $this->record->name) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="display:inline-flex;align-items:center;justify-content:center;border:1px solid #93c5fd;border-radius:8px;background:#eff6ff;color:#1d4ed8;padding:8px 14px;font-size:13px;font-weight:600;text-decoration:none;"
                    >
                        Abrir Google
                    </a>
                </div>
                <div style="margin-top:8px;font-size:12px;color:#6b7280;">Dica: pressione Enter para buscar.</div>
            </div>

            <div style="border:1px solid #a7f3d0;border-radius:12px;background:#ecfdf5;padding:12px;">
                <div style="margin-bottom:8px;font-size:14px;font-weight:700;color:#065f46;">Salvar por URL (recomendado)</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <input
                        type="url"
                        wire:model.live="manualImageUrl"
                        placeholder="Cole a URL da imagem (https://...)"
                        style="flex:1;min-width:220px;border:1px solid #6ee7b7;border-radius:8px;padding:8px 10px;font-size:13px;"
                    />
                    <button
                        type="button"
                        wire:click="saveManualImageUrl"
                        style="border:0;border-radius:8px;background:#059669;color:#fff;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;"
                    >
                        Salvar URL
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="searchGoogleImages" style="border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;padding:10px;font-size:13px;color:#4b5563;">
        Buscando imagens...
    </div>

    @if (!empty($this->imageSearchResults))
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
            @foreach ($this->imageSearchResults as $imageUrl)
                <div style="overflow:hidden;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
                    <div style="width:100%;aspect-ratio:1/1;background:#f3f4f6;">
                        <img
                            src="{{ $imageUrl }}"
                            alt="Resultado de imagem"
                            loading="lazy"
                            style="width:100%;height:100%;object-fit:cover;"
                            onerror="this.onerror=null;this.src='https://placehold.co/320x320/e5e7eb/6b7280?text=Imagem';"
                        />
                    </div>
                    <div style="border-top:1px solid #f3f4f6;padding:8px;display:grid;gap:6px;">
                        <div style="font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ parse_url($imageUrl, PHP_URL_HOST) ?: 'origem desconhecida' }}
                        </div>
                        <button
                            type="button"
                            x-on:click.prevent="
                                if (!confirm('Salvar esta imagem no produto?')) { return }
                                $wire.selectGoogleImage('{{ rawurlencode($imageUrl) }}')
                            "
                            style="border:0;border-radius:8px;background:#2563eb;color:#fff;padding:7px 10px;font-size:12px;font-weight:600;cursor:pointer;"
                        >
                            Usar esta imagem
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="border:1px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;font-size:13px;color:#6b7280;">
            Nenhum resultado ainda. Faça uma busca ou salve por URL.
        </div>
    @endif
</div>
