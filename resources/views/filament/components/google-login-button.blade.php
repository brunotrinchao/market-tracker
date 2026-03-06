<div style="margin-top: 12px;">
    @if (session('google_auth_error'))
        <p style="margin: 0 0 10px 0; color: #b45309; font-size: 12px; line-height: 1.4;">
            {{ session('google_auth_error') }}
        </p>
    @endif

    <a
        href="{{ route('auth.google.redirect') }}"
        style="
            width: 100%;
            min-height: 44px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            padding: 10px 12px;
        "
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.651 32.657 29.24 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.962 3.038l5.657-5.657C34.046 6.053 29.277 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917"/>
            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 18.961 13 24 13c3.059 0 5.842 1.154 7.962 3.038l5.657-5.657C34.046 6.053 29.277 4 24 4c-7.682 0-14.32 4.337-17.694 10.691"/>
            <path fill="#4CAF50" d="M24 44c5.179 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.189 35.091 26.715 36 24 36c-5.219 0-9.617-3.317-11.283-7.946l-6.522 5.025C9.53 39.556 16.227 44 24 44"/>
            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.794 2.357-2.305 4.378-4.296 5.57c.001-.001 6.19 5.238 6.19 5.238C36.759 39.251 44 34 44 24c0-1.341-.138-2.65-.389-3.917"/>
        </svg>
        Entrar com Google
    </a>
</div>
