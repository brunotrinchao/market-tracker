<style>
    @media (max-width: 768px) {
        .fi-topbar {
            position: sticky;
            top: 0;
            z-index: 35;
            backdrop-filter: blur(8px);
        }

        .fi-btn {
            min-height: 44px;
            padding-inline: 0.875rem;
        }

        .fi-icon-btn,
        button.fi-icon-btn,
        .fi-pagination-btn,
        .fi-dropdown-list-item-btn {
            min-width: 44px;
            min-height: 44px;
        }

        input.fi-input,
        textarea.fi-input,
        select.fi-select-input,
        .fi-select-input-btn,
        .fi-input-wrp {
            min-height: 44px;
        }

        .fi-ta-header-cell,
        .fi-ta-cell,
        .fi-ta-col,
        .fi-ta-record {
            font-size: 1rem;
        }

        .fi-ta-table thead,
        .fi-ta-header {
            display: none;
        }

        .fi-ta,
        .fi-ta-content,
        .fi-ta-table {
            border: 0 !important;
            box-shadow: none !important;
        }

        .fi-ta-table tbody tr,
        .fi-ta-record {
            border: 0 !important;
            border-top: 0 !important;
            border-bottom: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        .fi-ta-table tr::before,
        .fi-ta-table tr::after,
        .fi-ta-record::before,
        .fi-ta-record::after {
            border: 0 !important;
            border-top: 0 !important;
            border-bottom: 0 !important;
            box-shadow: none !important;
            content: none !important;
        }

        .fi-ta-table > tbody,
        .fi-ta-table > tbody > tr,
        .fi-ta-table > tbody > tr > td {
            border: 0 !important;
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        .fi-ta-table td::before,
        .fi-ta-table td::after,
        .fi-ta-cell::before,
        .fi-ta-cell::after {
            border: 0 !important;
            border-top: 0 !important;
            border-bottom: 0 !important;
            box-shadow: none !important;
            content: none !important;
        }

        .fi-ta-table tbody td,
        .fi-ta-cell,
        .fi-ta-row,
        .fi-ta-ctn,
        .fi-ta-table > :is(tbody, tfoot) > tr > td {
            border: 0 !important;
            padding: 0 !important;
        }

        .fi-divider,
        .fi-section-content-ctn,
        .divide-y > :not([hidden]) ~ :not([hidden]) {
            --tw-divide-y-reverse: 0 !important;
            border-top-width: 0 !important;
            border-bottom-width: 0 !important;
        }

        .fi-tabs-item-btn,
        .fi-breadcrumbs a {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }

        .fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) > .fi-modal-window-ctn {
            padding: 0;
        }

        .fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) > .fi-modal-window-ctn > .fi-modal-window {
            width: 100vw;
            max-width: 100vw;
            min-height: 100dvh;
            border-radius: 0;
            margin: 0;
        }

        .fi-modal .fi-modal-footer {
            padding-bottom: calc(0.9rem + env(safe-area-inset-bottom));
        }
    }
</style>
