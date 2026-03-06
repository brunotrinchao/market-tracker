<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $shoppingList->name }} - Lista de compras</title>
    <style>
        :root {
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --primary: #0f766e;
            --primary-soft: #dff7f4;
            --done-bg: #f8fafc;
            --accent: #0b5fff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 600px at -20% -20%, #c7f9f0 0%, rgba(199, 249, 240, 0) 65%),
                radial-gradient(1200px 600px at 120% -30%, #dbeafe 0%, rgba(219, 234, 254, 0) 60%),
                var(--bg);
        }
        .page {
            max-width: 980px;
            margin: 0 auto;
            padding: 16px;
            display: grid;
            gap: 14px;
        }
        .hero {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        }
        .hero h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
        }
        .hero p {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--muted);
        }
        .progress-wrap {
            margin-top: 14px;
            display: grid;
            gap: 8px;
        }
        .progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }
        .progress-track {
            width: 100%;
            height: 10px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
            transition: width .18s ease;
        }
        .btn {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            border-radius: 10px;
            min-height: 40px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-primary {
            border-color: #0f766e;
            background: #0f766e;
            color: #fff;
        }
        .btn-accent {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }
        .btn-danger {
            border-color: #b91c1c;
            background: #b91c1c;
            color: #fff;
        }
        .add-form {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .add-form h2 {
            margin: 0 0 10px;
            font-size: 17px;
        }
        .grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1.4fr 1fr .6fr;
        }
        .field {
            display: grid;
            gap: 6px;
        }
        .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }
        .field input {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 14px;
            background: #fff;
        }
        .row-actions {
            display: flex;
            align-items: end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .flash {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
            font-weight: 600;
        }
        .market {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .market-head {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
        }
        .market.is-collapsed > .section-title,
        .market.is-collapsed > .items,
        .market.is-collapsed > .done-wrap {
            display: none;
        }
        .market-head-main { min-width: 0; }
        .market-head h2 {
            margin: 0;
            font-size: 17px;
        }
        .market-head p {
            margin: 4px 0 0;
            font-size: 12px;
            color: var(--muted);
        }
        .section-title {
            margin: 0;
            padding: 10px 14px;
            font-size: 12px;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: var(--muted);
            background: #fff;
            border-bottom: 1px solid var(--line);
        }
        .items {
            display: grid;
            gap: 8px;
            padding: 10px;
            background: #fff;
        }
        .item {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: start;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            background: #fff;
        }
        .item.done {
            background: var(--done-bg);
            opacity: 0.85;
        }
        .item input[type="checkbox"] {
            margin-top: 3px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        .item-name { margin: 0; font-size: 14px; font-weight: 600; }
        .item-meta { margin: 4px 0 0; font-size: 12px; color: var(--muted); }
        .item-actions {
            display: grid;
            gap: 6px;
            justify-items: end;
        }
        .remove-btn {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            border-radius: 8px;
            min-height: 30px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .done-wrap {
            border-top: 1px dashed var(--line);
            background: #fcfcfd;
        }
        .empty {
            padding: 10px 14px;
            font-size: 13px;
            color: var(--muted);
            background: #fff;
        }
        .scan-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 16px;
        }
        .scan-card {
            width: min(560px, 100%);
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--line);
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .scan-card video {
            width: 100%;
            max-height: 58vh;
            background: #000;
            border-radius: 10px;
        }
        .add-btn {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            font-size: 24px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .product-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1001;
            padding: 16px;
        }
        .product-card {
            width: min(560px, 100%);
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--line);
            padding: 14px;
            display: grid;
            gap: 10px;
        }
        .search-results {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            overflow: auto;
            max-height: 280px;
        }
        .search-item {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--line);
            padding: 10px;
            background: #fff;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
        }
        .search-item:last-child { border-bottom: 0; }
        .search-item:hover { background: #f8fafc; }
        .search-name { margin: 0; font-size: 13px; font-weight: 600; color: #111827; }
        .search-meta { margin: 3px 0 0; font-size: 12px; color: var(--muted); }
        .search-empty {
            padding: 12px;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            background: #fff;
        }
        .product-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr;
        }
        .product-grid .field.full { grid-column: 1 / -1; }
        @media (max-width: 768px) {
            .page { padding: 10px; }
            .hero h1 { font-size: 20px; }
            .grid { grid-template-columns: 1fr; }
            .product-grid { grid-template-columns: 1fr; }
            .item { grid-template-columns: 22px minmax(0, 1fr); }
            .item-actions { grid-column: 2; justify-items: start; }
        }
    </style>
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
            <section class="market" data-market="{{ $marketIndex }}">
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

                <h3 class="section-title">A Fazer</h3>
                <div class="items" data-pending-list>
                    @foreach($group['items'] as $item)
                        <div class="item" data-item="{{ $item['id'] }}">
                            <input type="checkbox" data-toggle-done>
                            <div>
                                <p class="item-name">{{ $item['name'] }}</p>
                                <p class="item-meta">Qtd: {{ $item['quantity'] }} | Últ. valor: {{ $item['unit_price'] }}</p>
                            </div>
                            <div class="item-actions">
                                <form
                                    method="POST"
                                    action="{{ route('shared-shopping-lists.items.remove', ['token' => $shoppingList->share_token, 'item' => $item['id']]) }}"
                                    class="js-remove-form"
                                >
                                    @csrf
                                    <button class="remove-btn" type="submit">Remover</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="done-wrap">
                    <h3 class="section-title">Feitos</h3>
                    <div class="items" data-done-list></div>
                    <div class="empty" data-done-empty>Nenhum item concluído.</div>
                </div>
            </section>
        @endforeach
    </main>

    <div class="scan-modal" id="scan-modal">
        <div class="scan-card">
            <strong style="font-size:15px;">Leitor de código de barras</strong>
            <p style="margin:0;font-size:12px;color:var(--muted);">Aponte a câmera para o código. Se não funcionar, digite manualmente.</p>
            <video id="scan-video" autoplay playsinline muted></video>
            <div class="product-grid">
                <div class="field">
                    <label for="scan_manual_barcode">Código manual</label>
                    <input id="scan_manual_barcode" type="text" inputmode="numeric" placeholder="Digite o código de barras">
                </div>
                <div class="row-actions">
                    <button class="btn btn-primary" type="button" id="scan-manual-submit-btn">Usar código</button>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button class="btn" type="button" id="scan-close-btn">Fechar</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="search-modal">
        <div class="product-card">
            <strong style="font-size:15px;">Buscar produto</strong>
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

            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="button" id="search-cancel-btn">Cancelar</button>
                <button class="btn btn-primary" type="button" id="search-create-btn">Cadastrar produto</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="product-modal">
        <div class="product-card">
            <strong style="font-size:15px;">Cadastrar produto</strong>
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
            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="button" id="modal-scan-btn">Ler código de barras</button>
                <button class="btn" type="button" id="modal-cancel-btn">Cancelar</button>
                <button class="btn btn-primary" type="button" id="modal-submit-btn">Cadastrar</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="confirm-modal">
        <div class="product-card">
            <strong style="font-size:15px;">Confirmar produto lido</strong>
            <p style="margin:0;font-size:12px;color:var(--muted);" id="confirm-market">Mercado: -</p>
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
            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="button" id="confirm-cancel-btn">Cancelar</button>
                <button class="btn btn-primary" type="button" id="confirm-submit-btn">Cadastrar</button>
            </div>
        </div>
    </div>

    <div class="product-modal" id="remove-confirm-modal">
        <div class="product-card">
            <strong style="font-size:15px;">Remover produto</strong>
            <p style="margin:0;font-size:13px;color:var(--muted);">Tem certeza que deseja remover este produto da lista?</p>
            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="button" id="remove-confirm-cancel-btn">Cancelar</button>
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

    <script>
        (function () {
            const STORAGE_KEY = 'shared-shopping-list:' + @json($shoppingList->share_token);
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

            const markets = document.querySelectorAll('[data-market]');
            const progressDoneEl = document.getElementById('progress-done');
            const progressTotalEl = document.getElementById('progress-total');
            const progressPercentEl = document.getElementById('progress-percent');
            const progressBarEl = document.getElementById('progress-bar');

            const updateProgress = () => {
                const allItems = document.querySelectorAll('[data-item]');
                const doneItems = document.querySelectorAll('[data-item] [data-toggle-done]:checked');

                const total = allItems.length;
                const done = doneItems.length;
                const percent = total > 0 ? Math.round((done / total) * 100) : 0;

                if (progressDoneEl) progressDoneEl.textContent = String(done);
                if (progressTotalEl) progressTotalEl.textContent = String(total);
                if (progressPercentEl) progressPercentEl.textContent = percent + '%';
                if (progressBarEl) progressBarEl.style.width = percent + '%';
            };

            const syncEmptyState = (marketEl) => {
                const doneList = marketEl.querySelector('[data-done-list]');
                const empty = marketEl.querySelector('[data-done-empty]');
                empty.style.display = doneList.children.length ? 'none' : 'block';
            };

            markets.forEach((marketEl) => {
                const marketHead = marketEl.querySelector('.market-head');
                const pendingList = marketEl.querySelector('[data-pending-list]');
                const doneList = marketEl.querySelector('[data-done-list]');

                marketEl.querySelectorAll('[data-item]').forEach((itemEl) => {
                    const itemId = itemEl.getAttribute('data-item');
                    const checkbox = itemEl.querySelector('[data-toggle-done]');

                    if (saved[itemId]) {
                        checkbox.checked = true;
                        itemEl.classList.add('done');
                        doneList.appendChild(itemEl);
                    }

                    checkbox.addEventListener('change', () => {
                        if (checkbox.checked) {
                            itemEl.classList.add('done');
                            doneList.appendChild(itemEl);
                            saved[itemId] = true;
                        } else {
                            itemEl.classList.remove('done');
                            pendingList.appendChild(itemEl);
                            delete saved[itemId];
                        }

                        localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
                        syncEmptyState(marketEl);
                        updateProgress();
                    });
                });

                marketHead?.addEventListener('click', (event) => {
                    if (event.target instanceof Element && event.target.closest('[data-open-add]')) {
                        return;
                    }

                    marketEl.classList.toggle('is-collapsed');
                });

                syncEmptyState(marketEl);
            });
            updateProgress();

            const openAddButtons = document.querySelectorAll('[data-open-add]');
            const searchModal = document.getElementById('search-modal');
            const searchModalMarket = document.getElementById('search-modal-market');
            const searchInput = document.getElementById('search_product');
            const searchQuantityInput = document.getElementById('search_quantity');
            const searchResults = document.getElementById('search-results');
            const searchCancelBtn = document.getElementById('search-cancel-btn');
            const searchCreateBtn = document.getElementById('search-create-btn');
            const productModal = document.getElementById('product-modal');
            const confirmModal = document.getElementById('confirm-modal');
            const productModalMarket = document.getElementById('product-modal-market');
            const confirmMarket = document.getElementById('confirm-market');
            const modalProductName = document.getElementById('modal_product_name');
            const modalBarcode = document.getElementById('modal_barcode');
            const modalQuantity = document.getElementById('modal_quantity');
            const confirmProductName = document.getElementById('confirm_product_name');
            const confirmBarcode = document.getElementById('confirm_barcode');
            const confirmQuantity = document.getElementById('confirm_quantity');
            const modalScanBtn = document.getElementById('modal-scan-btn');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');
            const modalSubmitBtn = document.getElementById('modal-submit-btn');
            const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
            const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
            const removeForms = document.querySelectorAll('.js-remove-form');
            const removeConfirmModal = document.getElementById('remove-confirm-modal');
            const removeConfirmCancelBtn = document.getElementById('remove-confirm-cancel-btn');
            const removeConfirmSubmitBtn = document.getElementById('remove-confirm-submit-btn');

            const scanModal = document.getElementById('scan-modal');
            const scanCloseBtn = document.getElementById('scan-close-btn');
            const scanVideo = document.getElementById('scan-video');
            const scanManualBarcode = document.getElementById('scan_manual_barcode');
            const scanManualSubmitBtn = document.getElementById('scan-manual-submit-btn');
            const hiddenMarketId = document.getElementById('hidden_market_id');
            const hiddenProductId = document.getElementById('hidden_product_id');
            const hiddenProductName = document.getElementById('hidden_product_name');
            const hiddenBarcode = document.getElementById('hidden_barcode');
            const hiddenQuantity = document.getElementById('hidden_quantity');
            const sharedAddForm = document.getElementById('shared-add-form');
            let scanStream = null;
            let detectorInterval = null;
            let zxingControls = null;
            let zxingLoadingPromise = null;
            let currentMarketId = '';
            let currentMarketName = '';
            let searchDebounceTimer = null;
            let removeTargetForm = null;

            const closeSearchModal = () => { searchModal.style.display = 'none'; };
            const openSearchModal = () => { searchModal.style.display = 'flex'; };
            const closeProductModal = () => { productModal.style.display = 'none'; };
            const closeConfirmModal = () => { confirmModal.style.display = 'none'; };
            const openProductModal = () => { productModal.style.display = 'flex'; };
            const openConfirmModal = () => { confirmModal.style.display = 'flex'; };
            const closeRemoveConfirmModal = () => {
                removeConfirmModal.style.display = 'none';
                removeTargetForm = null;
            };
            const openRemoveConfirmModal = (form) => {
                removeTargetForm = form;
                removeConfirmModal.style.display = 'flex';
            };

            const submitSharedItem = ({ marketId, productId, productName, barcode, quantity }) => {
                hiddenMarketId.value = marketId || '';
                hiddenProductId.value = productId || '';
                hiddenProductName.value = productName || '';
                hiddenBarcode.value = barcode || '';
                hiddenQuantity.value = quantity || '1';
                sharedAddForm.submit();
            };

            const handleDetectedBarcode = async (barcodeValue) => {
                stopScanner();

                try {
                    const lookupTemplate = @json(route('shared-shopping-lists.barcode.lookup', ['token' => $shoppingList->share_token, 'barcode' => '__BARCODE__']));
                    const lookupUrl = lookupTemplate.replace('__BARCODE__', encodeURIComponent(barcodeValue));
                    const response = await fetch(lookupUrl);
                    let payload = null;
                    if (response.ok) {
                        payload = await response.json();
                    }

                    confirmBarcode.value = barcodeValue;
                    confirmProductName.value = payload?.suggested_name || ('Produto ' + barcodeValue);
                    confirmQuantity.value = modalQuantity.value || '1';
                    confirmMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
                    openConfirmModal();
                } catch (error) {
                    confirmBarcode.value = barcodeValue;
                    confirmProductName.value = 'Produto ' + barcodeValue;
                    confirmQuantity.value = modalQuantity.value || '1';
                    confirmMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
                    openConfirmModal();
                }
            };

            const ensureZxingLoaded = () => {
                if (window.ZXingBrowser) {
                    return Promise.resolve();
                }

                if (zxingLoadingPromise) {
                    return zxingLoadingPromise;
                }

                zxingLoadingPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = '/js/zxing-browser.min.js';
                    script.async = true;
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Falha ao carregar biblioteca de leitura.'));
                    document.head.appendChild(script);
                });

                return zxingLoadingPromise;
            };

            const openCameraStream = async () => {
                const constraintsList = [
                    { video: { facingMode: { exact: 'environment' } }, audio: false },
                    { video: { facingMode: { ideal: 'environment' } }, audio: false },
                    { video: true, audio: false },
                ];

                let lastError = null;
                for (const constraints of constraintsList) {
                    try {
                        return await navigator.mediaDevices.getUserMedia(constraints);
                    } catch (error) {
                        lastError = error;
                    }
                }

                throw lastError || new Error('Falha ao abrir câmera.');
            };

            const renderSearchResults = (products) => {
                if (!searchResults) return;

                if (!Array.isArray(products) || products.length === 0) {
                    searchResults.innerHTML = '<div class="search-empty">Nenhum produto encontrado. Use “Cadastrar produto”.</div>';
                    return;
                }

                searchResults.innerHTML = '';

                products.forEach((product) => {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'search-item';

                    const left = document.createElement('div');
                    const name = document.createElement('p');
                    name.className = 'search-name';
                    name.textContent = product.name || 'Produto';
                    const meta = document.createElement('p');
                    meta.className = 'search-meta';
                    const lastPriceLabel = product.last_price !== null && product.last_price !== undefined
                        ? ('Últ. valor: R$ ' + Number(product.last_price).toFixed(2).replace('.', ','))
                        : 'Últ. valor: -';
                    meta.textContent = (product.barcode ? ('Código: ' + product.barcode + ' | ') : '') + lastPriceLabel;
                    left.appendChild(name);
                    left.appendChild(meta);

                    const add = document.createElement('span');
                    add.className = 'btn';
                    add.textContent = 'Adicionar';

                    row.appendChild(left);
                    row.appendChild(add);

                    row.addEventListener('click', () => {
                        submitSharedItem({
                            marketId: currentMarketId,
                            productId: product.id,
                            productName: product.name || '',
                            barcode: product.barcode || '',
                            quantity: searchQuantityInput?.value || '1',
                        });
                    });

                    searchResults.appendChild(row);
                });
            };

            const searchProducts = async (term) => {
                const q = (term || '').trim();
                if (q.length < 2) {
                    renderSearchResults([]);
                    return;
                }
                if (!currentMarketId) {
                    searchResults.innerHTML = '<div class="search-empty">Este mercado não possui catálogo de produtos. Use “Cadastrar produto”.</div>';
                    return;
                }

                try {
                    const searchTemplate = @json(route('shared-shopping-lists.products.search', ['token' => $shoppingList->share_token]) . '?q=__Q__&market_id=__MARKET__');
                    const url = searchTemplate
                        .replace('__Q__', encodeURIComponent(q))
                        .replace('__MARKET__', encodeURIComponent(currentMarketId));
                    const response = await fetch(url);
                    const payload = response.ok ? await response.json() : { data: [] };
                    renderSearchResults(payload?.data || []);
                } catch (error) {
                    renderSearchResults([]);
                }
            };

            const stopScanner = () => {
                if (detectorInterval) {
                    clearInterval(detectorInterval);
                    detectorInterval = null;
                }
                if (scanStream) {
                    scanStream.getTracks().forEach((track) => track.stop());
                    scanStream = null;
                }
                if (zxingControls) {
                    zxingControls.stop();
                    zxingControls = null;
                }
                if (scanVideo) {
                    scanVideo.srcObject = null;
                }
                if (scanManualBarcode) {
                    scanManualBarcode.value = '';
                }
                scanModal.style.display = 'none';
            };

            const startScanner = async () => {
                if (!navigator.mediaDevices?.getUserMedia || !scanVideo) {
                    openProductModal();
                    setTimeout(() => modalBarcode?.focus(), 60);
                    alert('Este navegador não permite abrir câmera aqui. Digite o código manualmente.');
                    return;
                }

                try {
                    scanStream = await openCameraStream();
                    scanVideo.srcObject = scanStream;
                    scanModal.style.display = 'flex';

                    if (window.BarcodeDetector) {
                        const detector = new BarcodeDetector({
                            formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39'],
                        });

                        detectorInterval = setInterval(async () => {
                            if (!scanVideo || scanVideo.readyState < 2) {
                                return;
                            }

                            const codes = await detector.detect(scanVideo);
                            if (codes.length > 0 && codes[0].rawValue) {
                                handleDetectedBarcode(codes[0].rawValue);
                            }
                        }, 450);

                        return;
                    }

                    try {
                        await ensureZxingLoaded();
                        const codeReader = new window.ZXingBrowser.BrowserMultiFormatReader();
                        zxingControls = await codeReader.decodeFromConstraints(
                            { video: { facingMode: { ideal: 'environment' } }, audio: false },
                            scanVideo,
                            (result) => {
                                if (result?.getText) {
                                    handleDetectedBarcode(result.getText());
                                }
                            }
                        );
                    } catch (libraryError) {
                        alert('A câmera foi aberta, mas a leitura automática não está disponível agora. Digite o código manualmente.');
                    }
                } catch (error) {
                    stopScanner();
                    openProductModal();
                    setTimeout(() => modalBarcode?.focus(), 60);
                    const message = error?.message ? String(error.message) : 'Erro desconhecido.';
                    alert('Não foi possível abrir a câmera. Erro: ' + message + '. Verifique permissão de câmera no navegador.');
                }
            };

            openAddButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    currentMarketId = button.getAttribute('data-market-id') || '';
                    currentMarketName = button.getAttribute('data-market-name') || '';
                    searchModalMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
                    productModalMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
                    if (searchInput) searchInput.value = '';
                    if (searchQuantityInput) searchQuantityInput.value = '1';
                    renderSearchResults([]);
                    openSearchModal();
                });
            });

            searchInput?.addEventListener('input', () => {
                if (searchDebounceTimer) {
                    clearTimeout(searchDebounceTimer);
                }

                searchDebounceTimer = setTimeout(() => {
                    searchProducts(searchInput.value);
                }, 240);
            });

            searchCancelBtn?.addEventListener('click', closeSearchModal);
            searchCreateBtn?.addEventListener('click', () => {
                closeSearchModal();
                modalProductName.value = searchInput?.value?.trim() || '';
                modalBarcode.value = '';
                modalQuantity.value = searchQuantityInput?.value || '1';
                openProductModal();
            });

            modalCancelBtn?.addEventListener('click', closeProductModal);
            modalSubmitBtn?.addEventListener('click', () => {
                if (!modalProductName.value.trim()) {
                    alert('Informe o nome do produto.');
                    return;
                }

                submitSharedItem({
                    marketId: currentMarketId,
                    productId: '',
                    productName: modalProductName.value.trim(),
                    barcode: modalBarcode.value.trim(),
                    quantity: modalQuantity.value || '1',
                });
            });

            modalScanBtn?.addEventListener('click', () => {
                closeProductModal();
                startScanner();
            });
            scanManualSubmitBtn?.addEventListener('click', () => {
                const manualCode = scanManualBarcode?.value?.trim() || '';
                if (!manualCode) {
                    alert('Informe o código de barras.');
                    return;
                }

                handleDetectedBarcode(manualCode);
            });

            confirmCancelBtn?.addEventListener('click', closeConfirmModal);
            confirmSubmitBtn?.addEventListener('click', () => {
                if (!confirmProductName.value.trim()) {
                    alert('Informe o nome do produto.');
                    return;
                }

                submitSharedItem({
                    marketId: currentMarketId,
                    productId: '',
                    productName: confirmProductName.value.trim(),
                    barcode: confirmBarcode.value.trim(),
                    quantity: confirmQuantity.value || '1',
                });
            });

            removeForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    openRemoveConfirmModal(form);
                });
            });
            removeConfirmCancelBtn?.addEventListener('click', closeRemoveConfirmModal);
            removeConfirmSubmitBtn?.addEventListener('click', () => {
                if (removeTargetForm) {
                    removeTargetForm.submit();
                }
            });

            scanCloseBtn?.addEventListener('click', stopScanner);
            scanModal?.addEventListener('click', (event) => {
                if (event.target === scanModal) {
                    stopScanner();
                }
            });
            productModal?.addEventListener('click', (event) => {
                if (event.target === productModal) {
                    closeProductModal();
                }
            });
            searchModal?.addEventListener('click', (event) => {
                if (event.target === searchModal) {
                    closeSearchModal();
                }
            });
            confirmModal?.addEventListener('click', (event) => {
                if (event.target === confirmModal) {
                    closeConfirmModal();
                }
            });
            removeConfirmModal?.addEventListener('click', (event) => {
                if (event.target === removeConfirmModal) {
                    closeRemoveConfirmModal();
                }
            });
        })();
    </script>
</body>
</html>
