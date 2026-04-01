<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION["coms_csrf"])) {
    $_SESSION["coms_csrf"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["coms_csrf"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMS Monitor – Website uptime</title>
    <meta name="description" content="Monitor a website from your browser. Checks every 5 seconds and emails you when the site appears offline.">
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
            --ok: #15803d;
            --bad: #b91c1c;
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
            width: min(560px, 100%);
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
            margin: 0 0 8px;
            font-size: 1.45rem;
        }

        .subtitle {
            margin: 0 0 14px;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.45;
        }

        .note {
            margin: 0 0 18px;
            padding: 10px 12px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #92400e;
            line-height: 1.45;
        }

        .field {
            margin-bottom: 14px;
        }

        .field-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input[type="url"],
        input[type="email"] {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.97rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: 11px 18px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        button:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        button:active:not(:disabled) {
            transform: translateY(1px);
        }

        button.secondary {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
        }

        button.secondary:hover:not(:disabled) {
            background: #fff3d6;
        }

        button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .status {
            margin-top: 18px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #f8fafc;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .status-row:last-child {
            margin-bottom: 0;
        }

        .status-label {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .pill {
            font-weight: 700;
            font-size: 0.95rem;
        }

        .pill.up {
            color: var(--ok);
        }

        .pill.down {
            color: var(--bad);
        }

        .pill.wait {
            color: var(--muted);
        }

        .detail {
            font-size: 0.88rem;
            color: var(--muted);
            word-break: break-word;
        }

        .err {
            margin-top: 10px;
            padding: 10px 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #991b1b;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="back-link" href="index.php">← All tools</a>
        <h1>COMS Monitor</h1>
        <p class="subtitle">Website uptime checker. While this page stays open, we ping your URL every <strong>5 seconds</strong>. If it looks offline, we email you once per outage (not every 5 seconds).</p>
        <p class="note">
            <strong>Email:</strong> PHP must be able to send mail on your server (<code>mail()</code> or SMTP). On local XAMPP, email often needs extra setup—test on your live host if alerts do not arrive.
        </p>

        <form id="form" onsubmit="return false;">
            <div class="field">
                <label class="field-label" for="url">URL to monitor</label>
                <input type="url" id="url" name="url" placeholder="https://example.com" required autocomplete="url">
            </div>
            <div class="field">
                <label class="field-label" for="email">Alert email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
            </div>
            <div class="actions">
                <button type="button" id="btn-start">Start monitoring</button>
                <button type="button" class="secondary" id="btn-stop" disabled>Stop</button>
            </div>
        </form>

        <div class="status" id="status-panel" hidden>
            <div class="status-row">
                <span class="status-label">Status</span>
                <span class="pill wait" id="status-pill">—</span>
            </div>
            <div class="status-row">
                <span class="status-label">Last check</span>
                <span class="detail" id="status-time">—</span>
            </div>
            <div class="status-row">
                <span class="status-label">HTTP</span>
                <span class="detail" id="status-http">—</span>
            </div>
            <p class="detail" id="status-detail"></p>
        </div>
        <div id="api-error" class="err" hidden></div>
    </main>

    <script>
        (function () {
            const token = <?php echo json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const urlInput = document.getElementById("url");
            const emailInput = document.getElementById("email");
            const btnStart = document.getElementById("btn-start");
            const btnStop = document.getElementById("btn-stop");
            const panel = document.getElementById("status-panel");
            const pill = document.getElementById("status-pill");
            const timeEl = document.getElementById("status-time");
            const httpEl = document.getElementById("status-http");
            const detailEl = document.getElementById("status-detail");
            const apiErr = document.getElementById("api-error");

            let timer = null;

            function setError(msg) {
                if (!msg) {
                    apiErr.hidden = true;
                    apiErr.textContent = "";
                    return;
                }
                apiErr.hidden = false;
                apiErr.textContent = msg;
            }

            function setMonitoring(active) {
                btnStart.disabled = active;
                btnStop.disabled = !active;
                urlInput.readOnly = active;
                emailInput.readOnly = active;
            }

            async function runCheck() {
                setError("");
                const url = urlInput.value.trim();
                const email = emailInput.value.trim();
                try {
                    const res = await fetch("coms-monitor-check.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        credentials: "same-origin",
                        body: JSON.stringify({ token: token, url: url, email: email })
                    });
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        setError(data.error || ("Error " + res.status));
                        return;
                    }
                    panel.hidden = false;
                    pill.className = "pill " + (data.up ? "up" : "down");
                    pill.textContent = data.up ? "Online" : "Offline / error";
                    timeEl.textContent = data.checked_at || "—";
                    httpEl.textContent = data.http_code != null ? String(data.http_code) : "—";
                    var d = data.detail || "";
                    detailEl.textContent = d ? "Detail: " + d : "";
                    if (data.email_sent) {
                        detailEl.textContent = (detailEl.textContent ? detailEl.textContent + " · " : "") + "Alert email sent.";
                    }
                    if (data.email_error) {
                        setError(data.email_error);
                    }
                } catch (e) {
                    setError("Network error. Check your connection.");
                }
            }

            btnStart.addEventListener("click", function () {
                if (!urlInput.reportValidity() || !emailInput.reportValidity()) {
                    urlInput.reportValidity();
                    emailInput.reportValidity();
                    return;
                }
                setMonitoring(true);
                runCheck();
                if (timer) clearInterval(timer);
                timer = setInterval(runCheck, 5000);
            });

            btnStop.addEventListener("click", function () {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
                setMonitoring(false);
            });
        })();
    </script>
</body>
</html>
