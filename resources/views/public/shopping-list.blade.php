<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $shoppingList->name }} - Lista de compras</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicon/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/shared-shopping-list.css') }}">
</head>
<body>
    <main class="page" id="shopping-list-app">
        <section class="hero">
            <h1>{{ $shoppingList->name }}</h1>
            <p>
                @if($shoppingList->shopping_date)
                    Data: {{ \Illuminate\Support\Carbon::parse($shoppingList->shopping_date)->format('d/m/Y') }}
                @else
                    Criada em {{ $shoppingList->created_at?->format('d/m/Y H:i') }}
                @endif
            </p>
            @if($shoppingList->notes)
                <p>{{ $shoppingList->notes }}</p>
            @endif
            <div class="total-wrap">
                <span>Total estimado da lista</span>
                <strong>R$ {{ number_format((float) ($totalAmount ?? 0), 2, ',', '.') }}</strong>
            </div>
            @if(($itemsWithoutPrice ?? 0) > 0)
                <p class="total-note">{{ $itemsWithoutPrice }} item(ns) sem preço para o cálculo.</p>
            @endif
            <div class="progress-wrap">
                <div class="progress-meta">
                    <span>Progresso da lista</span>
                    <span><span id="progress-done">0</span>/<span id="progress-total">0</span> concluídos (<span id="progress-percent">0%</span>)</span>
                </div>
                <div class="progress-track">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
            </div>
        </section>

        @if (session('shared_list_success'))
            <div class="flash">{{ session('shared_list_success') }}</div>
        @endif

        <section class="add-form">
            <h2>Como adicionar produtos</h2>
            <p style="margin:0;font-size:13px;color:var(--muted);">Use o botão <strong>+</strong> dentro de cada supermercado para inserir itens na seção “A Fazer”. Se o produto não existir, ele será cadastrado automaticamente.</p>
        </section>

        @foreach($groups as $marketIndex => $group)
            <section class="market" data-market="{{ $marketIndex }}" data-market-group>
                <div class="market-head">
                    <div class="market-head-main">
                        <h2>{{ $group['market_name'] }}</h2>
                        <p>{{ $group['market_address'] }}</p>
                    </div>
                    <button
                        class="add-btn"
                        type="button"
                        title="Adicionar produto"
                        data-open-add
                        data-market-id="{{ $group['market_id'] ?? '' }}"
                        data-market-name="{{ $group['market_name'] }}"
                    >+</button>
                </div>

                <h3 class="section-title"><i class="bi bi-cart3" aria-hidden="true"></i> A Fazer</h3>
                <div class="items" data-pending-list>
                    @foreach($group['items'] as $item)
                        <div class="item" data-item="{{ $item['id'] }}">
                            <input type="checkbox" data-toggle-done>
                            <div>
                                <p class="item-name">{{ $item['name'] }}</p>
                                <p class="item-meta">
                                    <span class="item-qty-badge">Qtd: {{ $item['quantity'] }}</span>
                                    <span>Últ. valor: {{ $item['unit_price'] }}</span>
                                </p>
                            </div>
                            <div class="item-actions">
                                <form
                                    method="POST"
                                    action="{{ route('shared-shopping-lists.items.remove', ['token' => $shoppingList->share_token, 'item' => $item['id']]) }}"
                                    class="js-remove-form"
                                >
                                    @csrf
                                    <button class="remove-btn" type="submit" title="Remover produto" aria-label="Remover produto">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="done-wrap">
                    <h3 class="section-title"><i class="bi bi-check2-circle" aria-hidden="true"></i> Feitos</h3>
                    <div class="items" data-done-list></div>
                    <div class="empty" data-done-empty>Nenhum item concluído.</div>
                </div>
            </section>
        @endforeach
    </main>

    <div class="scan-modal" id="scan-modal">
        <div class="scan-card">
            <div class="modal-head">
                <strong style="font-size:15px;">Leitor de código de barras</strong>
                <button class="modal-close-btn" type="button" id="scan-close-btn" aria-label="Fechar modal" title="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p style="margin:0;font-size:12px;color:var(--muted);">Aponte a câmera para o código. Se não funcionar, digite manualmente.</p>
            <div class="scan-video-wrap">
                <div id="scan-reader"></div>
                <div class="scan-guide" aria-hidden="true"></div>
            </div>
            <p class="scan-status" id="scan-status" role="status" aria-live="polite">Iniciando câmera...</p>
            <p class="scan-hint" id="scan-hint">Centralize o código dentro da moldura.</p>
            <div class="scan-toolbar">
                <div class="field">
                    <label for="scan_camera_select">Câmera</label>
                    <select id="scan_camera_select" class="btn" style="width:100%;justify-content:flex-start;"></select>
                </div>
                <button class="btn" type="button" id="scan-camera-switch-btn">Trocar</button>
            </div>
            <div class="product-grid">
                <div class="field">
                    <label for="scan_manual_barcode">Código manual</label>
                    <input id="scan_manual_barcode" type="text" inputmode="numeric" placeholder="Digite o código de barras">
                </div>
                <div class="row-actions">
                    <button class="btn btn-primary" type="button" id="scan-manual-submit-btn">Usar código</button>
                </div>
            </div>
        </div>
    </div>

    <div class="product-modal" id="search-modal">
        <div class="product-card">
            <div class="modal-head">
                <strong style="font-size:15px;">Buscar produto</strong>
                <button class="modal-close-btn" type="button" id="search-cancel-btn" aria-label="Fechar modal" title="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p style="margin:0;font-size:12px;color:var(--muted);" id="search-modal-market">Mercado: -</p>
            <div class="product-grid">
                <div class="field full">
                    <label for="search_product">Digite para buscar</label>
                    <input id="search_product" type="text" placeholder="Ex: arroz, leite, sabão...">
                </div>
                <div class="field">
                    <label for="search_quantity">Quantidade</label>
                    <input id="search_quantity" type="number" step="0.001" min="0.001" value="1">
                </div>
            </div>

            <div class="search-results" id="search-results">
                <div class="search-empty">Digite ao menos 2 caracteres para buscar.</div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-primary" type="button" id="search-create-btn">Cadastrar produto</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="product-modal">
        <div class="product-card">
            <div class="modal-head">
                <strong style="font-size:15px;">Cadastrar produto</strong>
                <button class="modal-close-btn" type="button" id="modal-cancel-btn" aria-label="Fechar modal" title="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p style="margin:0;font-size:12px;color:var(--muted);" id="product-modal-market">Mercado: -</p>
            <div class="product-grid">
                <div class="field full">
                    <label for="modal_product_name">Produto</label>
                    <input id="modal_product_name" type="text" placeholder="Ex: Arroz 5kg">
                </div>
                <div class="field">
                    <label for="modal_barcode">Código de barras (opcional)</label>
                    <input id="modal_barcode" type="text" inputmode="numeric">
                </div>
                <div class="field">
                    <label for="modal_quantity">Quantidade</label>
                    <input id="modal_quantity" type="number" step="0.001" min="0.001" value="1">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn" type="button" id="modal-scan-btn">Ler código de barras</button>
                <button class="btn btn-primary" type="button" id="modal-submit-btn">Cadastrar</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="confirm-modal">
        <div class="product-card">
            <div class="modal-head">
                <strong style="font-size:15px;">Confirmar produto lido</strong>
                <button class="modal-close-btn" type="button" id="confirm-cancel-btn" aria-label="Fechar modal" title="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p style="margin:0;font-size:12px;color:var(--muted);" id="confirm-market">Mercado: -</p>
            <p class="confirm-message" id="confirm-message"></p>
            <div class="product-grid">
                <div class="field full">
                    <label for="confirm_product_name">Produto</label>
                    <input id="confirm_product_name" type="text">
                </div>
                <div class="field">
                    <label for="confirm_barcode">Código de barras</label>
                    <input id="confirm_barcode" type="text" readonly>
                </div>
                <div class="field">
                    <label for="confirm_quantity">Quantidade</label>
                    <input id="confirm_quantity" type="number" step="0.001" min="0.001" value="1">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-primary" type="button" id="confirm-submit-btn">Cadastrar</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="remove-confirm-modal">
        <div class="product-card">
            <div class="modal-head">
                <strong style="font-size:15px;">Remover produto</strong>
                <button class="modal-close-btn" type="button" id="remove-confirm-cancel-btn" aria-label="Fechar modal" title="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p style="margin:0;font-size:13px;color:var(--muted);">Tem certeza que deseja remover este produto da lista?</p>
            <div class="modal-actions">
                <button class="btn btn-danger" type="button" id="remove-confirm-submit-btn">Remover</button>
            </div>
        </div>
    </div>

    <form id="shared-add-form" method="POST" action="{{ route('shared-shopping-lists.items.store', ['token' => $shoppingList->share_token]) }}" style="display:none;">
        @csrf
        <input type="hidden" name="market_id" id="hidden_market_id">
        <input type="hidden" name="product_id" id="hidden_product_id">
        <input type="hidden" name="product_name" id="hidden_product_name">
        <input type="hidden" name="barcode" id="hidden_barcode">
        <input type="hidden" name="quantity" id="hidden_quantity">
    </form>

    <div id="app-toast" class="app-toast" role="status" aria-live="polite"></div>

    <script>
        window.sharedShoppingListConfig = {
            token: @json($shoppingList->share_token),
            searchUrlTemplate: @json(route('shared-shopping-lists.products.search', ['token' => $shoppingList->share_token]) . '?q=__Q__&market_id=__MARKET__'),
            barcodeLookupTemplate: @json(route('shared-shopping-lists.barcode.lookup', ['token' => $shoppingList->share_token, 'barcode' => '__BARCODE__'])),
            reorderItemsUrl: @json(route('shared-shopping-lists.items.reorder', ['token' => $shoppingList->share_token])),
        };
    </script>
    <script src="{{ asset('js/shared-shopping-list.js') }}" defer></script>
</body>
</html>
