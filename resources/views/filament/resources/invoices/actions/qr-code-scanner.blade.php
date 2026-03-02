<div
    x-data="{
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
        submitImportForm() {
            const form = this.$refs.reader ? this.$refs.reader.closest('form') : null
            if (!form) {
                this.status = 'QR lido, mas nao foi possivel enviar o formulario automaticamente.'
                return
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit()
                return
            }

            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
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

            // Evita UI/stream duplicados quando o modal re-renderiza.
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
                this.status = 'QR Code lido. Importando nota...'
                this.stopScanner()
                setTimeout(() => this.submitImportForm(), 120)
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

            // Alguns dispositivos (principalmente Android) aceitam foco contínuo por constraints.
            // Em outros navegadores isso é ignorado silenciosamente.
            try {
                await this.scanner.applyVideoConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                    ],
                })
            } catch (_error) {
                // Sem suporte de foco manual/contínuo no device/browser atual.
            }
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
                // Começa em 1x para preservar foco inicial.
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
                // Em muitos devices o zoom derruba o foco momentaneamente.
                // Reaplica foco continuo logo apos o zoom.
                setTimeout(() => {
                    this.enableAutoFocus()
                }, 180)
            } catch (_error) {
                // Alguns aparelhos expõem capability mas falham no apply; ignora.
            }
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
