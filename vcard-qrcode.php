<?php
declare(strict_types=1);

/**
 * Privacy-safe log: timestamp, client IP, payload size only (no contact contents).
 */
function logVcardQrGeneration(int $payloadBytes): void
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . "logs";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . "vcard-qrcode.log";
    $ip = $_SERVER["REMOTE_ADDR"] ?? "-";
    $line = date("c") . "\t" . $ip . "\tbytes=" . $payloadBytes . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function vcardEscape(string $value): string
{
    $value = str_replace(["\r\n", "\r", "\n"], [" ", " ", " "], $value);
    return str_replace(["\\", ";", ",", "\n"], ["\\\\", "\\;", "\\,", " "], $value);
}

/**
 * Fold lines to ~75 octets (RFC 2425 / vCard 3).
 */
function vcardFold(string $content): string
{
    $lines = explode("\r\n", $content);
    $out = [];
    foreach ($lines as $line) {
        $byteLen = strlen($line);
        if ($byteLen <= 75) {
            $out[] = $line;
            continue;
        }
        $out[] = substr($line, 0, 75);
        $rest = substr($line, 75);
        while ($rest !== "") {
            $chunk = substr($rest, 0, 74);
            $rest = substr($rest, 74);
            $out[] = " " . $chunk;
        }
    }
    return implode("\r\n", $out);
}

/**
 * @param array<string, string> $f Trimmed form fields
 */
function buildVcard30(array $f): string
{
    $family = vcardEscape($f["family_name"] ?? "");
    $given = vcardEscape($f["given_name"] ?? "");
    $additional = vcardEscape($f["additional_names"] ?? "");
    $prefix = vcardEscape($f["name_prefix"] ?? "");
    $suffix = vcardEscape($f["name_suffix"] ?? "");

    $fn = trim($f["fn"] ?? "");
    if ($fn === "") {
        $parts = array_filter([$prefix, $given, $additional, $family, $suffix], static fn ($p) => $p !== "");
        $fn = implode(" ", $parts);
    }
    $fn = vcardEscape($fn);

    $lines = [
        "BEGIN:VCARD",
        "VERSION:3.0",
        "N:" . $family . ";" . $given . ";" . $additional . ";" . $prefix . ";" . $suffix,
        "FN:" . $fn,
    ];

    if (($f["nickname"] ?? "") !== "") {
        $lines[] = "NICKNAME:" . vcardEscape($f["nickname"]);
    }
    if (($f["org"] ?? "") !== "") {
        $lines[] = "ORG:" . vcardEscape($f["org"]);
    }
    if (($f["title"] ?? "") !== "") {
        $lines[] = "TITLE:" . vcardEscape($f["title"]);
    }

    $email = $f["email"] ?? "";
    if ($email !== "" && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $etype = strtoupper($f["email_type"] ?? "INTERNET");
        if (!in_array($etype, ["HOME", "WORK", "OTHER", "INTERNET"], true)) {
            $etype = "INTERNET";
        }
        if ($etype === "INTERNET") {
            $lines[] = "EMAIL;TYPE=INTERNET:" . vcardEscape($email);
        } else {
            $lines[] = "EMAIL;TYPE=" . $etype . ",INTERNET:" . vcardEscape($email);
        }
    }

    $phones = [
        "CELL" => $f["tel_cell"] ?? "",
        "WORK" => $f["tel_work"] ?? "",
        "HOME" => $f["tel_home"] ?? "",
        "FAX" => $f["tel_fax"] ?? "",
    ];
    foreach ($phones as $ptype => $num) {
        $num = trim($num);
        if ($num !== "") {
            $lines[] = "TEL;TYPE=" . $ptype . ":" . vcardEscape($num);
        }
    }

    $url = trim($f["url"] ?? "");
    if ($url !== "" && filter_var($url, FILTER_VALIDATE_URL)) {
        $lines[] = "URL:" . vcardEscape($url);
    }

    $street = vcardEscape($f["adr_street"] ?? "");
    $extended = vcardEscape($f["adr_extended"] ?? "");
    $locality = vcardEscape($f["adr_locality"] ?? "");
    $region = vcardEscape($f["adr_region"] ?? "");
    $code = vcardEscape($f["adr_code"] ?? "");
    $country = vcardEscape($f["adr_country"] ?? "");
    if ($street !== "" || $locality !== "" || $region !== "" || $code !== "" || $country !== "" || $extended !== "") {
        $lines[] = "ADR;TYPE=WORK:;" . $extended . ";" . $street . ";" . $locality . ";" . $region . ";" . $code . ";" . $country;
    }

    if (($f["note"] ?? "") !== "") {
        $lines[] = "NOTE:" . vcardEscape($f["note"]);
    }

    $bday = trim($f["bday"] ?? "");
    if ($bday !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $bday)) {
        $lines[] = "BDAY:" . str_replace("-", "", $bday);
    }

    $lines[] = "END:VCARD";
    $raw = implode("\r\n", $lines);
    return vcardFold($raw) . "\r\n";
}

