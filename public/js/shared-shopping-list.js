document.addEventListener('DOMContentLoaded', () => {
    const config = window.sharedShoppingListConfig || {};
    if (!config.token) {
        return;
    }

    const STORAGE_KEY = 'shared-shopping-list:' + config.token;
    const lookupTemplate = config.barcodeLookupTemplate || '';
    const searchTemplate = config.searchUrlTemplate || '';
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
            let scanStream = null;
            let detectorInterval = null;
            let zxingControls = null;
            let zxingLoadingPromise = null;
            let scanDeviceId = '';
            let lastDetectedCode = '';
            let lastDetectedAt = 0;
            let currentMarketId = '';
            let currentMarketName = '';
    let searchDebounceTimer = null;
    let searchRequestId = 0;
    let removeTargetForm = null;
    let scanHintTimer1 = null;
    let scanHintTimer2 = null;

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
    const setScanStatus = (message) => {
        if (scanStatus) {
            scanStatus.textContent = message;
        }
    };
    const setScanHint = (message) => {
        if (scanHint) {
            scanHint.textContent = message;
        }
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

            const submitSharedItem = ({ marketId, productId, productName, barcode, quantity }) => {
                hiddenMarketId.value = marketId || '';
                hiddenProductId.value = productId || '';
                hiddenProductName.value = productName || '';
                hiddenBarcode.value = barcode || '';
                hiddenQuantity.value = quantity || '1';
                sharedAddForm.submit();
            };

const handleDetectedBarcode = async (barcodeValue) => {
    setScanHint('Código reconhecido. Processando...');
    stopScanner();

                try {
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

            const openCameraStream = async (preferredDeviceId = '') => {
                const constraintsList = [
                    preferredDeviceId !== ''
                        ? { video: { deviceId: { exact: preferredDeviceId } }, audio: false }
                        : null,
                    {
                        video: {
                            facingMode: { exact: 'environment' },
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                        },
                        audio: false,
                    },
                    {
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                        },
                        audio: false,
                    },
                    { video: true, audio: false },
                ];

                let lastError = null;
                for (const constraints of constraintsList) {
                    if (!constraints) {
                        continue;
                    }
                    try {
                        return await navigator.mediaDevices.getUserMedia(constraints);
                    } catch (error) {
                        lastError = error;
                    }
                }

                throw lastError || new Error('Falha ao abrir câmera.');
            };

            const populateCameraOptions = async () => {
                if (!navigator.mediaDevices?.enumerateDevices || !scanCameraSelect) {
                    return;
                }

                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoInputs = devices.filter((device) => device.kind === 'videoinput');

                    scanCameraSelect.innerHTML = '';

                    videoInputs.forEach((device, index) => {
                        const option = document.createElement('option');
                        option.value = device.deviceId;
                        option.textContent = device.label || ('Câmera ' + (index + 1));
                        scanCameraSelect.appendChild(option);
                    });

                    const preferred = videoInputs.find((device) =>
                        /back|rear|environment|traseira|externa/i.test(device.label || '')
                    );

                    if (scanDeviceId && videoInputs.some((device) => device.deviceId === scanDeviceId)) {
                        scanCameraSelect.value = scanDeviceId;
                    } else if (preferred) {
                        scanCameraSelect.value = preferred.deviceId;
                        scanDeviceId = preferred.deviceId;
                    } else if (videoInputs[0]) {
                        scanCameraSelect.value = videoInputs[0].deviceId;
                        scanDeviceId = videoInputs[0].deviceId;
                    }
                } catch (error) {
                    // No-op: enumeração pode falhar antes de liberar permissão.
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
            const requestId = ++searchRequestId;
            searchResults.innerHTML = '<div class="search-loading">Buscando produtos...</div>';
            const url = searchTemplate
                .replace('__Q__', encodeURIComponent(q))
                .replace('__MARKET__', encodeURIComponent(currentMarketId));
            const response = await fetch(url);
            const payload = response.ok ? await response.json() : { data: [] };
            if (requestId !== searchRequestId) {
                return;
            }
            renderSearchResults(payload?.data || []);
        } catch (error) {
            renderSearchResults([]);
        }
    };

    const releaseScannerResources = () => {
                if (detectorInterval) {
                    clearInterval(detectorInterval);
                    detectorInterval = null;
                }
                if (zxingControls) {
                    zxingControls.stop();
                    zxingControls = null;
                }
                if (scanStream) {
                    scanStream.getTracks().forEach((track) => track.stop());
                    scanStream = null;
                }
        if (scanVideo) {
            scanVideo.srcObject = null;
        }
        if (scanHintTimer1) {
            clearTimeout(scanHintTimer1);
            scanHintTimer1 = null;
        }
        if (scanHintTimer2) {
            clearTimeout(scanHintTimer2);
            scanHintTimer2 = null;
        }
    };

            const stopScanner = () => {
                releaseScannerResources();
        if (scanManualBarcode) {
            scanManualBarcode.value = '';
        }
        setScanStatus('Iniciando câmera...');
        setScanHint('Centralize o código dentro da moldura.');
        scanModal.style.display = 'none';
    };

            const startScanner = async (preferredDeviceId = '') => {
                if (!navigator.mediaDevices?.getUserMedia || !scanVideo) {
                    scanModal.style.display = 'flex';
                    if (scanManualBarcode) {
                        scanManualBarcode.value = '';
                        setTimeout(() => scanManualBarcode.focus(), 60);
                    }
                    alert('Este navegador não permite abrir câmera aqui. Digite o código manualmente abaixo.');
                    return;
                }

                try {
            releaseScannerResources();
            scanModal.style.display = 'flex';
            setScanStatus('Abrindo câmera...');
            setScanHint('Centralize o código dentro da moldura.');

                    if (window.BarcodeDetector) {
                        scanStream = await openCameraStream(preferredDeviceId || scanDeviceId);
                        const track = scanStream.getVideoTracks()[0] || null;
                        const settings = track?.getSettings ? track.getSettings() : null;
                        if (settings?.deviceId) {
                            scanDeviceId = settings.deviceId;
                        }

                scanVideo.srcObject = scanStream;
                await populateCameraOptions();
                setScanStatus('Aponte para o código de barras...');
                scanHintTimer1 = setTimeout(() => setScanHint('Aproxime a câmera até o código preencher a moldura.'), 5000);
                scanHintTimer2 = setTimeout(() => setScanHint('Se estiver borrado, afaste um pouco e estabilize a mão.'), 11000);

                        const detector = new BarcodeDetector({
                            formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39'],
                        });

                        detectorInterval = setInterval(async () => {
                            if (!scanVideo || scanVideo.readyState < 2) {
                                return;
                            }

                            const codes = await detector.detect(scanVideo);
                            if (codes.length > 0 && codes[0].rawValue) {
                                const value = String(codes[0].rawValue).trim();
                                const nowMs = Date.now();
                                if (value !== '' && (value !== lastDetectedCode || (nowMs - lastDetectedAt) > 1500)) {
                                    lastDetectedCode = value;
                                    lastDetectedAt = nowMs;
                                    setScanStatus('Código detectado: ' + value);
                                    handleDetectedBarcode(value);
                                }
                            }
                        }, 220);

                        return;
                    }

                    try {
                await ensureZxingLoaded();
                await populateCameraOptions();
                const codeReader = new window.ZXingBrowser.BrowserMultiFormatReader();
                setScanStatus('Aponte para o código de barras...');
                scanHintTimer1 = setTimeout(() => setScanHint('Aproxime a câmera até o código preencher a moldura.'), 5000);
                scanHintTimer2 = setTimeout(() => setScanHint('Se estiver borrado, afaste um pouco e estabilize a mão.'), 11000);
                zxingControls = await codeReader.decodeFromConstraints(
                            preferredDeviceId || scanDeviceId
                                ? { video: { deviceId: { exact: preferredDeviceId || scanDeviceId } }, audio: false }
                                : { video: { facingMode: { ideal: 'environment' } }, audio: false },
                            scanVideo,
                            (result) => {
                                if (result?.getText) {
                                    const value = String(result.getText()).trim();
                                    const nowMs = Date.now();
                                    if (value !== '' && (value !== lastDetectedCode || (nowMs - lastDetectedAt) > 1500)) {
                                        lastDetectedCode = value;
                                        lastDetectedAt = nowMs;
                                        setScanStatus('Código detectado: ' + value);
                                        handleDetectedBarcode(value);
                                    }
                                }
                            }
                        );
                    } catch (libraryError) {
                        setScanStatus('Leitura automática indisponível. Use código manual.');
                        alert('A câmera foi aberta, mas a leitura automática não está disponível agora. Digite o código manualmente.');
                    }
                } catch (error) {
                    releaseScannerResources();
                    scanModal.style.display = 'flex';
                    if (scanManualBarcode) {
                        scanManualBarcode.value = '';
                        setTimeout(() => scanManualBarcode.focus(), 60);
                    }
                    const message = error?.message ? String(error.message) : 'Erro desconhecido.';
                    setScanStatus('Não foi possível abrir a câmera.');
                    alert('Não foi possível abrir a câmera. Erro: ' + message + '. Digite o código manualmente abaixo.');
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
        setButtonBusy(modalSubmitBtn, true, 'Cadastrando...');
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
            scanManualBarcode?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    scanManualSubmitBtn?.click();
                }
            });
            scanCameraSwitchBtn?.addEventListener('click', () => {
                const selectedId = scanCameraSelect?.value || '';
                if (!selectedId) {
                    return;
                }
                scanDeviceId = selectedId;
                startScanner(selectedId);
            });

            confirmCancelBtn?.addEventListener('click', closeConfirmModal);
    confirmSubmitBtn?.addEventListener('click', () => {
        if (!confirmProductName.value.trim()) {
            alert('Informe o nome do produto.');
            return;
        }
        setButtonBusy(confirmSubmitBtn, true, 'Cadastrando...');
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
            setButtonBusy(removeConfirmSubmitBtn, true, 'Removendo...');
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
});
