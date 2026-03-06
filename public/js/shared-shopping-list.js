document.addEventListener('DOMContentLoaded', () => {
    const config = window.sharedShoppingListConfig || {};
    if (!config.token) {
        return;
    }

    const STORAGE_KEY = 'shared-shopping-list:' + config.token;
    const lookupTemplate = config.barcodeLookupTemplate || '';
    const searchTemplate = config.searchUrlTemplate || '';
    const reorderItemsUrl = config.reorderItemsUrl || '';

    const toastEl = document.getElementById('app-toast');
    const markets = document.querySelectorAll('[data-market]');

    const progressDoneEl = document.getElementById('progress-done');
    const progressTotalEl = document.getElementById('progress-total');
    const progressPercentEl = document.getElementById('progress-percent');
    const progressBarEl = document.getElementById('progress-bar');

    const openAddButtons = document.querySelectorAll('[data-open-add]');
    const searchModal = document.getElementById('search-modal');
    const searchModalMarket = document.getElementById('search-modal-market');
    const searchInput = document.getElementById('search_product');
    const searchQuantityInput = document.getElementById('search_quantity');
    const searchResults = document.getElementById('search-results');
    const searchCancelBtn = document.getElementById('search-cancel-btn');
    const searchCreateBtn = document.getElementById('search-create-btn');

    const productModal = document.getElementById('product-modal');
    const productModalMarket = document.getElementById('product-modal-market');
    const modalProductName = document.getElementById('modal_product_name');
    const modalBarcode = document.getElementById('modal_barcode');
    const modalQuantity = document.getElementById('modal_quantity');
    const modalScanBtn = document.getElementById('modal-scan-btn');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const modalSubmitBtn = document.getElementById('modal-submit-btn');

    const confirmModal = document.getElementById('confirm-modal');
    const confirmMarket = document.getElementById('confirm-market');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmProductName = document.getElementById('confirm_product_name');
    const confirmBarcode = document.getElementById('confirm_barcode');
    const confirmQuantity = document.getElementById('confirm_quantity');
    const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
    const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
    const confirmDefaultSubmitLabel = confirmSubmitBtn?.textContent || 'Cadastrar';

    const removeForms = document.querySelectorAll('.js-remove-form');
    const removeConfirmModal = document.getElementById('remove-confirm-modal');
    const removeConfirmCancelBtn = document.getElementById('remove-confirm-cancel-btn');
    const removeConfirmSubmitBtn = document.getElementById('remove-confirm-submit-btn');

    const scanModal = document.getElementById('scan-modal');
    const scanCloseBtn = document.getElementById('scan-close-btn');
    const scanReader = document.getElementById('scan-reader');
    const scanStatus = document.getElementById('scan-status');
    const scanHint = document.getElementById('scan-hint');
    const scanCameraSelect = document.getElementById('scan_camera_select');
    const scanCameraSwitchBtn = document.getElementById('scan-camera-switch-btn');
    const scanManualBarcode = document.getElementById('scan_manual_barcode');
    const scanManualSubmitBtn = document.getElementById('scan-manual-submit-btn');

    const hiddenMarketId = document.getElementById('hidden_market_id');
    const hiddenProductId = document.getElementById('hidden_product_id');
    const hiddenProductName = document.getElementById('hidden_product_name');
    const hiddenBarcode = document.getElementById('hidden_barcode');
    const hiddenQuantity = document.getElementById('hidden_quantity');
    const sharedAddForm = document.getElementById('shared-add-form');
    const csrfToken = sharedAddForm?.querySelector('input[name="_token"]')?.value || '';

    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

    let toastTimer = null;
    let searchDebounceTimer = null;
    let searchRequestId = 0;
    let removeTargetForm = null;

    let currentMarketId = '';
    let currentMarketName = '';
    let confirmExistingProductId = '';

    let html5LoadPromise = null;
    let sortableLoadPromise = null;
    let html5Scanner = null;
    let scannerRunning = false;
    let scannerStarting = false;
    let scanHandled = false;
    let scanDeviceId = '';
    let scanHintTimer1 = null;
    let scanHintTimer2 = null;

    const closeSearchModal = () => { if (searchModal) searchModal.style.display = 'none'; };
    const openSearchModal = () => { if (searchModal) searchModal.style.display = 'flex'; };
    const closeProductModal = () => { if (productModal) productModal.style.display = 'none'; };
    const openProductModal = () => { if (productModal) productModal.style.display = 'flex'; };
    const closeConfirmModal = () => {
        if (confirmModal) confirmModal.style.display = 'none';
        confirmExistingProductId = '';
        setConfirmMessage('');
        if (confirmProductName) {
            confirmProductName.readOnly = false;
        }
        if (confirmSubmitBtn) {
            confirmSubmitBtn.textContent = confirmDefaultSubmitLabel;
        }
    };
    const openConfirmModal = () => { if (confirmModal) confirmModal.style.display = 'flex'; };

    const closeRemoveConfirmModal = () => {
        if (removeConfirmModal) removeConfirmModal.style.display = 'none';
        removeTargetForm = null;
    };

    const openRemoveConfirmModal = (form) => {
        removeTargetForm = form;
        if (removeConfirmModal) removeConfirmModal.style.display = 'flex';
    };

    const notify = (message, type = 'info') => {
        if (!toastEl) return;

        toastEl.textContent = message;
        toastEl.classList.remove('is-error', 'is-success');
        if (type === 'error') toastEl.classList.add('is-error');
        if (type === 'success') toastEl.classList.add('is-success');
        toastEl.classList.add('is-visible');

        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toastEl.classList.remove('is-visible', 'is-error', 'is-success');
        }, 2800);
    };

    const setButtonBusy = (button, busy, busyLabel = 'Carregando...') => {
        if (!button) return;
        if (busy) {
            if (!button.dataset.originalLabel) {
                button.dataset.originalLabel = button.textContent || '';
            }
            button.disabled = true;
            button.textContent = busyLabel;
            return;
        }
        button.disabled = false;
        if (button.dataset.originalLabel) {
            button.textContent = button.dataset.originalLabel;
        }
    };

    const setScanStatus = (message) => {
        if (scanStatus) scanStatus.textContent = message;
    };

    const setScanHint = (message) => {
        if (scanHint) scanHint.textContent = message;
    };

    const setConfirmMessage = (message, tone = '') => {
        if (!confirmMessage) return;
        confirmMessage.textContent = message || '';
        confirmMessage.classList.remove('is-existing', 'is-warning');
        if (tone) {
            confirmMessage.classList.add(tone);
        }
    };

    const submitSharedItem = ({ marketId, productId, productName, barcode, quantity }) => {
        if (!sharedAddForm) return;
        hiddenMarketId.value = marketId || '';
        hiddenProductId.value = productId || '';
        hiddenProductName.value = productName || '';
        hiddenBarcode.value = barcode || '';
        hiddenQuantity.value = quantity || '1';
        sharedAddForm.submit();
    };

    const ensureSortableLoaded = () => {
        if (window.Sortable) {
            return Promise.resolve();
        }

        if (sortableLoadPromise) {
            return sortableLoadPromise;
        }

        sortableLoadPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-sortablejs="1"]');
            if (existing) {
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', () => reject(new Error('Falha ao carregar SortableJS.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
            script.async = true;
            script.dataset.sortablejs = '1';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Falha ao carregar SortableJS.'));
            document.head.appendChild(script);
        });

        return sortableLoadPromise;
    };

    const persistMarketOrder = async (marketEl) => {
        if (!marketEl || !reorderItemsUrl || !csrfToken) return;

        const pendingList = marketEl.querySelector('[data-pending-list]');
        if (!pendingList) return;

        const orderedIds = Array.from(pendingList.querySelectorAll('[data-item]'))
            .map((itemEl) => Number(itemEl.getAttribute('data-item')))
            .filter((id) => Number.isInteger(id) && id > 0);

        if (orderedIds.length < 2) return;

        try {
            const response = await fetch(reorderItemsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ item_ids: orderedIds }),
            });

            if (!response.ok) {
                notify('Não foi possível salvar a ordem da lista.', 'error');
            }
        } catch (_error) {
            notify('Falha ao salvar a ordem. Verifique sua conexão.', 'error');
        }
    };

    const initSortableForMarket = async (marketEl) => {
        const pendingList = marketEl.querySelector('[data-pending-list]');
        if (!pendingList || pendingList.dataset.sortableReady === '1') {
            return;
        }

        try {
            await ensureSortableLoaded();

            window.Sortable.create(pendingList, {
                animation: 160,
                draggable: '[data-item]',
                filter: 'input,button,form,label,a',
                preventOnFilter: false,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                forceFallback: true,
                fallbackOnBody: true,
                onEnd: () => {
                    persistMarketOrder(marketEl);
                },
            });

            pendingList.dataset.sortableReady = '1';
        } catch (_error) {
            // Sem bloqueio da tela se não carregar a lib.
        }
    };

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
        if (doneList && empty) {
            empty.style.display = doneList.children.length ? 'none' : 'block';
        }
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
            left.className = 'search-item-left';

            const thumb = document.createElement('img');
            thumb.className = 'search-thumb';
            thumb.loading = 'lazy';
            thumb.src = product.image || 'https://placehold.co/88x88/e5e7eb/6b7280?text=P';
            thumb.alt = product.name || 'Produto';

            const info = document.createElement('div');
            const name = document.createElement('p');
            name.className = 'search-name';
            name.textContent = product.name || 'Produto';

            const meta = document.createElement('p');
            meta.className = 'search-meta';
            const lastPriceLabel = product.last_price !== null && product.last_price !== undefined
                ? ('Últ. valor: R$ ' + Number(product.last_price).toFixed(2).replace('.', ','))
                : 'Últ. valor: -';
            meta.textContent = (product.barcode ? ('Código: ' + product.barcode + ' | ') : '') + lastPriceLabel;

            const badges = document.createElement('div');
            badges.className = 'search-badges';

            if (product.in_list) {
                const inList = document.createElement('span');
                inList.className = 'search-badge in-list';
                inList.textContent = 'Já na lista';
                badges.appendChild(inList);
            }

            const usage = document.createElement('span');
            usage.className = 'search-badge';
            usage.textContent = 'Histórico: ' + (product.usage_count || 0);
            badges.appendChild(usage);

            if (product.last_price !== null && product.last_price !== undefined) {
                const price = document.createElement('span');
                price.className = 'search-badge price';
                price.textContent = 'Últ. preço: R$ ' + Number(product.last_price).toFixed(2).replace('.', ',');
                badges.appendChild(price);
            }

            info.appendChild(name);
            info.appendChild(meta);
            info.appendChild(badges);

            left.appendChild(thumb);
            left.appendChild(info);

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
            if (searchResults) {
                searchResults.innerHTML = '<div class="search-empty">Este mercado não possui catálogo de produtos. Use “Cadastrar produto”.</div>';
            }
            return;
        }

        try {
            const requestId = ++searchRequestId;
            if (searchResults) {
                searchResults.innerHTML = '<div class="search-loading">Buscando produtos...</div>';
            }

            const url = searchTemplate
                .replace('__Q__', encodeURIComponent(q))
                .replace('__MARKET__', encodeURIComponent(currentMarketId));

            const response = await fetch(url);
            const payload = response.ok ? await response.json() : { data: [] };

            if (requestId !== searchRequestId) return;
            renderSearchResults(payload?.data || []);
        } catch (_error) {
            renderSearchResults([]);
        }
    };

    const handleDetectedBarcode = async (barcodeValue) => {
        setScanHint('Código reconhecido. Processando...');
        await stopScanner();

        try {
            const lookupUrl = lookupTemplate.replace('__BARCODE__', encodeURIComponent(barcodeValue));
            const response = await fetch(lookupUrl);
            const payload = response.ok ? await response.json() : null;
            const isExistingProduct = Boolean(payload?.existing_product || payload?.source === 'local');
            const detectedName = payload?.suggested_name || ('Produto ' + barcodeValue);
            const detectedQuantity = modalQuantity?.value || '1';

            if (isExistingProduct) {
                confirmExistingProductId = String(payload?.existing_product_id || '');
                confirmBarcode.value = barcodeValue;
                confirmProductName.value = detectedName;
                confirmProductName.readOnly = true;
                confirmQuantity.value = detectedQuantity;
                confirmMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
                setConfirmMessage('Este produto já está cadastrado. Deseja adicionar na lista?', 'is-existing');
                if (confirmSubmitBtn) {
                    confirmSubmitBtn.textContent = 'Adicionar na lista';
                }
                openConfirmModal();
                return;
            }

            if (modalBarcode) modalBarcode.value = barcodeValue;
            if (modalProductName) modalProductName.value = detectedName;
            if (modalQuantity) modalQuantity.value = detectedQuantity;
            if (productModalMarket) {
                productModalMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
            }
            notify('Produto novo. Complete o cadastro para adicionar na lista.');
            openProductModal();
        } catch (_error) {
            confirmBarcode.value = barcodeValue;
            confirmProductName.value = 'Produto ' + barcodeValue;
            confirmProductName.readOnly = false;
            confirmQuantity.value = modalQuantity?.value || '1';
            confirmMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
            setConfirmMessage('Não foi possível consultar o cadastro. Revise os dados para continuar.', 'is-warning');
            if (confirmSubmitBtn) {
                confirmSubmitBtn.textContent = 'Cadastrar';
            }
            openConfirmModal();
        }
    };

    const ensureHtml5QrcodeLoaded = () => {
        if (window.Html5Qrcode) {
            return Promise.resolve();
        }

        if (html5LoadPromise) {
            return html5LoadPromise;
        }

        html5LoadPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-html5-qrcode="1"]');
            if (existing) {
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', () => reject(new Error('Falha ao carregar html5-qrcode.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.async = true;
            script.dataset.html5Qrcode = '1';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Falha ao carregar html5-qrcode.'));
            document.head.appendChild(script);
        });

        return html5LoadPromise;
    };

    const populateCameraOptions = async () => {
        if (!scanCameraSelect || !window.Html5Qrcode || typeof window.Html5Qrcode.getCameras !== 'function') {
            return;
        }

        try {
            const cameras = await window.Html5Qrcode.getCameras();
            const videoInputs = cameras || [];

            scanCameraSelect.innerHTML = '';

            videoInputs.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.id;
                option.textContent = camera.label || ('Câmera ' + (index + 1));
                scanCameraSelect.appendChild(option);
            });

            const preferred = videoInputs.find((camera) =>
                /back|rear|environment|traseira|externa/i.test((camera.label || '').toLowerCase())
            );

            if (scanDeviceId && videoInputs.some((camera) => camera.id === scanDeviceId)) {
                scanCameraSelect.value = scanDeviceId;
            } else if (preferred) {
                scanDeviceId = preferred.id;
                scanCameraSelect.value = preferred.id;
            } else if (videoInputs[0]) {
                scanDeviceId = videoInputs[0].id;
                scanCameraSelect.value = videoInputs[0].id;
            }
        } catch (_error) {
            // Falha silenciosa: permissões podem impedir listagem neste momento.
        }
    };

    const clearScanHintTimers = () => {
        if (scanHintTimer1) {
            clearTimeout(scanHintTimer1);
            scanHintTimer1 = null;
        }
        if (scanHintTimer2) {
            clearTimeout(scanHintTimer2);
            scanHintTimer2 = null;
        }
    };

    const stopScanner = async (hideModal = true) => {
        clearScanHintTimers();

        if (html5Scanner) {
            try {
                if (scannerRunning) {
                    await html5Scanner.stop();
                }
            } catch (_error) {
                // Ignore stop errors.
            }

            try {
                await html5Scanner.clear();
            } catch (_error) {
                // Ignore clear errors.
            }

            html5Scanner = null;
        }

        scannerRunning = false;
        scannerStarting = false;
        scanHandled = false;

        if (scanManualBarcode) {
            scanManualBarcode.value = '';
        }

        setScanStatus('Iniciando câmera...');
        setScanHint('Centralize o código dentro da moldura.');

        if (hideModal && scanModal) {
            scanModal.style.display = 'none';
        }
    };

    const startScanner = async (forceRestart = false, preferredCameraId = '') => {
        if (scannerStarting || (scannerRunning && !forceRestart)) {
            return;
        }

        if (!window.isSecureContext) {
            if (scanModal) scanModal.style.display = 'flex';
            setScanStatus('Câmera bloqueada fora de HTTPS.');
            setScanHint('Use HTTPS ou localhost e tente novamente.');
            notify('Câmera bloqueada: use HTTPS ou localhost.', 'error');
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (scanModal) scanModal.style.display = 'flex';
            setScanStatus('Câmera não suportada neste navegador.');
            setScanHint('Use o código manual abaixo.');
            notify('Este navegador não permite abrir câmera aqui. Digite o código manualmente abaixo.', 'error');
            return;
        }

        scannerStarting = true;
        await stopScanner(false);

        if (scanModal) scanModal.style.display = 'flex';
        setScanStatus('Abrindo câmera...');
        setScanHint('Centralize o código dentro da moldura.');

        try {
            await ensureHtml5QrcodeLoaded();
            await populateCameraOptions();

            if (!scanReader) {
                throw new Error('Leitor não inicializado.');
            }
            if (!scanReader.id) {
                scanReader.id = 'shared-barcode-reader-' + Date.now();
            }

            if (preferredCameraId) {
                scanDeviceId = preferredCameraId;
            } else if (scanCameraSelect?.value) {
                scanDeviceId = scanCameraSelect.value;
            }

            html5Scanner = new window.Html5Qrcode(scanReader.id, { verbose: false });
            scanHandled = false;

            const scannerConfig = {
                fps: 12,
                qrbox: { width: 300, height: 130 },
                showTorchButtonIfSupported: true,
                rememberLastUsedCamera: true,
            };

            if (window.Html5QrcodeSupportedFormats) {
                const f = window.Html5QrcodeSupportedFormats;
                scannerConfig.formatsToSupport = [
                    f.EAN_13,
                    f.EAN_8,
                    f.UPC_A,
                    f.UPC_E,
                    f.CODE_128,
                    f.CODE_39,
                ].filter(Boolean);
            }

            const onSuccess = (decodedText) => {
                if (!decodedText || scanHandled) return;

                scanHandled = true;
                const value = String(decodedText).trim();
                setScanStatus('Código detectado: ' + value);

                setTimeout(() => {
                    handleDetectedBarcode(value);
                }, 30);
            };

            const onError = () => {};

            const cameraConfig = scanDeviceId
                ? { deviceId: { exact: scanDeviceId } }
                : { facingMode: 'environment' };

            await html5Scanner.start(cameraConfig, scannerConfig, onSuccess, onError);

            scannerRunning = true;
            scannerStarting = false;
            setScanStatus('Aponte para o código de barras...');
            scanHintTimer1 = setTimeout(() => setScanHint('Aproxime a câmera até o código preencher a moldura.'), 5000);
            scanHintTimer2 = setTimeout(() => setScanHint('Se estiver borrado, afaste um pouco e estabilize a mão.'), 11000);
        } catch (error) {
            scannerStarting = false;
            scannerRunning = false;

            const message = error?.message ? String(error.message) : 'Erro desconhecido.';
            setScanStatus('Não foi possível abrir a câmera.');
            setScanHint('Use o código manual abaixo.');
            notify('Não foi possível abrir a câmera. Erro: ' + message + '. Digite o código manualmente abaixo.', 'error');
        }
    };

    markets.forEach((marketEl) => {
        const marketHead = marketEl.querySelector('.market-head');
        const pendingList = marketEl.querySelector('[data-pending-list]');
        const doneList = marketEl.querySelector('[data-done-list]');

        marketEl.querySelectorAll('[data-item]').forEach((itemEl) => {
            const itemId = itemEl.getAttribute('data-item');
            const checkbox = itemEl.querySelector('[data-toggle-done]');

            if (checkbox && saved[itemId]) {
                checkbox.checked = true;
                itemEl.classList.add('done');
                doneList.appendChild(itemEl);
            }

            checkbox?.addEventListener('change', () => {
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
        initSortableForMarket(marketEl);
    });

    updateProgress();

    openAddButtons.forEach((button) => {
        button.addEventListener('click', () => {
            currentMarketId = button.getAttribute('data-market-id') || '';
            currentMarketName = button.getAttribute('data-market-name') || '';
            if (searchModalMarket) searchModalMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
            if (productModalMarket) productModalMarket.textContent = 'Mercado: ' + (currentMarketName || 'Sem supermercado');
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
        searchDebounceTimer = setTimeout(() => searchProducts(searchInput.value), 240);
    });

    searchCancelBtn?.addEventListener('click', closeSearchModal);
    searchCreateBtn?.addEventListener('click', () => {
        closeSearchModal();
        if (modalProductName) modalProductName.value = searchInput?.value?.trim() || '';
        if (modalBarcode) modalBarcode.value = '';
        if (modalQuantity) modalQuantity.value = searchQuantityInput?.value || '1';
        openProductModal();
    });

    modalCancelBtn?.addEventListener('click', closeProductModal);
    modalSubmitBtn?.addEventListener('click', () => {
        if (!modalProductName?.value.trim()) {
            notify('Informe o nome do produto.', 'error');
            return;
        }

        setButtonBusy(modalSubmitBtn, true, 'Cadastrando...');
        submitSharedItem({
            marketId: currentMarketId,
            productId: '',
            productName: modalProductName.value.trim(),
            barcode: modalBarcode?.value.trim() || '',
            quantity: modalQuantity?.value || '1',
        });
    });

    modalScanBtn?.addEventListener('click', () => {
        closeProductModal();
        startScanner();
    });

    scanManualSubmitBtn?.addEventListener('click', () => {
        const manualCode = scanManualBarcode?.value?.trim() || '';
        if (!manualCode) {
            notify('Informe o código de barras.', 'error');
            return;
        }
        handleDetectedBarcode(manualCode);
    });

    scanManualBarcode?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            scanManualSubmitBtn?.click();
        }
    });

    scanCameraSwitchBtn?.addEventListener('click', () => {
        const selectedId = scanCameraSelect?.value || '';
        if (!selectedId) return;
        scanDeviceId = selectedId;
        startScanner(true, selectedId);
    });

    confirmCancelBtn?.addEventListener('click', closeConfirmModal);
    confirmSubmitBtn?.addEventListener('click', () => {
        if (!confirmProductName?.value.trim()) {
            notify('Informe o nome do produto.', 'error');
            return;
        }

        setButtonBusy(confirmSubmitBtn, true, confirmExistingProductId ? 'Adicionando...' : 'Cadastrando...');
        submitSharedItem({
            marketId: currentMarketId,
            productId: confirmExistingProductId || '',
            productName: confirmProductName.value.trim(),
            barcode: confirmBarcode?.value.trim() || '',
            quantity: confirmQuantity?.value || '1',
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
        if (!removeTargetForm) return;
        setButtonBusy(removeConfirmSubmitBtn, true, 'Removendo...');
        removeTargetForm.submit();
    });

    scanCloseBtn?.addEventListener('click', () => { stopScanner(); });
    scanModal?.addEventListener('click', (event) => {
        if (event.target === scanModal) {
            stopScanner();
        }
    });
    productModal?.addEventListener('click', (event) => {
        if (event.target === productModal) closeProductModal();
    });
    searchModal?.addEventListener('click', (event) => {
        if (event.target === searchModal) closeSearchModal();
    });
    confirmModal?.addEventListener('click', (event) => {
        if (event.target === confirmModal) closeConfirmModal();
    });
    removeConfirmModal?.addEventListener('click', (event) => {
        if (event.target === removeConfirmModal) closeRemoveConfirmModal();
    });

    if (window.location.hash && window.location.hash.startsWith('#help-section-')) {
        // noop - avoid unused hash lints in shared file contexts.
    }
});
