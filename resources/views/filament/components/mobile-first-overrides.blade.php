<style>
    @media (max-width: 768px) {
        .fi-page,
        .fi-main,
        .fi-main-ctn {
            padding-inline: 0.75rem;
        }

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
            font-size: 0.92rem;
        }

        .fi-ta-record {
            padding-block: 0.35rem;
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

        .fi-modal .fi-modal-header,
        .fi-modal .fi-modal-content,
        .fi-modal .fi-modal-footer {
            padding-inline: 0.9rem;
        }

        .fi-modal .fi-modal-footer {
            padding-bottom: calc(0.9rem + env(safe-area-inset-bottom));
        }
    }
</style>
