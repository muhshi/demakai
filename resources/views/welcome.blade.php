<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demakai - Eksplorasi KBLI & KBJI Intelligent</title>

    <!-- Meta Tags for SEO -->
    <meta name="description"
        content="Platform cerdas untuk eksplorasi kode KBLI 2025 dan KBJI 2014 dengan teknologi pencarian pintar berbasis AI.">
    <meta name="keywords" content="KBLI 2025, KBJI 2014, Klasifikasi Bisnis, Klasifikasi Jabatan, Intelligent Search">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Styles -->
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --secondary: #a855f7;
            --secondary-glow: rgba(168, 85, 247, 0.5);
            --bg-dark: #0f172a;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Background Animations --- */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background:
                radial-gradient(circle at 20% 20%, var(--primary-glow) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, var(--secondary-glow) 0%, transparent 40%);
            filter: blur(80px);
            opacity: 0.6;
            animation: move-bg 20s infinite alternate ease-in-out;
        }

        @keyframes move-bg {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(5%, 5%) scale(1.1);
            }
        }

        /* --- Navigation --- */
        nav {
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            background: var(--glass);
            border: 1px solid var(--glass-border);
        }

        .nav-links a:hover {
            background: var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
            border-color: transparent;
        }

        /* --- Hero Section --- */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
        }

        h1 {
            font-size: Clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
            background: linear-gradient(to bottom, #fff 30%, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1 span {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        /* --- Search Bar --- */
        .search-container {
            width: 100%;
            max-width: 700px;
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            padding: 0.5rem;
            border-radius: 1.5rem;
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .search-container:focus-within {
            transform: scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 0 40px -10px var(--primary-glow);
        }

        .search-container input {
            flex: 1;
            border: none;
            background: transparent;
            color: white;
            padding: 1rem 1.5rem;
            font-size: 1.125rem;
            outline: none;
            font-family: inherit;
        }

        .search-container input::placeholder {
            color: #64748b;
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 0 2rem;
            border-radius: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .search-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* --- Filter Buttons --- */
        .filter-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            justify-content: center;
            width: 100%;
            max-width: 700px;
        }

        .filter-btn {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            padding: 0.6rem 1.25rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: white;
        }

        .filter-btn.active {
            background: rgba(99, 102, 241, 0.15);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 15px -5px var(--primary-glow);
        }

        .filter-btn .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-muted);
        }

        .filter-btn.active .dot {
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        .filter-btn.active.kbli2025 .dot {
            background: #a855f7;
            box-shadow: 0 0 10px #a855f7;
        }

        .filter-btn.active.kbji .dot {
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
        }

        /* --- Option Cards --- */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 5rem;
            width: 100%;
            max-width: 1000px;
        }

        .card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 2rem;
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: left;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-10px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card h3 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            width: fit-content;
        }

        /* --- Search Results --- */
        #search-results-container {
            width: 100%;
            max-width: 1400px;
            /* Wider for 3 columns */
            margin-top: 3rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            text-align: left;
            z-index: 10;
        }

        .results-column {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .column-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .column-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--glass-border), transparent);
        }

        .result-item {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: slideUp 0.4s ease-out forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary);
            transform: scale(1.01);
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .result-type {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.2rem 0.5rem;
            border-radius: 0.4rem;
        }

        .result-score {
            font-size: 0.75rem;
            color: #10b981;
            font-weight: 700;
        }

        .result-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .result-kode {
            color: var(--primary);
            margin-right: 0.75rem;
        }

        .result-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .result-desc.clamped {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            margin-bottom: 1rem;
            display: block;
            transition: opacity 0.2s;
        }

        .read-more-btn:hover {
            text-decoration: underline;
            opacity: 0.8;
        }

        .result-examples {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .example-tag {
            font-size: 0.7rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* --- Footer --- */
        footer {
            padding: 3rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
            border-top: 1px solid var(--glass-border);
            background: rgba(0, 0, 0, 0.2);
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: 1fr;
            }

            main {
                padding: 4rem 1rem;
            }

            .search-container {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.5rem;
            }

            .search-btn {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-gradient"></div>

    <nav>
        <div class="logo">DEMAKAI.</div>
        <div class="nav-links">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/admin') }}">Dashboard Admin</a>
                @else
                    <a href="{{ route('login') }}">Masuk</a>
                @endauth
            @endif
        </div>
    </nav>

    <main>
        <div class="badge">Next Generation Atlas</div>
        <h1>Pencarian <span>KBLI & KBJI</span><br>Tanpa Batas.</h1>
        <p class="subtitle">Eksplorasi ribuan klasifikasi bisnis dan jabatan dengan teknologi AI yang memudahkan
            pengambilan keputusan Anda.</p>

        <div class="filter-group">
            <button class="filter-btn active kbli2020" onclick="toggleFilter('kbli2020', this)">
                <span class="dot"></span> KBLI 2020
            </button>
            <button class="filter-btn active kbli2025" onclick="toggleFilter('kbli2025', this)">
                <span class="dot"></span> KBLI 2025
            </button>
            <button class="filter-btn active kbji" onclick="toggleFilter('kbji', this)">
                <span class="dot"></span> KBJI 2014
            </button>
        </div>

        <div class="search-container">
            <input type="text" id="search-input" placeholder="Cari kode, judul, atau deskripsi jabatan/bisnis...">
            <button class="search-btn" id="search-button">Telusuri Cerdas</button>
        </div>

        <div id="search-results-container" style="display: none;">
            <!-- Columns will be injected here dynamically -->
        </div>
        <div id="search-loading" style="display: none;">
            <div class="loading-spinner"></div>
        </div>
        <div id="search-empty" style="display: none; margin-top: 3rem; color: var(--text-muted);">
            Tidak ditemukan hasil yang relevan.
        </div>

        <div class="options-grid">
            <div class="card">
                <div class="card-icon">🏢</div>
                <h3>KBLI 2025</h3>
                <p>Update terbaru Klasifikasi Baku Lapangan Usaha Indonesia untuk kesesuaian izin usaha terkini.</p>
                <div class="badge" style="background: rgba(168, 85, 247, 0.1); color: var(--secondary);">Terbaru</div>
            </div>
            <div class="card">
                <div class="card-icon">💼</div>
                <h3>KBJI 2014</h3>
                <p>Klasifikasi Baku Jabatan Indonesia untuk standarisasi profesi dan kompetensi nasional.</p>
            </div>
            <div class="card">
                <div class="card-icon">⚡</div>
                <h3>AI Powered</h3>
                <p>Gunakan bahasa alami untuk menemukan klasifikasi yang paling relevan dengan kebutuhan Anda.</p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Demakai Intelligent System. Built for BPS Indonesia.</p>
        <p style="margin-top: 0.5rem; font-size: 0.75rem;">Laravel v{{ Illuminate\Foundation\Application::VERSION }}
            (PHP v{{ PHP_VERSION }})</p>
    </footer>

    <script>
        // Placeholder interaction
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        const resultsContainer = document.getElementById('search-results-container');
        const loadingBox = document.getElementById('search-loading');
        const emptyBox = document.getElementById('search-empty');

        let activeFilters = {
            kbli2020: true,
            kbli2025: true,
            kbji: true
        };

        window.toggleFilter = (type, btn) => {
            activeFilters[type] = !activeFilters[type];
            btn.classList.toggle('active');

            // Re-render if there's current query
            if (searchInput.value.length >= 3) {
                performSearch(searchInput.value);
            }
        };

        const placeholders = [
            "Contoh: Perdagangan eceran melalui media...",
            "Contoh: Programmer komputer atau pengembang...",
            "Cari berdasarkan kode 5 digit...",
            "Jelaskan aktivitas bisnis Anda di sini..."
        ];
        let currentIdx = 0;

        setInterval(() => {
            searchInput.placeholder = placeholders[currentIdx];
            currentIdx = (currentIdx + 1) % placeholders.length;
        }, 4000);

        // --- Search Logic ---
        let debounceTimer;

        const performSearch = async (query) => {
            if (!query || query.length < 3) {
                resultsContainer.style.display = 'none';
                emptyBox.style.display = 'none';
                return;
            }

            loadingBox.style.display = 'block';
            emptyBox.style.display = 'none';

            try {
                const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                renderResults(data);
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                loadingBox.style.display = 'none';
            }
        };

        const renderResults = (data) => {
            let html = '';
            let totalResults = 0;

            if (activeFilters.kbli2020) {
                html += `
                    <div class="results-column">
                        <h2 class="column-title"><span>🏢</span> KBLI 2020</h2>
                        <div id="kbli2020-results">
                            ${(data.kbli2020 && data.kbli2020.length > 0)
                        ? data.kbli2020.map((res, index) => renderItem(res, index)).join('')
                        : '<p style="color: var(--text-muted); opacity: 0.5;">Tidak ada hasil.</p>'}
                        </div>
                    </div>
                `;
                totalResults += (data.kbli2020 ? data.kbli2020.length : 0);
            }

            if (activeFilters.kbli2025) {
                html += `
                    <div class="results-column">
                        <h2 class="column-title"><span>🚀</span> KBLI 2025</h2>
                        <div id="kbli2025-results">
                            ${(data.kbli2025 && data.kbli2025.length > 0)
                        ? data.kbli2025.map((res, index) => renderItem(res, index)).join('')
                        : '<p style="color: var(--text-muted); opacity: 0.5;">Tidak ada hasil.</p>'}
                        </div>
                    </div>
                `;
                totalResults += (data.kbli2025 ? data.kbli2025.length : 0);
            }

            if (activeFilters.kbji) {
                html += `
                    <div class="results-column">
                        <h2 class="column-title"><span>💼</span> KBJI 2014</h2>
                        <div id="kbji-results">
                            ${(data.kbji && data.kbji.length > 0)
                        ? data.kbji.map((res, index) => renderItem(res, index)).join('')
                        : '<p style="color: var(--text-muted); opacity: 0.5;">Tidak ada hasil.</p>'}
                        </div>
                    </div>
                `;
                totalResults += (data.kbji ? data.kbji.length : 0);
            }

            if (totalResults === 0 && (activeFilters.kbli2020 || activeFilters.kbli2025 || activeFilters.kbji)) {
                resultsContainer.style.display = 'none';
                emptyBox.style.display = 'block';
                return;
            }

            resultsContainer.innerHTML = html;
            resultsContainer.style.display = 'grid';

            // Adjust grid columns based on active count
            const activeCount = Object.values(activeFilters).filter(v => v).length;
            resultsContainer.style.gridTemplateColumns = activeCount > 0 ? `repeat(${activeCount}, 1fr)` : 'none';
        };

        const renderItem = (res, index) => {
            const hasLongDesc = res.deskripsi && res.deskripsi.length > 150;
            return `
                <div class="result-item" style="animation-delay: ${index * 0.05}s; margin-bottom: 1rem;">
                    <div class="result-header">
                        <span class="result-type">${res.type}</span>
                        <span class="result-score">${res.score}% Match</span>
                    </div>
                    <div class="result-title">
                        <span class="result-kode">${res.kode}</span>
                        ${res.judul}
                    </div>
                    <div class="result-desc ${hasLongDesc ? 'clamped' : ''}" id="desc-${res.type}-${res.kode}">
                        ${res.deskripsi || 'Tidak ada deskripsi tersedia.'}
                    </div>
                    ${hasLongDesc ? `
                        <button class="read-more-btn" onclick="toggleReadMore('${res.type}-${res.kode}', this)">Read More</button>
                    ` : ''}
                    ${res.contoh && res.contoh.length > 0 ? `
                        <div class="result-examples">
                            ${res.contoh.map(ex => `<span class="example-tag">${ex}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
        };

        window.toggleReadMore = (id, btn) => {
            const desc = document.getElementById(`desc-${id}`);
            if (desc.classList.contains('clamped')) {
                desc.classList.remove('clamped');
                btn.textContent = 'Show Less';
            } else {
                desc.classList.add('clamped');
                btn.textContent = 'Read More';
            }
        };

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performSearch(e.target.value);
            }, 600);
        });

        searchButton.addEventListener('click', () => {
            performSearch(searchInput.value);
        });
    </script>
</body>

</html>