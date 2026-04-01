<?php
/**
 * Append a line to logs/qrcode-urls.log (timestamp, client IP, URL).
 */
function logQrCodeUrl(string $url): void
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . "logs";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . "qrcode-urls.log";
    $ip = $_SERVER["REMOTE_ADDR"] ?? "-";
    $line = date("c") . "\t" . $ip . "\t" . str_replace(["\r", "\n"], "", $url) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

if (isset($_GET["download"])) {
    $downloadInput = trim($_GET["download"]);

    if (!filter_var($downloadInput, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo "Invalid URL for download.";
        exit;
    }

    $downloadQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($downloadInput);
    $imageData = @file_get_contents($downloadQrUrl);

    if ($imageData === false) {
        http_response_code(502);
        echo "Unable to fetch QR code image.";
        exit;
    }

    $host = parse_url($downloadInput, PHP_URL_HOST) ?: "qrcode";
    $safeHost = preg_replace("/[^a-zA-Z0-9_-]/", "-", $host);
    $filename = "qr-" . $safeHost . ".png";

    header("Content-Type: image/png");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Length: " . strlen($imageData));
    echo $imageData;
    exit;
}

$inputUrl = $_POST["url"] ?? "";
$validatedUrl = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUrl = trim($inputUrl);

    if ($inputUrl === "") {
        $error = "Please enter a URL.";
    } elseif (!filter_var($inputUrl, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL (example: https://example.com).";
    } else {
        $validatedUrl = $inputUrl;
        logQrCodeUrl($validatedUrl);
    }
}

$qrUrl = "";
$downloadLink = "";
if ($validatedUrl !== "") {
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($validatedUrl);
    $downloadLink = "?download=" . urlencode($validatedUrl);
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$httpHost = $_SERVER["HTTP_HOST"] ?? "";
$scriptName = $_SERVER["SCRIPT_NAME"] ?? "/qrcode-generator.php";
$pageUrl = $httpHost !== "" ? $scheme . "://" . $httpHost . $scriptName : "";

$pageTitle = "Free QR Code Generator – Create QR Codes from URLs Online";
$metaDescription = "Create a free QR code for any website URL in seconds. No signup. Generate a scannable QR code online and download it as a PNG—perfect for links, menus, and print.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="robots" content="index, follow">
    <?php if ($pageUrl !== ""): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($pageUrl); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:locale" content="en_US">
    <?php if ($pageUrl !== ""): ?>
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <style>
        :root {
            color-scheme: light;
            --bg: #f8f9fc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --success-bg: #ecfdf3;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
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
            width: min(760px, 100%);
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
            font-size: 1.7rem;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .input-wrap {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input[type="url"] {
            flex: 1 1 420px;
            min-width: 260px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.97rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="url"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: 12px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        button:hover {
            background: var(--primary-hover);
        }

        button:active {
            transform: translateY(1px);
        }

        .message {
            margin-top: 14px;
            padding: 11px 12px;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }

        .result {
            margin-top: 22px;
            border-top: 1px solid var(--border);
            padding-top: 20px;
            text-align: center;
        }

        .result h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.2rem;
        }

        .qr-box {
            display: inline-block;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
        }

        .qr-box img {
            display: block;
            width: 280px;
            max-width: 100%;
            height: auto;
            border-radius: 6px;
        }

        .url-preview {
            margin: 12px auto 0;
            max-width: 100%;
            word-break: break-word;
            color: var(--muted);
        }

        .url-preview a {
            color: var(--primary);
        }

        .actions {
            margin-top: 14px;
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
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-secondary:hover {
            background: #fff3d6;
            border-color: #94a3b8;
        }

        .seo-intro {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.55;
        }

        .seo-faq {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .seo-faq h2 {
            margin: 0 0 14px;
            font-size: 1.1rem;
            color: var(--text);
        }

        .seo-faq dl {
            margin: 0;
        }

        .seo-faq dt {
            margin: 0 0 4px;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text);
        }

        .seo-faq dd {
            margin: 0 0 14px;
            padding-left: 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .seo-faq dd:last-of-type {
            margin-bottom: 0;
        }
    </style>
    <?php
    $structuredData = [
        "@context" => "https://schema.org",
        "@graph" => [
            array_merge(
                [
                    "@type" => "WebApplication",
                    "name" => "Free QR Code Generator",
                    "description" => $metaDescription,
                    "applicationCategory" => "UtilityApplication",
                    "operatingSystem" => "Any",
                    "offers" => [
                        "@type" => "Offer",
                        "price" => "0",
                        "priceCurrency" => "USD",
                    ],
                ],
                $pageUrl !== "" ? ["url" => $pageUrl] : []
            ),
            [
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "Is this QR code generator free to use?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Yes. Generate QR codes from website URLs at no charge. No account or payment is required.",
                        ],
                    ],
                    [
                        "@type" => "Question",
                        "name" => "Can I download my QR code as an image?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Yes. After you generate a QR code, use the Download PNG button to save a PNG file you can print or share.",
                        ],
                    ],
                    [
                        "@type" => "Question",
                        "name" => "What kind of links can I turn into a QR code?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Paste any valid website URL (http:// or https://). The QR code opens that link when scanned with a phone camera or QR reader app.",
                        ],
                    ],
                ],
            ],
        ],
    ];
    ?>
    <script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</head>
<body>
    <main class="card">
        <a class="back-link" href="index.php">← All tools</a>
        <h1>Free QR code generator</h1>
        <p class="subtitle">Create a QR code from any website URL online—no signup required.</p>
        <p class="seo-intro">
            Looking for a <strong>free QR code</strong> for your link? Paste a URL below to make a scannable QR code in seconds.
            Download your QR as a PNG for flyers, business cards, menus, or digital screens.
        </p>

        <form method="post" action="">
            <label class="field-label" for="url">Website URL</label>
            <div class="input-wrap">
                <input
                    type="url"
                    id="url"
                    name="url"
                    placeholder="https://example.com"
                    value="<?php echo htmlspecialchars($inputUrl); ?>"
                    autocomplete="url"
                    required
                >
                <button type="submit">Generate QR</button>
            </div>
        </form>

        <?php if ($error !== ""): ?>
            <p class="message error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($qrUrl !== ""): ?>
            <section class="result" aria-live="polite">
                <h2>Your QR Code</h2>
                <div class="qr-box">
                    <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="Free QR code image linking to <?php echo htmlspecialchars($validatedUrl); ?>">
                </div>
                <p class="url-preview">
                    Opens:
                    <a href="<?php echo htmlspecialchars($validatedUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo htmlspecialchars($validatedUrl); ?>
                    </a>
                </p>
                <div class="actions">
                    <a class="btn-secondary" href="<?php echo htmlspecialchars($downloadLink); ?>">Download PNG</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="seo-faq" aria-labelledby="faq-heading">
            <h2 id="faq-heading">Common questions</h2>
            <dl>
                <dt>Is this QR code generator free?</dt>
                <dd>Yes. You can create QR codes from URLs at no cost. No account or subscription is needed.</dd>
                <dt>Can I download my QR code?</dt>
                <dd>Yes. After generating, use <strong>Download PNG</strong> to save an image file for print or sharing.</dd>
                <dt>What URLs work?</dt>
                <dd>Any valid web address (http:// or https://). The QR code opens that page when someone scans it.</dd>
            </dl>
        </section>
    </main>
</body>
</html>
