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
        .hero-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
        }
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
        .item-price { font-size: 13px; font-weight: 700; color: #0f172a; }
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
        @media (max-width: 768px) {
            .page { padding: 10px; }
            .hero h1 { font-size: 20px; }
            .grid { grid-template-columns: 1fr; }
            .item { grid-template-columns: 22px minmax(0, 1fr); }
            .item-price { grid-column: 2; }
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
            <div class="hero-actions">
                <a class="btn btn-accent" href="{{ route('shared-shopping-lists.calendar.ics', ['token' => $shoppingList->share_token]) }}">Adicionar ao Apple Calendar (.ics)</a>
                <a class="btn btn-primary" href="https://calendar.google.com/calendar/render?action=TEMPLATE&text={{ urlencode('Lista de compras - ' . $shoppingList->name) }}&details={{ urlencode('Checklist: ' . route('shared-shopping-lists.show', ['token' => $shoppingList->share_token])) }}">Google Agenda</a>
            </div>
        </section>

        @if (session('shared_list_success'))
            <div class="flash">{{ session('shared_list_success') }}</div>
        @endif

        <section class="add-form">
            <h2>Adicionar produto</h2>
            <form method="POST" action="{{ route('shared-shopping-lists.items.store', ['token' => $shoppingList->share_token]) }}">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label for="product_name">Produto</label>
                        <input id="product_name" name="product_name" type="text" placeholder="Ex: Arroz 5kg" required>
                    </div>
                    <div class="field">
                        <label for="barcode">Código de barras (opcional)</label>
                        <input id="barcode" name="barcode" type="text" inputmode="numeric" placeholder="Ex: 7891234567890">
                    </div>
                    <div class="field">
                        <label for="quantity">Quantidade</label>
                        <input id="quantity" name="quantity" type="number" step="0.001" min="0.001" value="1" required>
                    </div>
                </div>
                <div class="row-actions" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit">Adicionar na lista</button>
                    <button class="btn" type="button" id="scan-barcode-btn">Ler código de barras</button>
                    <span style="font-size:12px;color:var(--muted);">Se o produto não existir, ele será cadastrado automaticamente.</span>
                </div>
            </form>
        </section>

        @foreach($groups as $marketIndex => $group)
            <section class="market" data-market="{{ $marketIndex }}">
                <div class="market-head">
                    <h2>{{ $group['market_name'] }}</h2>
                    <p>{{ $group['market_address'] }}</p>
                </div>

                <h3 class="section-title">A Fazer</h3>
                <div class="items" data-pending-list>
                    @foreach($group['items'] as $item)
                        <label class="item" data-item="{{ $item['id'] }}">
                            <input type="checkbox" data-toggle-done>
                            <div>
                                <p class="item-name">{{ $item['name'] }}</p>
                                <p class="item-meta">Qtd: {{ $item['quantity'] }} | Unit: {{ $item['unit_price'] }}</p>
                            </div>
                            <div class="item-price">{{ $item['subtotal'] }}</div>
                        </label>
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
            <video id="scan-video" autoplay playsinline></video>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button class="btn" type="button" id="scan-close-btn">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const STORAGE_KEY = 'shared-shopping-list:' + @json($shoppingList->share_token);
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

            const markets = document.querySelectorAll('[data-market]');

            const syncEmptyState = (marketEl) => {
                const doneList = marketEl.querySelector('[data-done-list]');
                const empty = marketEl.querySelector('[data-done-empty]');
                empty.style.display = doneList.children.length ? 'none' : 'block';
            };

            markets.forEach((marketEl) => {
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
                    });
                });

                syncEmptyState(marketEl);
            });

            const scanBtn = document.getElementById('scan-barcode-btn');
            const scanModal = document.getElementById('scan-modal');
            const scanCloseBtn = document.getElementById('scan-close-btn');
            const scanVideo = document.getElementById('scan-video');
            const barcodeInput = document.getElementById('barcode');
            let scanStream = null;
            let detectorInterval = null;

            const stopScanner = () => {
                if (detectorInterval) {
                    clearInterval(detectorInterval);
                    detectorInterval = null;
                }
                if (scanStream) {
                    scanStream.getTracks().forEach((track) => track.stop());
                    scanStream = null;
                }
                if (scanVideo) {
                    scanVideo.srcObject = null;
                }
                scanModal.style.display = 'none';
            };

            const startScanner = async () => {
                if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) {
                    alert('Leitura por câmera não suportada neste navegador. Digite o código manualmente.');
                    return;
                }

                try {
                    scanStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false,
                    });

                    scanVideo.srcObject = scanStream;
                    scanModal.style.display = 'flex';

                    const detector = new BarcodeDetector({
                        formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39'],
                    });

                    detectorInterval = setInterval(async () => {
                        if (!scanVideo || scanVideo.readyState < 2) {
                            return;
                        }

                        const codes = await detector.detect(scanVideo);
                        if (codes.length > 0 && codes[0].rawValue) {
                            barcodeInput.value = codes[0].rawValue;
                            stopScanner();
                        }
                    }, 450);
                } catch (error) {
                    stopScanner();
                    alert('Não foi possível abrir a câmera neste momento.');
                }
            };

            scanBtn?.addEventListener('click', startScanner);
            scanCloseBtn?.addEventListener('click', stopScanner);
            scanModal?.addEventListener('click', (event) => {
                if (event.target === scanModal) {
                    stopScanner();
                }
            });
        })();
    </script>
</body>
</html>
