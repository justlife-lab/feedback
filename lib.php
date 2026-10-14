<?php
require_once __DIR__ . '/config.php';

/* ---------- storage helpers (plain .txt files in /data) ---------- */

function data_path(string $file): string {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir . '/' . $file;
}

/** The counters written to data/stats.txt, with human-readable labels. */
function stat_labels(): array {
    return [
        'qr_scans'             => 'QR scans (unique visits)',
        'feedback_submitted'   => 'Feedback forms submitted',
        'vouchers_issued'      => 'Vouchers issued',
    ];
}

function parse_stats(string $text): array {
    $stats = array_fill_keys(array_keys(stat_labels()), 0);
    foreach (preg_split('/\R/', $text) as $line) {
        if (preg_match('/^(\w+):\s*(\d+)/', $line, $m) && isset($stats[$m[1]])) {
            $stats[$m[1]] = (int)$m[2];
        }
    }
    return $stats;
}

function render_stats(array $s): string {
    $rate = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) . '%' : '—';
    $out  = "# " . BRAND_NAME . " QR feedback campaign — live stats\n";
    $out .= "# Last updated: " . date('Y-m-d H:i:s') . " (" . APP_TIMEZONE . ")\n";
    $out .= "# Scan → feedback rate: " . $rate($s['feedback_submitted'], $s['qr_scans']) . "\n\n";
    foreach (stat_labels() as $key => $label) {
        $out .= str_pad("$key: {$s[$key]}", 32) . "# $label\n";
    }
    return $out;
}

function read_stats(): array {
    $p = data_path('stats.txt');
    return is_file($p) ? parse_stats((string)file_get_contents($p)) : parse_stats('');
}

/** Atomically add 1 to a counter in data/stats.txt (file lock prevents lost updates). */
function bump_stat(string $key): void {
    if (!array_key_exists($key, stat_labels())) return;
    $fh = fopen(data_path('stats.txt'), 'c+');
    if (!$fh) return;
    flock($fh, LOCK_EX);
    $stats = parse_stats(stream_get_contents($fh));
    $stats[$key]++;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, render_stats($stats));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

function clean(string $v, int $max = 500): string {
    $v = str_replace(["\r", "\n", "|", "\t"], ' ', trim($v));
    return mb_substr($v, 0, $max);
}

/** Append one line to a .txt log, e.g. data/events.txt */
function log_line(string $file, array $fields): void {
    $line = date('Y-m-d H:i:s') . ' | ' . implode(' | ', array_map(fn($f) => clean((string)$f), $fields)) . "\n";
    file_put_contents(data_path($file), $line, FILE_APPEND | LOCK_EX);
}

/* ---------- session ---------- */

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
