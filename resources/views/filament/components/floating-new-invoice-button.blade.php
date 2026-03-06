@if (filament()->auth()->check())
    <div x-data="{ open: false }" x-on:keydown.escape.window="open = false; window.dispatchEvent(new Event('modal-closed'))">
        <button
            type="button"
            title="Importar nota"
            aria-label="Importar nota"
            x-on:click="open = true"
            style="
                position: fixed;
                right: 20px;
                bottom: 20px;
                width: 56px;
                height: 56px;
                border-radius: 9999px;
                background: #f59e0b;
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.22);
                z-index: 1000;
                border: 2px solid rgba(255, 255, 255, 0.95);
                cursor: pointer;
            "
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6M9 14h6"/>
            </svg>
        </button>

        <template x-if="open">
            <div
                role="dialog"
                aria-modal="true"
                x-on:click.self="open = false; window.dispatchEvent(new Event('modal-closed'))"
                style="
                    position: fixed;
                    inset: 0;
                    z-index: 1100;
                    background: rgba(17, 24, 39, 0.62);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 16px;
                "
            >
                <div
                    style="
                        width: min(760px, 100%);
                        max-height: 92vh;
                        overflow: auto;
                        background: #ffffff;
                        border-radius: 14px;
                        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
                        padding: 16px;
                    "
                >
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">Importar nota fiscal</h3>
                        <button
                            type="button"
                            aria-label="Fechar"
                            title="Fechar"
                            x-on:click="open = false; window.dispatchEvent(new Event('modal-closed'))"
                            style="
                                width: 34px;
                                height: 34px;
                                border-radius: 9999px;
                                border: 1px solid #d1d5db;
                                background: #fff;
                                color: #111827;
                                cursor: pointer;
                                font-size: 18px;
                                line-height: 1;
                            "
                        >
                            ×
                        </button>
                    </div>

                    <div style="display: grid; gap: 10px;">
                        <input type="text" id="invoice-qr-url" style="display: none;" />
                        @include('filament.resources.invoices.actions.qr-code-scanner')
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
