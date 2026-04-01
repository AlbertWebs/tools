<?php
declare(strict_types=1);

session_start();

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

$token = $data["token"] ?? "";
$url = isset($data["url"]) ? trim((string) $data["url"]) : "";
$email = isset($data["email"]) ? trim((string) $data["email"]) : "";

if ($token === "" || !hash_equals($_SESSION["coms_csrf"] ?? "", $token)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid session. Reload the page."]);
    exit;
}

if ($url === "" || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["error" => "Enter a valid URL (including http:// or https://)."]);
    exit;
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array(strtolower((string) $scheme), ["http", "https"], true)) {
    http_response_code(400);
    echo json_encode(["error" => "Only http and https URLs are allowed."]);
    exit;
}

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Enter a valid email address."]);
    exit;
}

$clientIp = $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
if (!rateLimitAllow($clientIp)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Wait a minute and try again."]);
    exit;
}

$result = checkUrlAvailability($url);
$up = $result["up"];
$stateKey = hash("sha256", $url . "|" . strtolower($email));
$state = loadComsState();
$prev = $state[$stateKey] ?? null;

$emailSent = false;
$emailError = null;

if (!$up) {
    $wasUp = $prev === null ? true : (bool) ($prev["last_up"] ?? true);
    if ($wasUp) {
        $mailResult = sendDowntimeEmail($email, $url, $result["detail"] ?? "");
        $emailSent = $mailResult["sent"];
        $emailError = $mailResult["error"];
    }
    $state[$stateKey] = ["last_up" => false, "updated" => time()];
} else {
    $state[$stateKey] = ["last_up" => true, "updated" => time()];
}

saveComsState($state);

echo json_encode([
    "ok" => true,
    "up" => $up,
    "http_code" => $result["http_code"],
    "detail" => $result["detail"],
    "checked_at" => date("c"),
    "email_sent" => $emailSent,
    "email_error" => $emailError,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

/**
 * @return array{up: bool, http_code: int|null, detail: string|null}
 */
function checkUrlAvailability(string $url): array
{
    if (function_exists("curl_init")) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => "COMS-Monitor/1.0",
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        if (defined("CURLOPT_MAXFILESIZE")) {
            curl_setopt($ch, CURLOPT_MAXFILESIZE, 65536);
        }
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err = $errno ? curl_error($ch) : "";
        curl_close($ch);

        if ($errno !== 0) {
            return ["up" => false, "http_code" => null, "detail" => $err !== "" ? $err : "Connection failed"];
        }

        if ($code >= 200 && $code < 500) {
            return ["up" => true, "http_code" => $code, "detail" => null];
        }

        return ["up" => false, "http_code" => $code, "detail" => "HTTP " . $code];
    }

    $ctx = stream_context_create([
        "http" => [
            "timeout" => 20,
            "method" => "HEAD",
            "follow_location" => 1,
            "ignore_errors" => true,
            "user_agent" => "COMS-Monitor/1.0",
        ],
        "ssl" => [
            "verify_peer" => true,
            "verify_peer_name" => true,
        ],
    ]);

    $headers = @get_headers($url, 1, $ctx);
    if ($headers === false) {
        return ["up" => false, "http_code" => null, "detail" => "Connection failed"];
    }

    $first = is_array($headers[0] ?? null) ? ($headers[0][0] ?? "") : ($headers[0] ?? "");
    if (preg_match('/\b(\d{3})\b/', (string) $first, $m)) {
        $code = (int) $m[1];
        if ($code >= 200 && $code < 500) {
            return ["up" => true, "http_code" => $code, "detail" => null];
        }
        return ["up" => false, "http_code" => $code, "detail" => "HTTP " . $code];
    }

    return ["up" => false, "http_code" => null, "detail" => "Unexpected response"];
}

/**
 * @return array{sent: bool, error: string|null}
 */
function sendDowntimeEmail(string $to, string $url, string $detail): array
{
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    $subject = "[COMS Monitor] Offline: " . $host;
    $body = "COMS Monitor detected that this URL may be unreachable or returning an error.\r\n\r\n";
    $body .= "URL: " . $url . "\r\n";
    $body .= "Time (server): " . date("c") . "\r\n";
    if ($detail !== "") {
        $body .= "Detail: " . $detail . "\r\n";
    }
    $body .= "\r\nMonitoring runs from your browser session. When the site responds again, alerts reset.\r\n";

    $from = "coms-monitor@" . ($_SERVER["HTTP_HOST"] ?? "localhost");
    $headers = "From: COMS Monitor <" . $from . ">\r\nContent-Type: text/plain; charset=UTF-8\r\n";

    $ok = @mail($to, $subject, $body, $headers);
    if ($ok) {
        return ["sent" => true, "error" => null];
    }
    return ["sent" => false, "error" => "mail() failed. Configure PHP mail or SMTP on your server."];
}

function comsStatePath(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . "data";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . DIRECTORY_SEPARATOR . "coms-monitor-state.json";
}

/** @return array<string, array{last_up: bool, updated: int}> */
function loadComsState(): array
{
    $path = comsStatePath();
    if (!is_readable($path)) {
        return [];
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** @param array<string, array{last_up: bool, updated: int}> $state */
function saveComsState(array $state): void
{
    $path = comsStatePath();
    file_put_contents($path, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function rateLimitAllow(string $ip): bool
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . "data";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . "coms-rate-" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $ip) . ".json";
    $now = time();
    $window = 60;
    $max = 120;

    $data = ["window_start" => $now, "count" => 0];
    if (is_readable($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded["window_start"], $decoded["count"])) {
            $data = $decoded;
        }
    }

    if ($now - (int) $data["window_start"] > $window) {
        $data = ["window_start" => $now, "count" => 0];
    }

    $data["count"] = (int) $data["count"] + 1;
    file_put_contents($file, json_encode($data), LOCK_EX);

    return $data["count"] <= $max;
}
