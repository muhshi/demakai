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

        <div class="search-container">
            <input type="text" placeholder="Cari kode, judul, atau deskripsi jabatan/bisnis...">
            <button class="search-btn">Telusuri Cerdas</button>
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
        const searchInput = document.querySelector('.search-container input');
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
        }, 3000);
    </script>
</body>

</html>