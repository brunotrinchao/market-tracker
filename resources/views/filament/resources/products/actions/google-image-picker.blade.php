<div class="space-y-4" wire:init="searchGoogleImages">
    <div class="flex flex-col gap-2 md:flex-row">
        <input
            type="text"
            wire:model.live="imageSearchTerm"
            placeholder="Ex: {{$this->record->name}}"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        />
        <button
            type="button"
            wire:click="searchGoogleImages"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
            Buscar no Google
        </button>
    </div>

    <div class="text-xs text-gray-500">
        Clique em uma imagem para salvar automaticamente no produto.
    </div>

    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm">
        <div class="mb-2 font-semibold text-blue-900">Método recomendado</div>
        <div class="mb-3 text-blue-800">
            Abra o Google Imagens, copie a URL da imagem desejada e cole abaixo para salvar.
        </div>
        <div class="mb-3">
            <a
                href="{{ 'https://www.google.com/search?tbm=isch&hl=pt-BR&q=' . urlencode($this->imageSearchTerm ?: $this->record->name) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-blue-700 ring-1 ring-blue-300 hover:bg-blue-100"
            >
                Abrir Google Imagens
            </a>
        </div>
        <div class="flex flex-col gap-2 md:flex-row">
            <input
                type="url"
                wire:model.live="manualImageUrl"
                placeholder="Cole aqui a URL da imagem (https://...)"
                class="w-full rounded-lg border border-blue-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
            />
            <button
                type="button"
                wire:click="saveManualImageUrl"
                class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
            >
                Salvar imagem
            </button>
        </div>
    </div>

    <div wire:loading wire:target="searchGoogleImages" class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">
        Buscando imagens no Google...
    </div>

    @if (!empty($this->imageSearchResults))
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach ($this->imageSearchResults as $imageUrl)
                <button
                    type="button"
                    x-on:click.prevent="
                        if (!confirm('Deseja salvar esta imagem no produto?')) { return }
                        $wire.selectGoogleImage('{{ rawurlencode($imageUrl) }}')
                    "
                    class="group overflow-hidden rounded-xl border border-gray-200 bg-white text-left transition hover:-translate-y-0.5 hover:border-blue-500 hover:shadow-sm"
                    title="Selecionar imagem"
                >
                    <div class="aspect-square w-full bg-gray-100">
                        <img
                            src="{{ $imageUrl }}"
                            alt="Resultado de imagem"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='https://placehold.co/320x320/e5e7eb/6b7280?text=Imagem';"
                        />
                    </div>
                    <div class="border-t border-gray-100 px-2 py-1.5 text-[11px] text-gray-500 group-hover:text-blue-700">
                        Clique para selecionar
                    </div>
                </button>
            @endforeach
        </div>
    @else
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
            Nenhuma imagem carregada ainda. Faça uma busca acima.
        </div>
    @endif
</div>
