<div style="margin-top: 1.5rem;">
    {{-- Divider --}}
    <div style="position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
        <div style="position: absolute; inset: 0; display: flex; align-items: center;">
            <div style="width: 100%; border-top: 1px solid #e5e7eb;"></div>
        </div>
        <div style="position: relative; padding: 0 0.75rem; background: white;">
            <span style="font-size: 0.75rem; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">atau</span>
        </div>
    </div>

    {{-- SSO Button --}}
    <a href="{{ route('sipetra.login') }}"
       style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.625rem 1.25rem; 
              font-size: 0.875rem; font-weight: 500; color: #374151; text-decoration: none;
              background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 0.5rem; 
              box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); 
              transition: all 0.2s ease; cursor: pointer;"
       onmouseover="this.style.backgroundColor='#f9fafb'; this.style.borderColor='#60a5fa'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)';"
       onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#d1d5db'; this.style.boxShadow='0 1px 2px 0 rgba(0,0,0,0.05)';">

        {{-- Logo BPS (menggunakan SVG jika logo png belum tersedia, tapi disesuaikan dgn panduan) --}}
        <div style="margin-right: 0.75rem; display: flex; align-items: center;">
            <svg style="width: 20px; height: 20px; color: #1d4ed8;" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
        </div>

        {{-- Label --}}
        <span>Masuk dengan SIPETRA SSO</span>

        {{-- Arrow --}}
        <svg style="width: 16px; height: 16px; margin-left: 0.5rem; color: #9ca3af; flex-shrink: 0;" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Info text --}}
    <p style="margin-top: 0.75rem; text-align: center; font-size: 0.7rem; color: #9ca3af;">
        Login terpusat menggunakan akun BPS Kabupaten Demak
    </p>
</div>
