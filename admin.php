<?php
/* Password-protected stats page: open https://your-site/admin.php */
require_once __DIR__ . '/lib.php';
start_session();

if (isset($_GET['logout'])) { unset($_SESSION['admin']); header('Location: admin.php'); exit; }
if (isset($_POST['password']) && hash_equals(ADMIN_PASSWORD, (string)$_POST['password'])) {
    $_SESSION['admin'] = true;
}
$authed = !empty($_SESSION['admin']);

// Download raw .txt files
if ($authed && isset($_GET['download'])) {
    $f = basename($_GET['download']);
    if (in_array($f, ['stats.txt', 'feedback.txt', 'events.txt'], true) && is_file(data_path($f))) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $f . '"');
        readfile(data_path($f)); exit;
    }
}

$stats = read_stats();
$feedback = [];
if ($authed && is_file(data_path('feedback.txt'))) {
    $lines = array_reverse(file(data_path('feedback.txt'), FILE_IGNORE_NEW_LINES));
    foreach (array_slice($lines, 0, 100) as $l) $feedback[] = array_map('trim', explode('|', $l));
}
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) . '%' : '—';
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>Campaign stats</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  :root{--brand:#02C2FF;--on-brand:#06283D;--ink:#06283D;--ink-2:#4A5D70;--line:#DDE7EE;--bg:#F3F8FB}
  *{box-sizing:border-box;margin:0}
  body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:var(--bg);color:var(--ink-2);padding:24px 16px}
  .wrap{max-width:960px;margin:auto}
  h1{color:var(--ink);font-size:24px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
  h1 a{font-size:13px;color:var(--ink-2)}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:20px 0}
  .tile{background:#fff;border-radius:16px;padding:18px;box-shadow:0 4px 16px rgba(18,35,63,.06)}
  .tile b{display:block;font-size:34px;color:var(--ink);font-weight:800}
  .tile span{font-size:13px}.tile em{font-style:normal;color:#0077B6;font-weight:600;font-size:12px}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden;font-size:14px}
  th,td{text-align:left;padding:12px;border-bottom:1px solid var(--line);vertical-align:top}
  th{color:var(--ink);background:#FAFBFC}
  .dl a{display:inline-block;margin:0 8px 8px 0;background:var(--brand);color:var(--on-brand);text-decoration:none;
        padding:8px 12px;border-radius:10px;font-size:13px;font-weight:600}
  .scroll{overflow-x:auto}
  form{max-width:340px;margin:12vh auto;background:#fff;padding:24px;border-radius:16px}
  input,button{width:100%;font:inherit;padding:12px;border-radius:12px;margin-top:12px;border:1.5px solid var(--line)}
  button{background:var(--brand);color:var(--on-brand);border:0;font-weight:700;cursor:pointer}
</style></head><body><div class="wrap">
<?php if (!$authed): ?>
  <form method="post"><h1>Campaign stats</h1>
    <input type="password" name="password" placeholder="Admin password" autofocus>
    <button>Sign in</button></form>
<?php else: ?>
  <h1><span><img src="assets/logo.png" alt="" style="width:32px;height:32px;border-radius:8px;vertical-align:middle;margin-right:8px">QR feedback campaign</span> <a href="?logout=1">Sign out</a></h1>
  <div class="grid">
    <div class="tile"><b><?= $stats['qr_scans'] ?></b><span>QR scans</span></div>
    <div class="tile"><b><?= $stats['feedback_submitted'] ?></b><span>Feedback submitted</span>
      <em><?= $pct($stats['feedback_submitted'], $stats['qr_scans']) ?> of scans</em></div>
    <div class="tile"><b><?= $stats['vouchers_issued'] ?></b><span>Vouchers issued</span></div>
  </div>
  <div class="dl">
    <a href="?download=stats.txt">⬇ stats.txt</a><a href="?download=feedback.txt">⬇ feedback.txt</a><a href="?download=events.txt">⬇ events.txt</a>
  </div>
  <div class="scroll"><table>
    <tr><th>Date</th><th>Rating</th><th>Comment</th></tr>
    <?php if (!$feedback): ?><tr><td colspan="3">No feedback yet.</td></tr><?php endif; ?>
    <?php foreach ($feedback as $r): ?>
      <tr><td><?= e($r[0] ?? '') ?></td>
          <td style="white-space:nowrap"><?= e(str_replace('rating=', '', $r[1] ?? '')) ?> ★</td><td><?= e($r[2] ?? '') ?></td></tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</div></body></html>
