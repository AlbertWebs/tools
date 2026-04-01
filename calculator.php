<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculator</title>
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
            --key-bg: #fffaf0;
            --key-op: #ffe7ba;
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
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(340px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        h1 {
            margin: 0 0 16px;
            font-size: 1.5rem;
        }

        .display-wrap {
            background: #0f172a;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 14px;
            text-align: right;
            min-height: 3.2rem;
        }

        .display {
            color: #f8fafc;
            font-size: 1.65rem;
            font-variant-numeric: tabular-nums;
            word-break: break-all;
            line-height: 1.3;
        }

        .keys {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .keys button {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--key-bg);
            color: var(--text);
            font-size: 1.1rem;
            font-weight: 600;
            padding: 14px 8px;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s ease, transform 0.08s ease;
        }

        .keys button:hover {
            background: #fff3d6;
        }

        .keys button:active {
            transform: scale(0.98);
        }

        .keys button.op {
            background: var(--key-op);
            border-color: #ffd28a;
        }

        .keys button.op:hover {
            background: #ffd28a;
        }

        .keys button.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .keys button.primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .keys button.span-2 {
            grid-column: span 2;
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="back-link" href="index.php">← All tools</a>
        <h1>Calculator</h1>

        <div class="display-wrap" aria-label="Result">
            <div class="display" id="display" role="status">0</div>
        </div>

        <div class="keys" role="group" aria-label="Calculator keypad">
            <button type="button" data-action="clear">C</button>
            <button type="button" data-action="sign">±</button>
            <button type="button" data-action="percent">%</button>
            <button type="button" class="op" data-op="/">÷</button>

            <button type="button" data-digit="7">7</button>
            <button type="button" data-digit="8">8</button>
            <button type="button" data-digit="9">9</button>
            <button type="button" class="op" data-op="*">×</button>

            <button type="button" data-digit="4">4</button>
            <button type="button" data-digit="5">5</button>
            <button type="button" data-digit="6">6</button>
            <button type="button" class="op" data-op="-">−</button>

            <button type="button" data-digit="1">1</button>
            <button type="button" data-digit="2">2</button>
            <button type="button" data-digit="3">3</button>
            <button type="button" class="op" data-op="+">+</button>

            <button type="button" class="span-2" data-digit="0">0</button>
            <button type="button" data-digit=".">.</button>
            <button type="button" class="primary" data-action="equals">=</button>
        </div>
    </main>

    <script>
        (function () {
            const displayEl = document.getElementById("display");

            let current = "0";
            let stored = null;
            let pendingOp = null;
            let fresh = false;

            function format(n) {
                if (!Number.isFinite(n)) return "Error";
                const s = String(n);
                if (s.length > 14) return n.toPrecision(10).replace(/\.?0+$/, "");
                return s;
            }

            function render() {
                displayEl.textContent = current;
            }

            function apply(a, b, op) {
                switch (op) {
                    case "+": return a + b;
                    case "-": return a - b;
                    case "*": return a * b;
                    case "/": return b === 0 ? NaN : a / b;
                    default: return b;
                }
            }

            function inputDigit(d) {
                if (fresh) {
                    current = d === "." ? "0." : d;
                    fresh = false;
                } else {
                    if (d === "." && current.includes(".")) return;
                    if (current === "0" && d !== ".") current = d;
                    else current += d;
                }
                render();
            }

            function inputOp(op) {
                const v = parseFloat(current);
                if (stored !== null && pendingOp !== null && !fresh) {
                    stored = apply(stored, v, pendingOp);
                    current = format(stored);
                    fresh = true;
                    render();
                } else {
                    stored = v;
                    fresh = true;
                }
                pendingOp = op;
            }

            function equals() {
                if (pendingOp === null || stored === null) return;
                const b = parseFloat(current);
                const r = apply(stored, b, pendingOp);
                current = format(r);
                stored = null;
                pendingOp = null;
                fresh = true;
                render();
            }

            function clearAll() {
                current = "0";
                stored = null;
                pendingOp = null;
                fresh = false;
                render();
            }

            function toggleSign() {
                if (current === "0" || current === "Error") return;
                if (current.startsWith("-")) current = current.slice(1);
                else current = "-" + current;
                render();
            }

            function percent() {
                const v = parseFloat(current);
                if (!Number.isFinite(v)) return;
                current = format(v / 100);
                fresh = true;
                render();
            }

            document.querySelector(".keys").addEventListener("click", function (e) {
                const btn = e.target.closest("button");
                if (!btn) return;

                if (btn.dataset.digit !== undefined) {
                    inputDigit(btn.dataset.digit);
                    return;
                }

                if (btn.dataset.op !== undefined) {
                    inputOp(btn.dataset.op);
                    return;
                }

                switch (btn.dataset.action) {
                    case "clear":
                        clearAll();
                        break;
                    case "sign":
                        toggleSign();
                        break;
                    case "percent":
                        percent();
                        break;
                    case "equals":
                        equals();
                        break;
                }
            });
        })();
    </script>
</body>
</html>
