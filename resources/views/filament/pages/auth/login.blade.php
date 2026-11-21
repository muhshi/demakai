<x-filament-panels::page.simple>
    <style>
        .sso-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }
        .sso-btn-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
        .sso-hero-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            padding: 0.875rem 1.25rem !important;
            background-color: #005596 !important;
            color: #ffffff !important;
            border-radius: 0.75rem !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 85, 150, 0.25), 0 2px 4px -2px rgba(0, 85, 150, 0.15) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            border: none !important;
        }
        .sso-hero-btn:hover {
            background-color: #00467c !important;
            box-shadow: 0 10px 15px -3px rgba(0, 85, 150, 0.35), 0 4px 6px -4px rgba(0, 85, 150, 0.2) !important;
            transform: translateY(-1px);
        }
        .sso-hero-btn:active {
            transform: scale(0.99);
        }
        .sso-btn-left {
            display: flex !important;
            align-items: center !important;
            gap: 0.875rem !important;
            min-width: 0;
        }
        .sso-logo-badge {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 2.25rem !important;
            height: 2.25rem !important;
            border-radius: 0.5rem !important;
            background-color: #ffffff !important;
            padding: 0.375rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1) !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
        }
        .sso-logo-img {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
            display: block !important;
        }
        .sso-btn-text {
            font-size: 1rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.015em !important;
            color: #ffffff !important;
            white-space: nowrap !important;
        }
        .sso-btn-right {
            display: flex !important;
            align-items: center !important;
            flex-shrink: 0 !important;
            margin-left: 0.75rem !important;
        }
        .sso-arrow-icon {
            width: 1.25rem !important;
            height: 1.25rem !important;
            color: rgba(255, 255, 255, 0.9) !important;
            transition: transform 0.2s ease !important;
        }
        .sso-hero-btn:hover .sso-arrow-icon {
            transform: translateX(3px);
            color: #ffffff !important;
        }
        .sso-spinner {
            display: none;
            width: 1.25rem !important;
            height: 1.25rem !important;
            color: #ffffff !important;
            animation: sso-spin 1s linear infinite !important;
        }
        @keyframes sso-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .sso-caption {
            font-size: 0.75rem !important;
            text-align: center !important;
            color: #64748b !important;
            line-height: 1.4 !important;
            max-width: 24rem !important;
            margin: 0 !important;
        }
        .dark .sso-caption {
            color: #94a3b8 !important;
        }
        .sso-alert {
            display: flex !important;
            align-items: flex-start !important;
            gap: 0.75rem !important;
            padding: 0.875rem 1rem !important;
            border-radius: 0.75rem !important;
            font-size: 0.875rem !important;
            box-sizing: border-box !important;
        }
        .sso-alert-error {
            background-color: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            color: #991b1b !important;
        }
        .dark .sso-alert-error {
            background-color: rgba(69, 10, 10, 0.5) !important;
            border-color: rgba(127, 29, 29, 0.6) !important;
            color: #fca5a5 !important;
        }
        .sso-alert-status {
            background-color: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
            color: #166534 !important;
        }
        .dark .sso-alert-status {
            background-color: rgba(5, 46, 22, 0.5) !important;
            border-color: rgba(20, 83, 45, 0.6) !important;
            color: #86efac !important;
        }
        .sso-fallback-section {
            margin-top: 1.5rem !important;
            padding-top: 1.25rem !important;
            border-top: 1px solid #e2e8f0 !important;
            width: 100% !important;
        }
        .dark .sso-fallback-section {
            border-top-color: #334155 !important;
        }
        .sso-fallback-toggle {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            background: transparent !important;
            border: none !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            color: #64748b !important;
            cursor: pointer !important;
            padding: 0.375rem 0.75rem !important;
            border-radius: 0.375rem !important;
            transition: all 0.15s ease !important;
            text-decoration: none !important;
            white-space: nowrap !important;
        }
        .sso-fallback-toggle:hover {
            color: #334155 !important;
            background-color: rgba(0, 0, 0, 0.04) !important;
        }
        .dark .sso-fallback-toggle {
            color: #94a3b8 !important;
        }
        .dark .sso-fallback-toggle:hover {
            color: #e2e8f0 !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        .sso-toggle-lead-icon {
            width: 0.875rem !important;
            height: 0.875rem !important;
            flex-shrink: 0 !important;
            opacity: 0.85 !important;
        }
        .sso-fallback-chevron {
            width: 0.875rem !important;
            height: 0.875rem !important;
            flex-shrink: 0 !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .sso-fallback-chevron.is-open {
            transform: rotate(180deg) !important;
        }
        .sso-fallback-content {
            margin-top: 1.25rem;
            width: 100%;
        }
        .sso-fallback-notice {
            display: flex !important;
            align-items: flex-start !important;
            gap: 0.625rem !important;
            padding: 0.75rem 0.875rem !important;
            border-radius: 0.5rem !important;
            background-color: #fffbeb !important;
            border: 1px solid #fef3c7 !important;
            color: #92400e !important;
            font-size: 0.75rem !important;
            line-height: 1.4 !important;
            margin-bottom: 1.25rem !important;
            box-sizing: border-box !important;
        }
        .dark .sso-fallback-notice {
            background-color: rgba(69, 26, 3, 0.4) !important;
            border-color: rgba(120, 53, 15, 0.5) !important;
            color: #fde68a !important;
        }
    </style>

    <div class="sso-wrapper">
        {{-- Flash Session Alerts --}}
        @if (session('error'))
            <div class="sso-alert sso-alert-error">
                <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="flex: 1 1 0%;">
                    <strong style="display: block; margin-bottom: 0.125rem;">Gagal Masuk</strong>
                    <span style="font-size: 0.75rem; line-height: 1.25;">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="sso-alert sso-alert-status">
                <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="flex: 1 1 0%;">
                    <span style="font-size: 0.75rem; line-height: 1.25;">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        {{-- Heroic Single SSO Button --}}
        <div class="sso-btn-container">
            <a href="{{ route('sipetra.login') }}"
               id="sipetra-sso-btn"
               onclick="if(this.getAttribute('data-submitting')) return false; this.setAttribute('data-submitting', '1'); this.style.pointerEvents='none'; this.style.opacity='0.85'; document.getElementById('sipetra-sso-text').innerText='Menghubungkan ke SIPETRA...'; document.getElementById('sipetra-sso-spinner').style.display='inline-block'; document.getElementById('sipetra-sso-arrow').style.display='none';"
               class="sso-hero-btn">
               
                <div class="sso-btn-left">
                    <span class="sso-logo-badge">
                        <img src="{{ asset('images/logo_bps.png') }}" class="sso-logo-img" alt="Logo BPS">
                    </span>
                    <span id="sipetra-sso-text" class="sso-btn-text">Masuk dengan SIPETRA SSO</span>
                </div>

                <div class="sso-btn-right">
                    {{-- Spinner (hidden by default) --}}
                    <svg id="sipetra-sso-spinner" class="sso-spinner" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    {{-- Arrow --}}
                    <svg id="sipetra-sso-arrow" class="sso-arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <p class="sso-caption">
                Autentikasi terpusat Badan Pusat Statistik Kabupaten Demak. Gunakan akun SIPETRA Anda untuk masuk secara aman.
            </p>
        </div>

        {{-- Accordion Fallback: Opsi Darurat / Pengembang --}}
        <div x-data="{ show: @entangle('showFallback') }" class="sso-fallback-section">
            <div style="text-align: center;">
                <button 
                    type="button" 
                    @click="show = !show"
                    class="sso-fallback-toggle"
                >
                    <svg class="sso-toggle-lead-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Opsi Darurat / Pengembang</span>
                    <svg :class="{ 'is-open': show }" class="sso-fallback-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <div x-show="show" x-transition x-cloak class="sso-fallback-content">
                <div class="sso-fallback-notice">
                    <svg style="width: 1rem; height: 1rem; flex-shrink: 0; margin-top: 0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Mode darurat hanya digunakan saat server SIPETRA tidak dapat diakses atau untuk pengujian lokal developer.</span>
                </div>

                {{ $this->content }}
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
