<?php
// whoer-client.php
// Simple GET client for retrieving basic IP info from whoer.net
// Usage: php whoer-client.php [ip]
// If no IP given, it will request info for the caller IP.

$ip = $argv[1] ?? '';

function fetch_url($url, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'whoer-php-client/1.0',
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        throw new Exception("HTTP request failed: {$err}");
    }
    return ['code' => $code, 'body' => $body];
}

try {
    if ($ip === '') {
        // Lightweight trace returns basic key=value lines for the caller IP
        $resp = fetch_url('https://whoer.net/cdn-cgi/trace');
        if ($resp['code'] !== 200) {
            throw new Exception('Unexpected HTTP code: ' . $resp['code']);
        }
        // parse lines like "ip=1.2.3.4"
        $pairs = [];
        foreach (preg_split("/\r\n|\n|\r/", $resp['body']) as $line) {
            if (trim($line) === '') continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $pairs[$k] = $v;
        }
        // Output JSON
        echo json_encode(['success' => true, 'source' => 'whoer_trace', 'data' => $pairs], JSON_PRETTY_PRINT);
        exit(0);
    } else {
        // When IP is provided, whoer.net doesn't document a stable JSON API.
        // We try the domain path that may show IP info page and attempt to extract some fields.
        $url = "https://whoer.net/checkwhois?query=" . urlencode($ip);
        $resp = fetch_url($url);
        if ($resp['code'] !== 200) {
            throw new Exception('Unexpected HTTP code: ' . $resp['code']);
        }
        $html = $resp['body'];

        // Basic extraction using regex (best-effort)
        $data = [];
        // Example: try find "IP address" block
        if (preg_match('/IP\s+address(?:[^>]*>){0,3}\s*<\/strong>\s*([\d\.]+)/i', $html, $m)) {
            $data['ip'] = $m[1];
        }
        if (preg_match('/Country(?:[^>]*>){0,3}\s*<\/strong>\s*([^<\n\r]+)/i', $html, $m)) {
            $data['country'] = trim($m[1]);
        }
        if (preg_match('/ISP(?:[^>]*>){0,3}\s*<\/strong>\s*([^<\n\r]+)/i', $html, $m)) {
            $data['isp'] = trim($m[1]);
        }

        // Fallback: try to extract any visible "whois" block lines
        if (empty($data)) {
            // strip tags and take first 400 chars as fallback
            $text = trim(strip_tags($html));
            $data['raw_text_snippet'] = mb_substr(preg_replace("/\s+/", " ", $text), 0, 400);
        }

        echo json_encode(['success' => true, 'source' => 'whoer_checkwhois', 'data' => $data], JSON_PRETTY_PRINT);
        exit(0);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
    exit(1);
}
