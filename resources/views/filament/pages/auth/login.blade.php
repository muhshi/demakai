<x-filament-panels::page.simple>
    {{-- Flash Error Notification --}}
    @if (session('error'))
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-300">
            <svg class="h-5 w-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-medium text-red-800 dark:text-red-200">Gagal Masuk</p>
                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-300">
            <svg class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-xs text-blue-600 dark:text-blue-400">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    {{-- Primary Action: SIPETRA SSO Hero Button --}}
    <div class="flex flex-col items-center gap-4 py-2">
        <a href="{{ route('sipetra.login') }}"
           id="sipetra-sso-btn"
           onclick="if(this.getAttribute('data-submitting')) return false; this.setAttribute('data-submitting', '1'); this.style.pointerEvents='none'; this.style.opacity='0.85'; document.getElementById('sso-spinner').style.display='inline-block'; document.getElementById('sso-icon-badge').style.display='none'; document.getElementById('sso-btn-text').innerText='Menghubungkan ke SIPETRA...';"
           class="w-full flex items-center justify-center gap-3.5 px-4 py-3 text-base font-semibold text-white rounded-xl shadow-md transition-all duration-200 hover:shadow-lg hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-offset-2"
           style="background-color: #005596;">
            {{-- Logo BPS Badge with Padding & Rounded Corners --}}
            <span id="sso-icon-badge" class="flex items-center justify-center w-8 h-8 rounded-lg bg-white p-1.5 shadow-sm shrink-0">
                <img id="sso-icon"
                     src="{{ asset('images/logo_bps.png') }}"
                     alt="Logo BPS"
                     class="w-full h-full object-contain"
                     loading="lazy">
            </span>

            {{-- Spinner --}}
            <svg id="sso-spinner" style="display: none;" class="w-7 h-7 animate-spin text-white shrink-0 p-0.5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>

            <span id="sso-btn-text" class="tracking-wide">Masuk dengan SIPETRA SSO</span>

            <svg class="w-5 h-5 ml-auto text-blue-200 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>

        <p class="text-xs text-center text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm">
            Autentikasi terpusat Badan Pusat Statistik Kabupaten Demak. Gunakan akun SIPETRA Anda untuk masuk secara aman.
        </p>
    </div>

    {{-- Emergency Fallback Collapsible Section --}}
    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800" x-data="{ show: @entangle('showFallback') }">
        <div class="text-center">
            <button type="button"
                    @click="show = !show"
                    class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors focus:outline-none">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Opsi Darurat / Pengembang</span>
                <svg class="w-3.5 h-3.5 transform transition-transform duration-200" :class="{ 'rotate-180': show }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <div x-show="show" x-collapse x-cloak class="mt-4">
            <div class="p-3 mb-4 text-xs text-amber-800 bg-amber-50 rounded-lg border border-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:border-amber-800 flex items-start gap-2">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Mode darurat hanya digunakan saat server SIPETRA tidak dapat diakses atau untuk pengujian lokal.</span>
            </div>

            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page.simple>
