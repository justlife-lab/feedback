<?php
/*
 * Receives the feedback form. The voucher code is ONLY sent back from here,
 * after the form passes every check. It is never inside index.php's HTML.
 */
require_once __DIR__ . '/lib.php';
start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

// 1. Must come from a real page visit (token created in index.php)
$token = $_POST['token'] ?? '';
if (empty($_SESSION['token']) || !hash_equals($_SESSION['token'], $token)) {
    fail('Your session expired. Please scan the QR code again.', 403);
}

// 2. Bots fill hidden fields; people don't
if (!empty($_POST['website'])) fail('Invalid submission.');

// 3. This device already has its voucher → show it again, don't count twice
if (already_claimed()) {
    echo json_encode(['ok' => true, 'repeat' => true, 'voucher' => VOUCHER_CODE]);
    exit;
}

// 4. Validate the form
$rating  = (int)($_POST['rating'] ?? 0);
$comment = clean($_POST['comment'] ?? '', 1000);

if ($rating < 1 || $rating > 5)   fail('Please choose a star rating.');
if (mb_strlen($comment) < 10)     fail('Please write a few words about your experience (10+ characters).');

// 5. Save + count
log_line('feedback.txt', ["rating=$rating", $comment]);
log_line('events.txt',   ['feedback_submitted', "rating=$rating", $_SERVER['REMOTE_ADDR'] ?? '-']);
bump_stat('feedback_submitted');
bump_stat('vouchers_issued');
mark_claimed();

echo json_encode(['ok' => true, 'repeat' => false, 'voucher' => VOUCHER_CODE]);


/* ------------------------------------------------------------------
 * One-voucher-per-person rule.
 * Default: remembered on the device (session + 1-year cookie).
 * Stricter alternative: also limit submissions per IP address per day.
 * ------------------------------------------------------------------ */
function already_claimed(): bool {
    return !empty($_SESSION['claimed']) || !empty($_COOKIE['jl_claimed']);
}

function mark_claimed(): void {
    $_SESSION['claimed'] = true;
    setcookie('jl_claimed', '1', [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}
