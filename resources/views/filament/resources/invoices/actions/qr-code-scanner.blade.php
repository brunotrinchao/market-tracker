<div
    x-data="{
        importEndpoint: '{{ route('invoices.import.from-qr') }}',
        csrfToken: '{{ csrf_token() }}',
        status: 'Abrindo camera...',
        qrValue: '',
        scanner: null,
        scannerRunning: false,
        scannerStarting: false,
        scanHandled: false,
        cameraOptions: [],
        selectedCameraId: '',
        zoomSupported: false,
        zoomMin: 1,
        zoomMax: 1,
        zoomStep: 0.1,
        zoomValue: 1,
        async init() {
            this.$nextTick(() => {
                setTimeout(() => this.startScanner(), 200)
            })
        },
        async ensureLibrary() {
            if (window.Html5Qrcode) {
                return
            }

            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-html5-qrcode=\'1\']')

                if (existing) {
                    existing.addEventListener('load', () => resolve(), { once: true })
                    existing.addEventListener('error', () => reject(new Error('Falha ao carregar html5-qrcode.')), { once: true })
                    return
                }

                const script = document.createElement('script')
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'
                script.async = true
                script.dataset.html5Qrcode = '1'
                script.onload = () => resolve()
                script.onerror = () => reject(new Error('Falha ao carregar html5-qrcode.'))
                document.head.appendChild(script)
            })
        },
        findQrInput() {
            const allInputs = Array.from(document.querySelectorAll('input'))

            return document.getElementById('invoice-qr-url')
                || allInputs.find((el) => (el.id || '').includes('invoice-qr-url'))
                || allInputs.find((el) => (el.name || '') === 'qr_url' || (el.name || '').endsWith('[qr_url]'))
                || allInputs.find((el) => ((el.getAttribute('wire:model') || '').includes('qr_url')))
                || allInputs.find((el) => ((el.getAttribute('wire:model.live') || '').includes('qr_url')))
        },
        setQrInputValue(value) {
            const input = this.findQrInput()

            if (!input) {
                this.status = 'QR lido, mas o campo do formulario nao foi encontrado.'
                return
            }

            input.value = value
            input.setAttribute('value', value)
            input.dispatchEvent(new Event('input', { bubbles: true }))
            input.dispatchEvent(new Event('change', { bubbles: true }))
            input.dispatchEvent(new Event('blur', { bubbles: true }))
        },
        showProcessingOverlay() {
            const existing = document.getElementById('invoice-import-processing-overlay')
            if (existing) {
                return
            }

            const overlay = document.createElement('div')
            overlay.id = 'invoice-import-processing-overlay'
            overlay.style.position = 'fixed'
            overlay.style.inset = '0'
            overlay.style.zIndex = '9999'
            overlay.style.background = 'rgba(17, 24, 39, 0.58)'
            overlay.style.display = 'flex'
            overlay.style.alignItems = 'center'
            overlay.style.justifyContent = 'center'

            const box = document.createElement('div')
            box.style.minWidth = '280px'
            box.style.maxWidth = '90vw'
            box.style.background = '#fff'
            box.style.borderRadius = '12px'
            box.style.padding = '20px 24px'
            box.style.boxShadow = '0 10px 25px rgba(0,0,0,.2)'
            box.style.display = 'grid'
            box.style.gap = '10px'

            const row = document.createElement('div')
            row.style.display = 'flex'
            row.style.alignItems = 'center'
            row.style.gap = '10px'

            const spinner = document.createElement('span')
            spinner.style.width = '18px'
            spinner.style.height = '18px'
            spinner.style.border = '2px solid #d1d5db'
            spinner.style.borderTopColor = '#2563eb'
            spinner.style.borderRadius = '9999px'
            spinner.style.display = 'inline-block'
            spinner.style.animation = 'invoice-import-spin .8s linear infinite'

            const title = document.createElement('strong')
            title.style.fontSize = '15px'
            title.style.color = '#111827'
            title.textContent = 'Processando nota fiscal...'

            const desc = document.createElement('span')
            desc.style.fontSize = '13px'
            desc.style.color = '#4b5563'
            desc.textContent = 'Aguarde enquanto extraimos e salvamos os dados da NFC-e.'

            row.appendChild(spinner)
            row.appendChild(title)
            box.appendChild(row)
            box.appendChild(desc)
            overlay.appendChild(box)

            document.body.appendChild(overlay)

            if (!document.getElementById('invoice-import-processing-style')) {
                const style = document.createElement('style')
                style.id = 'invoice-import-processing-style'
                style.textContent = '@keyframes invoice-import-spin { to { transform: rotate(360deg); } }'
                document.head.appendChild(style)
            }
        },
        hideProcessingOverlay() {
            const overlay = document.getElementById('invoice-import-processing-overlay')
            if (overlay) {
                overlay.remove()
            }
        },
        notifyError(message) {
            if (window.FilamentNotification) {
                new window.FilamentNotification()
                    .title('Erro na importacao')
                    .body(message)
                    .danger()
                    .send()
                return
            }

            console.error(message)
        },
        closeCurrentModal() {
            const modal = this.$root.closest('[role=dialog]') || document.querySelector('[role=dialog]')
            if (!modal) {
                window.dispatchEvent(new Event('modal-closed'))
                return
            }

            const closeButton = modal.querySelector('[aria-label=Close], [aria-label=Fechar], button[title=Close], button[title=Fechar]')
                || Array.from(modal.querySelectorAll('button')).find((btn) => /fechar|close/i.test((btn.textContent || '').trim()))

            if (closeButton) {
                closeButton.click()
                return
            }

            window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
        },
        async importInvoiceFromQr(qrUrl) {
            const response = await fetch(this.importEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ qr_url: qrUrl }),
            })

            const payload = await response.json().catch(() => ({}))
            if (!response.ok) {
                throw new Error(payload.message || 'Falha ao importar nota fiscal.')
            }

            if (!payload.redirect_url) {
                throw new Error('Importacao concluida sem URL de redirecionamento.')
            }

            window.location.assign(payload.redirect_url)
        },
        async startScanner(forceRestart = false) {
            if (this.scannerStarting || (this.scannerRunning && !forceRestart)) {
                return
            }

            this.scannerStarting = true
            await this.stopScanner()

            if (!window.isSecureContext) {
                this.status = 'Camera bloqueada: use HTTPS ou localhost.'
                this.scannerStarting = false
                return
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.status = 'Camera nao suportada neste navegador.'
                this.scannerStarting = false
                return
            }

            try {
                await this.ensureLibrary()
                await this.loadCameraOptions()
            } catch (error) {
                this.status = error && error.message ? error.message : 'Falha ao carregar leitor de QR.'
                this.scannerStarting = false
                return
            }

            const reader = this.$refs.reader
            if (!reader) {
                this.status = 'Leitor nao inicializado.'
                this.scannerStarting = false
                return
            }

            reader.innerHTML = ''

            if (!reader.id) {
                reader.id = 'invoice-qr-reader-' + Date.now()
            }

            this.scanner = new Html5Qrcode(reader.id, { verbose: false })
            this.status = 'Aponte a camera para o QR Code da NFC-e. Para cupom pequeno, use o zoom.'
            this.scanHandled = false

            try {
                await this.startScannerWithFallback()

                this.scannerRunning = true
                this.enableAutoFocus()
                await this.initZoom()
                this.scannerStarting = false
            } catch (error) {
                this.status = 'Nao foi possivel abrir a camera. ' + (error && error.message ? error.message : 'Erro desconhecido')
                this.scannerStarting = false
            }
        },
        async startScannerWithFallback() {
            const onSuccess = (decodedText) => {
                if (!decodedText || this.scanHandled) {
                    return
                }

                this.scanHandled = true
                this.qrValue = decodedText
                this.setQrInputValue(decodedText)
                this.status = 'QR Code lido. Processando nota...'
                this.stopScanner()
                this.closeCurrentModal()
                this.showProcessingOverlay()

                setTimeout(async () => {
                    try {
                        await this.importInvoiceFromQr(decodedText)
                    } catch (error) {
                        this.hideProcessingOverlay()
                        this.status = error && error.message ? error.message : 'Erro ao importar a nota fiscal.'
                        this.notifyError(this.status)
                    }
                }, 120)
            }

            const onError = () => {}

            const configs = this.selectedCameraId
                ? [{ deviceId: { exact: this.selectedCameraId } }]
                : [{ facingMode: 'environment' }]

            const scannerConfig = {
                fps: 10,
                qrbox: 250,
                showZoomSliderIfSupported: false,
                defaultZoomValueIfSupported: 1,
                showTorchButtonIfSupported: false,
                rememberLastUsedCamera: true,
            }

            if (window.Html5QrcodeSupportedFormats && window.Html5QrcodeSupportedFormats.QR_CODE) {
                scannerConfig.formatsToSupport = [window.Html5QrcodeSupportedFormats.QR_CODE]
            }

            let lastError = null

            for (const cameraConfig of configs) {
                try {
                    await this.scanner.start(cameraConfig, scannerConfig, onSuccess, onError)
                    return
                } catch (error) {
                    lastError = error
                }
            }

            throw lastError || new Error('Nao foi possivel iniciar a camera.')
        },
        async loadCameraOptions() {
            if (!window.Html5Qrcode || typeof window.Html5Qrcode.getCameras !== 'function') {
                this.cameraOptions = []
                return
            }

            try {
                const cameras = await window.Html5Qrcode.getCameras()
                this.cameraOptions = cameras || []

                if (!this.selectedCameraId && this.cameraOptions.length > 0) {
                    const preferred = this.cameraOptions.find((cam) => {
                        const label = (cam.label || '').toLowerCase()
                        return label.includes('back') || label.includes('rear') || label.includes('environment') || label.includes('trase')
                    })

                    this.selectedCameraId = (preferred || this.cameraOptions[0]).id
                }
            } catch (_error) {
                this.cameraOptions = []
            }
        },
        async onCameraChange() {
            if (this.scannerStarting) {
                return
            }

            await this.startScanner(true)
        },
        async enableAutoFocus() {
            if (!this.scanner || typeof this.scanner.applyVideoConstraints !== 'function') {
                return
            }

            try {
                await this.scanner.applyVideoConstraints({
                    advanced: [{ focusMode: 'continuous' }],
                })
            } catch (_error) {}
        },
        async initZoom() {
            this.zoomSupported = false
            this.zoomMin = 1
            this.zoomMax = 3
            this.zoomStep = 0.1
            this.zoomValue = 1

            if (!this.scanner || typeof this.scanner.getRunningTrackCameraCapabilities !== 'function') {
                return
            }

            try {
                const caps = this.scanner.getRunningTrackCameraCapabilities()
                const zoom = caps && caps.zoom ? caps.zoom : null
                if (!zoom) {
                    return
                }

                this.zoomSupported = true
                this.zoomMin = Number(zoom.min ?? 1)
                this.zoomMax = Number(zoom.max ?? this.zoomMin)
                this.zoomStep = Number(zoom.step ?? 0.1) || 0.1
                this.zoomValue = Math.min(this.zoomMax, Math.max(this.zoomMin, 1))
                await this.applyZoom(this.zoomValue)
            } catch (_error) {
                this.zoomSupported = false
            }
        },
        async applyZoom(value) {
            if (!this.scanner || typeof this.scanner.applyVideoConstraints !== 'function') {
                return
            }

            if (!this.zoomSupported) {
                return
            }

            const clamped = Math.max(this.zoomMin, Math.min(this.zoomMax, Number(value)))
            this.zoomValue = clamped

            try {
                await this.scanner.applyVideoConstraints({
                    advanced: [{ zoom: clamped }],
                })
                setTimeout(() => {
                    this.enableAutoFocus()
                }, 180)
            } catch (_error) {}
        },
        async zoomIn() {
            await this.applyZoom(this.zoomValue + this.zoomStep)
        },
        async zoomOut() {
            await this.applyZoom(this.zoomValue - this.zoomStep)
        },
        async stopScanner() {
            if (!this.scanner) {
                this.scannerRunning = false
                return
            }

            try {
                if (this.scannerRunning) {
                    await this.scanner.stop()
                }
            } catch (_error) {}

            try {
                await this.scanner.clear()
            } catch (_error) {}

            this.scanner = null
            this.scannerRunning = false
            this.scannerStarting = false
            this.scanHandled = false
            this.zoomSupported = false
            this.zoomValue = 1
        },
    }"
    x-init="init()"
    x-on:modal-closed.window="stopScanner()"
    style="display: grid; gap: 12px;"
>
    <div style="font-size: 14px; color: #374151;" x-text="status"></div>

    <div
        x-ref="reader"
        style="width: 100%; min-height: 260px; border-radius: 12px; overflow: hidden; background: #111827;"
    ></div>

    <label style="display:grid;gap:6px;" x-show="cameraOptions.length > 0">
        <span style="font-size:12px;color:#6b7280;">Camera</span>
        <select
            x-model="selectedCameraId"
            x-on:change="onCameraChange()"
            style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font-size:13px;"
        >
            <template x-for="camera in cameraOptions" :key="camera.id">
                <option :value="camera.id" x-text="camera.label || ('Camera ' + camera.id)"></option>
            </template>
        </select>
    </label>
</div>
