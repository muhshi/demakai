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

    <script>
        // Cek preferensi sistem & localStorage sebelum render untuk menghindari flash
        const currentTheme = localStorage.getItem("theme");
        const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
        if (currentTheme === "light" || (!currentTheme && !prefersDarkScheme.matches)) {
            document.documentElement.classList.add("light-mode");
        }
    </script>

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
            --glass-hover: rgba(255, 255, 255, 0.05);
            --panel-bg: rgba(255, 255, 255, 0.04);
            --badge-color: #a5b4fc;
            --gradient-start: var(--primary);
            --gradient-end: var(--secondary);
            --input-placeholder: #94a3b8;
            --btn-bg: rgba(255, 255, 255, 0.04);
            --active-bg: rgba(99, 102, 241, 0.15);
            --active-text: #ffffff;
            
            /* Background effect variables */
            --bg-anim-style: 
                radial-gradient(circle at 15% 20%, var(--primary-glow) 0%, transparent 80%),
                radial-gradient(circle at 85% 80%, var(--secondary-glow) 0%, transparent 80%);
            --bg-anim-filter: blur(100px);
            --bg-anim-opacity: 0.8;
        }

        :root.light-mode {
            --bg-dark: #f8f9fc;
            --text-light: #111111;
            --text-muted: #555555;
            
            /* Solid colors instead of transparency for light mode */
            --glass: #ffffff;
            --glass-border: rgba(0, 0, 0, 0.1);
            --glass-hover: #f1f5f9;
            --panel-bg: #ffffff;
            --badge-color: #4f46e5;

            /* Sharper primary/secondary for contrast */
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.18);
            --secondary: #7e22ce;
            --secondary-glow: rgba(126, 34, 206, 0.18);

            /* Sharper gradient for text */
            --gradient-start: #3730a3;
            --gradient-end: #6b21a8;
            --input-placeholder: #666666;
            --btn-bg: #f1f5f9;
            --active-bg: rgba(79, 70, 229, 0.1);
            --active-text: #3730a3;

            /* Elegant dark blue & white linear gradient for light mode */
            --bg-anim-style: linear-gradient(135deg, #ffffff 0%, #e0e7ff 50%, #1e3a8a 100%);
            --bg-anim-filter: none;
            --bg-anim-opacity: 1;
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
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* --- Background Animations --- */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--bg-anim-style);
            filter: var(--bg-anim-filter);
            opacity: var(--bg-anim-opacity);
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
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
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
            background: linear-gradient(to bottom, var(--text-light) 30%, var(--text-muted));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1 span {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
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
            background: var(--glass);
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
            color: var(--text-light);
            padding: 1rem 1.5rem;
            font-size: 1.125rem;
            outline: none;
            font-family: inherit;
        }

        .search-container input::placeholder {
            color: var(--input-placeholder);
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
            background: var(--active-bg);
            border-color: var(--primary);
            color: var(--active-text);
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
            background: var(--glass-hover);
            transform: translateY(-10px);
            border-color: var(--primary);
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
            background: var(--glass-hover);
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
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            border-radius: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: slideUp 0.4s ease-out forwards;
            cursor: pointer;
        }

        .result-item:hover {
            background: var(--glass-hover);
            border-color: var(--primary);
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 15px 30px -10px var(--primary-glow);
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
            background: var(--glass-hover);
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
            background: var(--glass-hover);
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
            color: var(--text-light);
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

        .see-more-btn {
            width: 100%;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-top: 1rem;
            transition: all 0.2s ease;
        }

        .see-more-btn:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--primary);
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

        .more-examples-toggle {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0.1rem 0.4rem;
            border-radius: 0.4rem;
            transition: all 0.2s;
        }

        .more-examples-toggle:hover {
            background: rgba(99, 102, 241, 0.1);
            text-decoration: underline;
        }

        .loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid var(--glass-border);
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
            background: var(--glass-hover);
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: 1fr;
            }

            main {
                padding: 4rem 1rem;
            }

            .search-btn {
                padding: 1rem;
            }
        }

        /* --- Submission Modal --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: var(--bg-dark);
            border: 1px solid var(--glass-border);
            padding: 2.5rem;
            border-radius: 2rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* --- Detail Modal --- */
        .detail-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
            padding: 1rem;
        }

        .detail-modal-content {
            background: var(--bg-dark);
            border: 1px solid var(--glass-border);
            border-radius: 1.5rem;
            width: 100%;
            max-width: 900px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            transform: scale(0.95);
            animation: scaleUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            overflow: hidden;
        }

        @keyframes scaleUp {
            to { transform: scale(1); }
        }

        .detail-modal-header {
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--glass-hover);
        }

        .detail-modal-body {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            grid-template-areas: 
                "kode ."
                "judul ."
                "desc examples";
            gap: 1.5rem 2.5rem;
            padding: 2.5rem;
            overflow-y: auto;
            flex: 1;
            align-items: start;
        }

        @media (max-width: 850px) {
            .detail-modal-body {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "kode"
                    "judul"
                    "desc"
                    "examples";
                gap: 1.5rem;
                padding: 1.5rem;
            }
        }

        .section-kode { grid-area: kode; }
        .section-judul { grid-area: judul; }
        .section-desc { grid-area: desc; }
        .section-examples { grid-area: examples; }

        .detail-section {
            display: flex;
            flex-direction: column;
        }

        .detail-modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--glass-border);
            text-align: right;
            background: var(--glass-hover);
        }

        .detail-section {
            margin-bottom: 1.5rem;
        }
        
        .detail-section:last-child {
            margin-bottom: 0;
        }

        .detail-section h4 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        .detail-kode {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0;
            line-height: 1;
            letter-spacing: -1px;
        }

        .detail-desc-text {
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--text-light);
            white-space: pre-wrap;
            background: var(--active-bg);
            padding: 1.25rem;
            border-radius: 1.25rem;
            border: 1px solid var(--glass-border);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .copy-mini-btn {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            width: 28px;
            height: 28px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-mini-btn:hover {
            color: var(--text-light);
            border-color: var(--primary);
            background: var(--glass-hover);
        }

        .copy-mini-btn.copied {
            color: #10b981;
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .example-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--active-bg);
            border: 1px solid var(--glass-border);
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            font-size: 0.88rem;
            color: var(--text-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1.4;
            width: 100%;
        }

        .example-pill:hover {
            border-color: var(--primary);
            background: var(--glass-hover);
            transform: translateX(4px);
        }

        .item-with-copy {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.5rem;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: var(--text-light);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .modal-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }

        .form-input {
            width: 100%;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-light);
            padding: 1rem;
            border-radius: 1rem;
            outline: none;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: var(--primary);
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .add-example-btn {
            position: absolute;
            bottom: 1.25rem;
            right: 1.25rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--glass);
            border: 1.5px solid var(--primary);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 5;
        }

        .add-example-btn:hover {
            background: var(--primary);
            color: white;
            border-color: transparent;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* --- Theme Toggle Pill --- */
        .theme-toggle-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 9999px;
            padding: 4px;
            width: 140px;
            height: 40px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            margin-left: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .theme-toggle-pill:hover {
            border-color: var(--primary);
        }

        .toggle-thumb {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        :root.light-mode .toggle-thumb {
            transform: translateX(100px);
            background: #ffffff;
        }

        :root:not(.light-mode) .toggle-thumb {
            background: #1e293b;
        }

        .toggle-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .toggle-text {
            flex: 1;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-light);
            z-index: 2;
            user-select: none;
        }

        :root.light-mode .toggle-icon {
            color: #475569;
        }
        :root.light-mode .sun-wrapper {
            color: #0f172a;
        }
        :root:not(.light-mode) .toggle-icon {
            color: #94a3b8;
        }
        :root:not(.light-mode) .moon-wrapper {
            color: #f8fafc;
        }

        .theme-toggle:hover {
            background: var(--glass-hover);
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
            transform: scale(1.05);
        }

        .theme-toggle svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            transition: all 0.3s ease;
        }

        :root.light-mode .theme-toggle .moon-icon {
            display: block;
        }

        :root.light-mode .theme-toggle .sun-icon {
            display: none;
        }

        :root:not(.light-mode) .theme-toggle .moon-icon {
            display: none;
        }

        :root:not(.light-mode) .theme-toggle .sun-icon {
            display: block;
        }

        .nav-actions {
            display: flex;
            align-items: center;
        }

        /* --- Method Selector Buttons --- */
        .method-btn {
            background: var(--btn-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            padding: 0.4rem 0.9rem;
            border-radius: 0.75rem;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .method-btn:hover {
            border-color: #6366f1;
            color: var(--badge-color);
        }
        .method-btn.active {
            background: var(--active-bg);
            border-color: var(--primary);
            color: var(--active-text);
            box-shadow: 0 0 12px -4px var(--primary-glow);
        }
    </style>
</head>

<body>
    <div class="bg-gradient"></div>

    <nav>
        <div class="logo">DEMAKAI.</div>
        <div class="nav-actions">
            <div class="nav-links">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin') }}">Dashboard Admin</a>
                    @else
                        <a href="{{ route('login') }}">Masuk</a>
                    @endauth
                @endif
            </div>
            <button class="theme-toggle-pill" id="theme-toggle" aria-label="Toggle Dark/Light Mode">
                <div class="toggle-thumb"></div>
                <div class="toggle-icon moon-wrapper">
                    <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </div>
                <span class="toggle-text" id="toggle-text">Mode Gelap</span>
                <div class="toggle-icon sun-wrapper">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                </div>
            </button>
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

        {{-- ── Panel Selector Metode (untuk penelitian) ── --}}
        <div id="method-panel" style="
            width:100%; max-width:700px; margin-top:1rem;
            background:var(--panel-bg); border:1px solid var(--glass-border);
            border-radius:1.25rem; padding:1rem 1.25rem;
            backdrop-filter:blur(10px);
        ">
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                <span
                    style="font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#6366f1;">🔬
                    Metode Pencarian</span>
                <span id="active-method-badge"
                    style="font-size:0.7rem; background:rgba(99,102,241,0.15); color:var(--badge-color); padding:0.2rem 0.6rem; border-radius:0.4rem; font-weight:700;">HYBRID
                    + None</span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <button class="method-btn" data-search="sql" data-proc="none" onclick="setMethod(this)">SQL · None</button>
                <button class="method-btn" data-search="sql" data-proc="advanced" onclick="setMethod(this)">SQL · Advanced</button>
                <button class="method-btn" data-search="sql" data-proc="expansion" onclick="setMethod(this)">SQL · Expansion</button>
                <button class="method-btn active" data-search="hybrid" data-proc="none" onclick="setMethod(this)">Hybrid · None</button>
                <button class="method-btn" data-search="hybrid" data-proc="advanced" onclick="setMethod(this)">Hybrid · Advanced</button>
                <button class="method-btn" data-search="hybrid" data-proc="expansion" onclick="setMethod(this)">Hybrid · Expansion</button>
            </div>
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

    </footer>

    <!-- Submission Modal -->
    <div id="submission-modal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeSubmissionModal()">&times;</button>
            <h2 class="modal-title">Ajukan Contoh</h2>
            <p class="modal-subtitle" id="modal-subtitle-text">Bantu kami memperkaya data dengan contoh lapangan nyata.
            </p>

            <form id="submission-form" onsubmit="submitExample(event)">
                <input type="hidden" id="sub-type">
                <input type="hidden" id="sub-kode">

                <div class="form-group">
                    <label class="form-label">Kode & Judul</label>
                    <div id="display-info" style="font-size: 0.9rem; font-weight: 600; color: var(--text-light);"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contoh Lapangan (Pisahkan dengan koma jika lebih dari satu)</label>
                    <textarea id="sub-content" class="form-input" rows="4"
                        placeholder="Contoh: Petani Jagung, Pengusaha Kerupuk..." required></textarea>
                </div>

                <button type="submit" class="submit-btn" id="submit-btn-text">Kirim Pengajuan</button>
            </form>
        </div>
    </div>

    <!-- Universal Detail Modal -->
    <div id="detail-modal" class="detail-modal-overlay" onclick="closeDetailModal(event)">
        <div class="detail-modal-content" onclick="event.stopPropagation()">
            <div class="detail-modal-header">
                <span id="detail-type-badge" class="badge"></span>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="detail-modal-body">
                <div class="detail-section section-kode">
                    <h4>Kode</h4>
                    <div class="item-with-copy">
                        <p id="detail-kode" class="detail-kode"></p>
                        <button id="copy-kode-btn" class="copy-mini-btn" title="Salin Kode" onclick="copyText(document.getElementById('detail-kode').textContent, this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="detail-section section-judul">
                    <h4>Judul</h4>
                    <div class="item-with-copy" style="align-items: flex-start;">
                        <h3 id="detail-judul-full" style="font-size: 1.1rem; color: var(--text-light); margin: 0; line-height: 1.4;"></h3>
                        <button id="copy-judul-btn" class="copy-mini-btn" title="Salin Judul" onclick="copyText(document.getElementById('detail-judul-full').textContent, this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="detail-section section-desc">
                    <h4>Deskripsi Lengkap</h4>
                    <p id="detail-desc" class="detail-desc-text"></p>
                </div>

                <div class="detail-section section-examples" id="detail-examples-section" style="display: none;">
                    <h4>Contoh Lapangan</h4>
                    <div id="detail-examples" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>
                </div>
            </div>
            <div class="detail-modal-footer">
                <button class="search-btn" style="padding: 0.5rem 1.5rem; border-radius: 0.75rem;" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

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

        // ── Metode aktif saat ini ──
        let activeSearch     = 'hybrid';
        let activeProcessing = 'none';

        window.setMethod = (btn) => {
            // Hapus active dari semua tombol
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            activeSearch     = btn.dataset.search;
            activeProcessing = btn.dataset.proc;

            // Update badge
            const label = `${activeSearch.toUpperCase()} + ${activeProcessing.charAt(0).toUpperCase() + activeProcessing.slice(1)}`;
            document.getElementById('active-method-badge').textContent = label;

            // Auto-search ulang kalau ada query aktif
            const q = searchInput.value.trim();
            if (q.length >= 3) performSearch(q);
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
                const response = await fetch(`/api/search?q=${encodeURIComponent(query)}&search_method=${activeSearch}&processing=${activeProcessing}`);
                const data = await response.json();
                
                window.lastSearchResults = data; // Simpan ke global state
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

            const renderColumnWithLimit = (items, columnId, title, emoji) => {
                if (!items || items.length === 0) {
                    return `
                        <div class="results-column">
                            <h2 class="column-title"><span>${emoji}</span> ${title}</h2>
                            <div id="${columnId}-results">
                                <p style="color: var(--text-muted); opacity: 0.5;">Tidak ada hasil.</p>
                            </div>
                        </div>
                    `;
                }

                const visibleItems = items.slice(0, 5);
                const hiddenItems = items.slice(5);

                return `
                    <div class="results-column">
                        <h2 class="column-title"><span>${emoji}</span> ${title}</h2>
                        <div id="${columnId}-results">
                            ${visibleItems.map((res, index) => renderItem(res, index)).join('')}
                            ${hiddenItems.length > 0 ? `
                                <div id="${columnId}-hidden" style="display: none;">
                                    ${hiddenItems.map((res, index) => renderItem(res, index + 5)).join('')}
                                </div>
                                <button class="see-more-btn" onclick="toggleSeeMore('${columnId}', this)">
                                    Lihat ${hiddenItems.length} lainnya ▼
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            };

            if (activeFilters.kbli2020) {
                html += renderColumnWithLimit(data.kbli2020, 'kbli2020', 'KBLI 2020', '🏢');
                totalResults += (data.kbli2020 ? data.kbli2020.length : 0);
            }

            if (activeFilters.kbli2025) {
                html += renderColumnWithLimit(data.kbli2025, 'kbli2025', 'KBLI 2025', '🚀');
                totalResults += (data.kbli2025 ? data.kbli2025.length : 0);
            }

            if (activeFilters.kbji) {
                html += renderColumnWithLimit(data.kbji, 'kbji', 'KBJI 2014', '💼');
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

        window.toggleSeeMore = (columnId, btn) => {
            const hidden = document.getElementById(`${columnId}-hidden`);
            if (hidden.style.display === 'none') {
                hidden.style.display = 'block';
                btn.textContent = 'Sembunyikan ▲';
            } else {
                hidden.style.display = 'none';
                const count = hidden.children.length;
                btn.textContent = `Lihat ${count} lainnya ▼`;
            }
        };

        const renderItem = (res, index) => {
            const hasExamples = res.contoh && res.contoh.length > 0;
            const moreCount = hasExamples ? res.contoh.length - 2 : 0;
            const safeKode = res.kode.replace(/\./g, '-');

            return `
                <div class="result-item" role="button" tabindex="0" onclick="openDetailModal('${res.type}', '${res.kode}')" style="animation-delay: ${index * 0.05}s; margin-bottom: 1rem;">
                    <div class="result-header">
                        <span class="result-type">${res.type}</span>
                        <span class="result-score">${res.score}% Match</span>
                    </div>
                    <div class="result-title">
                        <span class="result-kode">${res.kode}</span>
                        ${res.judul}
                    </div>
                    <div class="result-desc clamped" id="desc-${res.type}-${safeKode}">
                        ${res.deskripsi || 'Tidak ada deskripsi tersedia.'}
                    </div>
                    ${hasExamples ? `
                        <div class="result-examples" style="margin-top: 0.5rem;">
                            ${res.contoh.slice(0, 2).map(ex => `<span class="example-tag">${ex}</span>`).join('')}
                            <span id="more-tags-${res.type}-${safeKode}" style="display: none;">
                                ${res.contoh.slice(2).map(ex => `<span class="example-tag">${ex}</span>`).join('')}
                            </span>
                        </div>
                        ${moreCount > 0 ? `
                            <button class="more-examples-toggle" style="margin-top: 0.5rem;" onclick="event.stopPropagation(); toggleResultExamples('${res.type}', '${safeKode}', this, ${moreCount})">
                                +${moreCount} lainnya
                            </button>
                        ` : ''}
                    ` : ''}
                    <button class="add-example-btn" title="Ajukan Contoh Lapangan" onclick="event.stopPropagation(); openSubmissionModal('${res.type}', '${res.kode}', '${res.judul}')">+</button>
                </div>
            `;
        };

        window.toggleResultExamples = (type, safeKode, btn, count) => {
            const moreTags = document.getElementById(`more-tags-${type}-${safeKode}`);
            if (moreTags.style.display === 'none') {
                moreTags.style.display = 'contents';
                btn.textContent = 'Sembunyikan';
            } else {
                moreTags.style.display = 'none';
                btn.textContent = `+${count} lainnya`;
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

        // --- Theme Toggle Logic ---
        const themeToggle = document.getElementById('theme-toggle');
        const toggleText = document.getElementById('toggle-text');
        
        const updateToggleText = () => {
            if (document.documentElement.classList.contains('light-mode')) {
                toggleText.textContent = "Mode Gelap";
            } else {
                toggleText.textContent = "Mode Terang";
            }
        };
        // Setup initial state
        updateToggleText();

        themeToggle.addEventListener('click', () => {
            const root = document.documentElement;
            root.classList.toggle('light-mode');
            const theme = root.classList.contains('light-mode') ? 'light' : 'dark';
            localStorage.setItem('theme', theme);
            updateToggleText();
        });

        // --- Submission Logic ---
        const modal = document.getElementById('submission-modal');
        const subTypeInput = document.getElementById('sub-type');
        const subKodeInput = document.getElementById('sub-kode');
        const subContentInput = document.getElementById('sub-content');
        const displayInfo = document.getElementById('display-info');

        window.openSubmissionModal = (type, kode, judul) => {
            subTypeInput.value = type;
            subKodeInput.value = kode;
            displayInfo.textContent = `${kode} - ${judul}`;
            modal.style.display = 'flex';
            subContentInput.focus();
        };

        window.closeSubmissionModal = () => {
            modal.style.display = 'none';
            subContentInput.value = '';
        };

        window.submitExample = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-btn-text');
            const originalText = btn.textContent;

            btn.textContent = 'Mengirim...';
            btn.disabled = true;

            try {
                const response = await fetch('/api/submissions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        type: subTypeInput.value,
                        kode: subKodeInput.value,
                        content: subContentInput.value
                    })
                });

                const data = await response.json();
                alert(data.message);
                closeSubmissionModal();
            } catch (error) {
                console.error('Submission error:', error);
                alert('Terjadi kesalahan saat mengirim pengajuan.');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        };

        // Close modals on click outside
        window.onclick = (event) => {
            if (event.target == modal) {
                closeSubmissionModal();
            }
            if (event.target == document.getElementById('detail-modal')) {
                closeDetailModal();
            }
        };

        // --- Detail Modal Logic ---
        const detailModal = document.getElementById('detail-modal');
        
        window.getDetailData = (type, kode) => {
            if (!window.lastSearchResults) return null;
            const keyMap = {
                'KBLI 2025': 'kbli2025',
                'KBLI 2020': 'kbli2020',
                'KBJI 2014': 'kbji'
            };
            const key = keyMap[type];
            if (key && window.lastSearchResults[key]) {
                return window.lastSearchResults[key].find(r => r.kode === kode);
            }
            return null;
        };

        window.openDetailModal = (type, kode) => {
            const data = getDetailData(type, kode);
            if (!data) return;

            document.getElementById('detail-type-badge').textContent = data.type;
            document.getElementById('detail-judul-full').textContent = data.judul;
            document.getElementById('detail-kode').textContent = data.kode;
            document.getElementById('detail-desc').textContent = data.deskripsi || 'Tidak ada deskripsi tersedia.';

            const examplesSection = document.getElementById('detail-examples-section');
            const examplesContainer = document.getElementById('detail-examples');
            
            if (data.contoh && data.contoh.length > 0) {
                examplesContainer.innerHTML = data.contoh.map(ex => `
                    <div class="example-pill">
                        <span>${ex}</span>
                        <button class="copy-mini-btn" title="Salin" onclick="copyText('${ex.replace(/'/g, "\\'")}', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </div>
                `).join('');
                examplesSection.style.display = 'flex';
            } else {
                examplesSection.style.display = 'none';
            }

            detailModal.style.display = 'flex';
        };

        window.closeDetailModal = () => {
            detailModal.style.display = 'none';
        };

        window.copyText = (text, btn) => {
            if (!text || text === 'Tidak ada deskripsi tersedia.') return;

            navigator.clipboard.writeText(text).then(() => {
                const originalIcon = btn.innerHTML;
                
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                btn.classList.add('copied');
                
                const originalTitle = btn.title;
                btn.title = "Tersalin!";

                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                    btn.classList.remove('copied');
                    btn.title = originalTitle;
                }, 1500);
            }).catch(err => {
                console.error('Gagal menyalin teks: ', err);
            });
        };

        // Global Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeDetailModal();
                closeSubmissionModal();
            }
            // Enable Enter key on clickable cards
            if (e.key === 'Enter' && document.activeElement.classList.contains('result-item')) {
                document.activeElement.click();
            }
        });
    </script>
</body>

</html>