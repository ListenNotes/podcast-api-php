<?php
// Local-only echo fixture: no upstream connections.
file_put_contents(getenv('SDK_FIXTURE_LOG'), json_encode($_SERVER['REQUEST_URI']) . "\n", FILE_APPEND);
if (isset($_GET['delay'])) { usleep(150000); }
$status = (int) ($_GET['status'] ?? 200);
http_response_code($status);
header('Content-Type: application/json');
header('X-ListenAPI-Usage: 17');
header('X-ListenAPI-FreeQuota: 25000');
header('X-ListenAPI-Latency-Seconds: 0.056');
header('X-ListenAPI-NextBillingDate: 2026-09-26T17:27:33.110641+00:00');
if ($status >= 300 && $status < 400) { header('Location: /redirected'); }
if ($status >= 400) {
    echo ($_GET['format'] ?? '') === 'html' ? '<html>unavailable</html>' : '{"error":"Specific API error"}';
} else {
    $body = file_get_contents('php://input');
    parse_str($body, $form);
    echo json_encode(['method' => $_SERVER['REQUEST_METHOD'], 'uri' => $_SERVER['REQUEST_URI'],
        'query' => $_GET, 'body' => $body, 'form' => $form, 'headers' => array_change_key_case(getallheaders())]);
}