/**
 * @return array<string, string>
 */
function collectVcardFieldsFromRequest(): array
{
    $keys = [
        "fn", "name_prefix", "name_suffix", "given_name", "family_name", "additional_names",
        "nickname", "org", "title", "email", "email_type",
        "tel_cell", "tel_work", "tel_home", "tel_fax", "url",
        "adr_street", "adr_extended", "adr_locality", "adr_region", "adr_code", "adr_country",
        "note", "bday",
    ];
    $out = [];
    foreach ($keys as $k) {
        $out[$k] = isset($_POST[$k]) ? trim((string) $_POST[$k]) : "";
    }
    return $out;
}

function hasAnyNameOrOrg(array $f): bool
{
    foreach (["fn", "given_name", "family_name", "org"] as $k) {
        if (($f[$k] ?? "") !== "") {
            return true;
        }
    }
    return false;
}

function hasEmailOrPhone(array $f): bool
{
    if (($f["email"] ?? "") !== "" && filter_var($f["email"], FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    foreach (["tel_cell", "tel_work", "tel_home", "tel_fax"] as $k) {
        if (trim($f[$k] ?? "") !== "") {
            return true;
        }
    }
    return false;
}

/** Maximum vCard payload length (bytes) for QR services and Version-40 QR (~M). */
const VCARD_QR_MAX_BYTES = 2600;

/**
 * Same QR image API as qrcode-generator.php (no broken CDN paths).
 */
function qrServerImageUrl(string $data, int $size = 260): string
{
    return "https://api.qrserver.com/v1/create-qr-code/?size=" . $size . "x" . $size
        . "&margin=2&ecc=M&data=" . rawurlencode($data);
}

// ----- Download PNG (POST: reposts vCard as base64) -----
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["download_qr"] ?? "") === "1") {
    $b64 = isset($_POST["vcard_b64"]) ? trim((string) $_POST["vcard_b64"]) : "";
    $payload = $b64 !== "" ? base64_decode($b64, true) : false;
    if ($payload === false || !is_string($payload) || strlen($payload) > VCARD_QR_MAX_BYTES) {
        http_response_code(400);
        echo "Invalid or oversized vCard for download.";
        exit;
    }
    if (strncmp($payload, "BEGIN:VCARD", 11) !== 0 || stripos($payload, "END:VCARD") === false) {
        http_response_code(400);
        echo "Invalid vCard payload.";
        exit;
    }

    $qrUrl = qrServerImageUrl($payload, 800);
    $imageData = @file_get_contents($qrUrl);
    if ($imageData === false || strlen($imageData) < 100) {
        http_response_code(502);
        echo "Unable to fetch QR code image.";
        exit;
    }

    $filename = "vcard-contact-qr.png";
    header("Content-Type: image/png");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Length: " . strlen($imageData));
    echo $imageData;
    exit;
}

