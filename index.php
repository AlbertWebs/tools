<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8f9fc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --border: #e2e8f0;
            --shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(145deg, #fff7e6, var(--bg));
            color: var(--text);
            min-height: 100vh;
            padding: 32px 24px 48px;
        }

        .wrap {
            max-width: 960px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 28px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.85rem;
        }

        .lead {
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.5;
        }

        .tools {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        .tool-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tool-card h2 {
            margin: 0;
            font-size: 1.15rem;
        }

        .tool-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.45;
            flex: 1;
        }

        .tool-card a {
            align-self: flex-start;
            margin-top: 4px;
            display: inline-block;
            text-decoration: none;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: 10px 16px;
            font-size: 0.95rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .tool-card a:hover {
            background: var(--primary-hover);
        }

        .tool-card a:active {
            transform: translateY(1px);
        }

        .badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--primary);
            background: rgba(245, 158, 11, 0.14);
            padding: 4px 8px;
            border-radius: 6px;
            width: fit-content;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <h1>Tools</h1>
            <p class="lead">Small utilities in one place. Pick a tool to get started.</p>
        </header>

        <section class="tools" aria-label="Available tools">
            <article class="tool-card">
                <span class="badge">Generator</span>
                <h2>QR code generator</h2>
                <p>Turn any valid URL into a scannable QR code. Download as PNG when you need a file.</p>
                <a href="qrcode-generator.php">Open QR</a>
            </article>
            <article class="tool-card">
                <span class="badge">Math</span>
                <h2>Calculator</h2>
                <p>Basic arithmetic with add, subtract, multiply, divide, percent, and sign change.</p>
                <a href="calculator.php">Open calculator</a>
            </article>
            <article class="tool-card">
                <span class="badge">Units</span>
                <h2>Measurement converter</h2>
                <p>Miles to km, feet to meters, lb to kg, °F to °C, US gallons to liters, and more.</p>
                <a href="unit-converter.php">Open converter</a>
            </article>
            <article class="tool-card">
                <span class="badge">Monitor</span>
                <h2>COMS Monitor</h2>
                <p>Check a URL every 5 seconds while this page is open. Get an email if the site goes offline.</p>
                <a href="coms-monitor.php">Open COMS</a>
            </article>
        </section>
    </div>
</body>
</html>
