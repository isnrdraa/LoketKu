<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f9fafb;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            text-align: center;
            max-width: 400px;
        }
        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #e5e7eb;
            line-height: 1;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        p {
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #111827;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        a:hover { background: #374151; }
        .app-name {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <h1>Halaman Tidak Ditemukan</h1>
        <p>Halaman yang Anda cari tidak ada atau sudah dipindahkan.</p>
        <a href="/dashboard">← Kembali ke Dashboard</a>
        <p class="app-name">{{ config('app.name') }}</p>
    </div>
</body>
</html>