// ----- Form page -----
$fields = collectVcardFieldsFromRequest();
$error = "";
$vcardText = "";
$vcardQrError = "";
$qrImgUrl = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["download_qr"] ?? "") !== "1") {
    if (!hasAnyNameOrOrg($fields)) {
        $error = "Enter at least a display name, given/family name, or organization.";
    } elseif (!hasEmailOrPhone($fields)) {
        $error = "Add at least one valid email or a phone number so the contact is useful.";
    } elseif (($fields["email"] ?? "") !== "" && !filter_var($fields["email"], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address, or leave email blank.";
    } elseif (($fields["url"] ?? "") !== "" && !filter_var($fields["url"], FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL (https://…), or leave website blank.";
    } else {
        $vcardText = buildVcard30($fields);
        logVcardQrGeneration(strlen($vcardText));
        if (strlen($vcardText) > VCARD_QR_MAX_BYTES) {
            $vcardQrError = "This contact is too long for one QR code (about " . VCARD_QR_MAX_BYTES . " characters max). Shorten the notes or address and try again.";
        } else {
            $qrImgUrl = qrServerImageUrl($vcardText, 260);
        }
    }
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$httpHost = $_SERVER["HTTP_HOST"] ?? "";
$scriptName = $_SERVER["SCRIPT_NAME"] ?? "/vcard-qrcode.php";
$pageUrl = $httpHost !== "" ? $scheme . "://" . $httpHost . $scriptName : "";

$pageTitle = "vCard QR Code – Contact card generator";
$metaDescription = "Build a vCard from your details and get a QR code phones can scan to save your contact. Free, no signup.";
$siteName = "Tools";
$author = "Designekta Studios";
$themeColor = "#0d9488";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc($pageTitle); ?></title>
    <meta name="description" content="<?php echo esc($metaDescription); ?>">
    <meta name="author" content="<?php echo esc($author); ?>">
    <meta name="theme-color" content="<?php echo esc($themeColor); ?>">
    <meta name="color-scheme" content="light">
    <?php if ($pageUrl !== ""): ?>
    <link rel="canonical" href="<?php echo esc($pageUrl); ?>">
    <?php endif; ?>
    <style>
        :root {
            color-scheme: light;
            --bg: #f0fdfa;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #0d9488;
            --primary-hover: #0f766e;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --border: #e2e8f0;
            --shadow: 0 12px 28px rgba(15, 23, 42, 0.1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(155deg, #ccfbf1, var(--bg));
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }
        .card {
            width: min(820px, 100%);
            margin: 0 auto;
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
        .back-link:hover { text-decoration: underline; }
        h1 { margin: 0 0 8px; font-size: 1.65rem; }
        .subtitle { margin: 0 0 18px; color: var(--muted); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px 16px;
        }
        .field-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.88rem;
        }
        .span-2 { grid-column: 1 / -1; }
        input[type="text"],
        input[type="email"],
        input[type="url"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            outline: none;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
        }
        textarea { min-height: 72px; resize: vertical; }
        .hint { font-size: 0.8rem; color: var(--muted); font-weight: 400; margin-left: 4px; }
        .actions-row {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        button[type="submit"] {
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: 12px 18px;
            cursor: pointer;
        }
        button[type="submit"]:hover { background: var(--primary-hover); }
        .message.error {
            margin-top: 14px;
            padding: 11px 12px;
            border-radius: 10px;
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }
        .result {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .result h2 { margin: 0 0 12px; font-size: 1.15rem; }
        .result-inner {
            display: grid;
            grid-template-columns: 1fr minmax(200px, 260px);
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 640px) {
            .result-inner { grid-template-columns: 1fr; }
        }
        .qr-box {
            text-align: center;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
        }
        .qr-box img {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
            border-radius: 8px;
        }
        .qr-status {
            margin: 0 0 10px;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .qr-status.qr-error {
            color: var(--error-text);
        }
        pre.vcard-preview {
            margin: 0;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.78rem;
            line-height: 1.45;
            overflow: auto;
            max-height: 280px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .btn-secondary {
            display: inline-block;
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            background: #f8fafc;
            font-weight: 600;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-secondary:hover { background: #ecfdf5; border-color: #94a3b8; }
        fieldset {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin: 0 0 16px;
            padding: 14px 16px 16px;
        }
        fieldset legend {
            padding: 0 8px;
            font-weight: 700;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <a class="back-link" href="index.php">← All tools</a>
        <h1>vCard QR code</h1>
        <p class="subtitle">Fill in your contact details. We encode them as a standard vCard and generate a QR code many phones can scan to save you as a contact.</p>

        <form method="post" action="" id="vcard-form">
            <fieldset>
                <legend>Name</legend>
                <div class="grid">
                    <div>
                        <label class="field-label" for="fn">Display name (FN)</label>
                        <input type="text" id="fn" name="fn" value="<?php echo esc($fields["fn"]); ?>" placeholder="Auto from parts if empty">
                    </div>
                    <div>
                        <label class="field-label" for="name_prefix">Prefix</label>
                        <input type="text" id="name_prefix" name="name_prefix" value="<?php echo esc($fields["name_prefix"]); ?>" placeholder="Dr., Mr., …">
                    </div>
                    <div>
                        <label class="field-label" for="given_name">Given name</label>
                        <input type="text" id="given_name" name="given_name" value="<?php echo esc($fields["given_name"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="additional_names">Middle / additional</label>
                        <input type="text" id="additional_names" name="additional_names" value="<?php echo esc($fields["additional_names"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="family_name">Family name</label>
                        <input type="text" id="family_name" name="family_name" value="<?php echo esc($fields["family_name"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="name_suffix">Suffix</label>
                        <input type="text" id="name_suffix" name="name_suffix" value="<?php echo esc($fields["name_suffix"]); ?>" placeholder="Jr., III, …">
                    </div>
                    <div>
                        <label class="field-label" for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname" value="<?php echo esc($fields["nickname"]); ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Work</legend>
                <div class="grid">
                    <div>
                        <label class="field-label" for="org">Organization</label>
                        <input type="text" id="org" name="org" value="<?php echo esc($fields["org"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="title">Job title</label>
                        <input type="text" id="title" name="title" value="<?php echo esc($fields["title"]); ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Reach</legend>
                <div class="grid">
                    <div>
                        <label class="field-label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo esc($fields["email"]); ?>" autocomplete="email">
                    </div>
                    <div>
                        <label class="field-label" for="email_type">Email type</label>
                        <select id="email_type" name="email_type">
                            <?php
                            $et = $fields["email_type"] ?: "INTERNET";
                            foreach (["INTERNET" => "Internet", "WORK" => "Work", "HOME" => "Home", "OTHER" => "Other"] as $val => $label) {
                                $sel = strtoupper($et) === $val ? " selected" : "";
                                echo '<option value="' . esc($val) . '"' . $sel . '>' . esc($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="tel_cell">Mobile</label>
                        <input type="tel" id="tel_cell" name="tel_cell" value="<?php echo esc($fields["tel_cell"]); ?>" autocomplete="tel-national">
                    </div>
                    <div>
                        <label class="field-label" for="tel_work">Work phone</label>
                        <input type="tel" id="tel_work" name="tel_work" value="<?php echo esc($fields["tel_work"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="tel_home">Home phone</label>
                        <input type="tel" id="tel_home" name="tel_home" value="<?php echo esc($fields["tel_home"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="tel_fax">Fax</label>
                        <input type="tel" id="tel_fax" name="tel_fax" value="<?php echo esc($fields["tel_fax"]); ?>">
                    </div>
                    <div class="span-2">
                        <label class="field-label" for="url">Website <span class="hint">https://…</span></label>
                        <input type="url" id="url" name="url" value="<?php echo esc($fields["url"]); ?>" placeholder="https://example.com">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Address <span class="hint">(optional)</span></legend>
                <div class="grid">
                    <div class="span-2">
                        <label class="field-label" for="adr_street">Street</label>
                        <input type="text" id="adr_street" name="adr_street" value="<?php echo esc($fields["adr_street"]); ?>">
                    </div>
                    <div class="span-2">
                        <label class="field-label" for="adr_extended">Apt / suite</label>
                        <input type="text" id="adr_extended" name="adr_extended" value="<?php echo esc($fields["adr_extended"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="adr_locality">City</label>
                        <input type="text" id="adr_locality" name="adr_locality" value="<?php echo esc($fields["adr_locality"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="adr_region">Region / state</label>
                        <input type="text" id="adr_region" name="adr_region" value="<?php echo esc($fields["adr_region"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="adr_code">Postal code</label>
                        <input type="text" id="adr_code" name="adr_code" value="<?php echo esc($fields["adr_code"]); ?>">
                    </div>
                    <div>
                        <label class="field-label" for="adr_country">Country</label>
                        <input type="text" id="adr_country" name="adr_country" value="<?php echo esc($fields["adr_country"]); ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>More</legend>
                <div class="grid">
                    <div>
                        <label class="field-label" for="bday">Birthday</label>
                        <input type="date" id="bday" name="bday" value="<?php echo esc($fields["bday"]); ?>">
                    </div>
                    <div class="span-2">
                        <label class="field-label" for="note">Notes</label>
                        <textarea id="note" name="note"><?php echo esc($fields["note"]); ?></textarea>
                    </div>
                </div>
            </fieldset>

            <div class="actions-row">
                <button type="submit">Generate vCard &amp; QR</button>
            </div>
        </form>

        <?php if ($error !== ""): ?>
            <p class="message error"><?php echo esc($error); ?></p>
        <?php endif; ?>

        <?php if ($vcardText !== ""): ?>
            <?php
            $vcardB64 = base64_encode($vcardText);
            ?>
            <section class="result" aria-live="polite">
                <h2>Result</h2>
                <div class="result-inner">
                    <div>
                        <p style="margin:0 0 8px; font-size:0.9rem; color:var(--muted);">Encoded vCard (what the QR contains):</p>
                        <pre class="vcard-preview" id="vcard-block"><?php echo esc($vcardText); ?></pre>
                        <?php if ($qrImgUrl !== ""): ?>
                        <form method="post" style="margin-top:12px;">
                            <input type="hidden" name="download_qr" value="1">
                            <input type="hidden" name="vcard_b64" value="<?php echo esc($vcardB64); ?>">
                            <button type="submit" class="btn-secondary">Download PNG</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="qr-box">
                        <?php if ($vcardQrError !== ""): ?>
                            <p class="qr-status qr-error" role="alert"><?php echo esc($vcardQrError); ?></p>
                        <?php else: ?>
                            <p class="qr-status qr-error" id="vcard-qr-err" hidden role="alert"></p>
                            <img id="vcard-qr-img" src="<?php echo esc($qrImgUrl); ?>" width="260" height="260" alt="QR code containing vCard contact data" decoding="async" fetchpriority="high">
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php if ($qrImgUrl !== ""): ?>
            <script>
            (function () {
                var img = document.getElementById("vcard-qr-img");
                var err = document.getElementById("vcard-qr-err");
                if (!img || !err) return;
                img.addEventListener("error", function () {
                    err.hidden = false;
                    err.textContent = "Could not load the QR image. Allow api.qrserver.com in your blocker or try another network.";
                    img.style.display = "none";
                });
            })();
            </script>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
