@if (filament()->auth()->check())
    <a
        href="{{ $url }}"
        title="Importar nota"
        aria-label="Importar nota"
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
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.95);
        "
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6M9 14h6"/>
        </svg>
    </a>
@endif

