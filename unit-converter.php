<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit converter – miles, km, lb, kg, °F, °C &amp; more</title>
    <meta name="description" content="Convert miles to kilometers, feet to meters, pounds to kilograms, gallons to liters, Fahrenheit to Celsius, and more.">
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
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(520px, 100%);
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
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 0.95rem;
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

        select,
        input[type="number"] {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.97rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        select:focus,
        input[type="number"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
        }

        input[type="number"] {
            font-variant-numeric: tabular-nums;
        }

        .result-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            background: #f8fafc;
            font-size: 1.15rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            min-height: 2.75rem;
            display: flex;
            align-items: center;
            word-break: break-all;
        }

        .hint {
            margin-top: 14px;
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.45;
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="back-link" href="index.php">← All tools</a>
        <h1>Measurement converter</h1>
        <p class="subtitle">Convert between common US / imperial and metric units. Choose a conversion, enter a value, and see the result instantly.</p>

        <div class="field">
            <label class="field-label" for="conv">Conversion</label>
            <select id="conv" aria-describedby="hint-volume">
                <optgroup label="Length">
                    <option value="mi_km" data-from="Miles" data-to="Kilometers">Miles → kilometers</option>
                    <option value="km_mi" data-from="Kilometers" data-to="Miles">Kilometers → miles</option>
                    <option value="ft_m" data-from="Feet" data-to="Meters">Feet → meters</option>
                    <option value="m_ft" data-from="Meters" data-to="Feet">Meters → feet</option>
                    <option value="in_cm" data-from="Inches" data-to="Centimeters">Inches → centimeters</option>
                    <option value="cm_in" data-from="Centimeters" data-to="Inches">Centimeters → inches</option>
                    <option value="yd_m" data-from="Yards" data-to="Meters">Yards → meters</option>
                    <option value="m_yd" data-from="Meters" data-to="Yards">Meters → yards</option>
                </optgroup>
                <optgroup label="Weight">
                    <option value="lb_kg" data-from="Pounds (lb)" data-to="Kilograms (kg)">Pounds → kilograms</option>
                    <option value="kg_lb" data-from="Kilograms (kg)" data-to="Pounds (lb)">Kilograms → pounds</option>
                    <option value="oz_g" data-from="Ounces (oz)" data-to="Grams (g)">Ounces → grams</option>
                    <option value="g_oz" data-from="Grams (g)" data-to="Ounces (oz)">Grams → ounces</option>
                </optgroup>
                <optgroup label="Volume (US)">
                    <option value="gal_L" data-from="Gallons (US liquid)" data-to="Liters">Gallons → liters</option>
                    <option value="L_gal" data-from="Liters" data-to="Gallons (US liquid)">Liters → gallons</option>
                    <option value="qt_L" data-from="Quarts (US liquid)" data-to="Liters">Quarts → liters</option>
                    <option value="L_qt" data-from="Liters" data-to="Quarts (US liquid)">Liters → quarts</option>
                    <option value="cup_ml" data-from="US cups" data-to="Milliliters">Cups → milliliters</option>
                    <option value="ml_cup" data-from="Milliliters" data-to="US cups">Milliliters → cups</option>
                </optgroup>
                <optgroup label="Temperature">
                    <option value="F_C" data-from="Fahrenheit (°F)" data-to="Celsius (°C)">Fahrenheit → Celsius</option>
                    <option value="C_F" data-from="Celsius (°C)" data-to="Fahrenheit (°F)">Celsius → Fahrenheit</option>
                </optgroup>
            </select>
        </div>

        <div class="field">
            <label class="field-label" for="in-value"><span id="from-label">Miles</span></label>
            <input type="number" id="in-value" inputmode="decimal" step="any" placeholder="0" autocomplete="off">
        </div>

        <div class="field">
            <span class="field-label" id="to-label-wrap"><span id="to-label">Kilometers</span></span>
            <div class="result-box" id="out-value" role="status" aria-live="polite">—</div>
        </div>

        <p class="hint" id="hint-volume">Volume conversions use <strong>US customary</strong> cups, quarts, and gallons (not imperial UK).</p>
    </main>

    <script>
        (function () {
            const conversions = {
                mi_km: function (v) { return v * 1.609344; },
                km_mi: function (v) { return v / 1.609344; },
                ft_m: function (v) { return v * 0.3048; },
                m_ft: function (v) { return v / 0.3048; },
                in_cm: function (v) { return v * 2.54; },
                cm_in: function (v) { return v / 2.54; },
                yd_m: function (v) { return v * 0.9144; },
                m_yd: function (v) { return v / 0.9144; },
                lb_kg: function (v) { return v * 0.45359237; },
                kg_lb: function (v) { return v / 0.45359237; },
                oz_g: function (v) { return v * 28.349523125; },
                g_oz: function (v) { return v / 28.349523125; },
                gal_L: function (v) { return v * 3.785411784; },
                L_gal: function (v) { return v / 3.785411784; },
                qt_L: function (v) { return v * 0.946352946; },
                L_qt: function (v) { return v / 0.946352946; },
                cup_ml: function (v) { return v * 236.5882365; },
                ml_cup: function (v) { return v / 236.5882365; },
                F_C: function (v) { return (v - 32) * 5 / 9; },
                C_F: function (v) { return v * 9 / 5 + 32; }
            };

            const sel = document.getElementById("conv");
            const input = document.getElementById("in-value");
            const out = document.getElementById("out-value");
            const fromLabel = document.getElementById("from-label");
            const toLabel = document.getElementById("to-label");

            function formatResult(n) {
                if (!Number.isFinite(n)) {
                    return "—";
                }
                const s = String(parseFloat(n.toPrecision(12)));
                return s;
            }

            function updateLabels() {
                const opt = sel.selectedOptions[0];
                fromLabel.textContent = opt.getAttribute("data-from") || "";
                toLabel.textContent = opt.getAttribute("data-to") || "";
            }

            function convert() {
                const key = sel.value;
                const fn = conversions[key];
                const raw = input.value.trim();
                if (raw === "" || raw === "-" || raw === "." || raw === "-.") {
                    out.textContent = "—";
                    return;
                }
                const v = Number(raw);
                if (!Number.isFinite(v)) {
                    out.textContent = "—";
                    return;
                }
                out.textContent = formatResult(fn(v));
            }

            sel.addEventListener("change", function () {
                updateLabels();
                convert();
            });

            input.addEventListener("input", convert);

            updateLabels();
        })();
    </script>
</body>
</html>
