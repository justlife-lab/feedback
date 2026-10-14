<?php
require_once __DIR__ . '/lib.php';
start_session();

// Count each visitor once per session as a "scan"
if (empty($_SESSION['scanned'])) {
    $_SESSION['scanned'] = true;
    bump_stat('qr_scans');
    log_line('events.txt', ['qr_scan', 'src=' . ($_GET['src'] ?? 'direct'), $_SERVER['REMOTE_ADDR'] ?? '-']);
}

// One-time token so submit.php only accepts forms from this page
if (empty($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(16));

$claimed = !empty($_SESSION['claimed']) || !empty($_COOKIE['jl_claimed']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#02C2FF">
<title><?= e(BRAND_NAME) ?> · Share your feedback</title>
<link rel="icon" href="assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  /* ===== Justlife brand colours ===== */
  :root{
    --brand:        #02C2FF;   /* Justlife cyan (from the logo) */
    --brand-deep:   #0077B6;   /* deeper blue for gradients behind white text */
    --brand-soft:   #E5F8FF;
    --on-brand:     #06283D;   /* dark text on cyan buttons: 7.4:1 contrast */
    --ink:          #06283D;   /* headings */
    --ink-2:        #4A5D70;   /* body text */
    --line:         #DDE7EE;
    --bg:           #F3F8FB;
    --card:         #FFFFFF;
    --star:         #FFB800;
    --danger:       #D93A4A;
    --radius:       20px;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:var(--bg);color:var(--ink-2);
       min-height:100vh;-webkit-font-smoothing:antialiased}
  .hero{background:linear-gradient(155deg,var(--brand) 0%,#0399E0 50%,var(--brand-deep) 100%);
        padding:28px 20px 96px;color:#fff;text-align:center;position:relative;overflow:hidden}
  .hero::before,.hero::after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.10)}
  .hero::before{width:260px;height:260px;top:-110px;left:-90px}
  .hero::after{width:200px;height:200px;bottom:-80px;right:-60px}
  .logo{width:60px;height:60px;border-radius:16px;box-shadow:0 0 0 3px rgba(255,255,255,.9),0 8px 20px rgba(6,40,61,.25);
        position:relative;z-index:1}
  .hero h1{font-size:27px;line-height:1.25;font-weight:800;margin-top:20px;position:relative;z-index:1;
           text-shadow:0 1px 2px rgba(6,40,61,.25)}
  .hero p{margin-top:8px;font-size:15px;font-weight:500;position:relative;z-index:1;text-shadow:0 1px 2px rgba(6,40,61,.25)}
  .pill{display:inline-flex;gap:6px;align-items:center;background:#fff;color:var(--ink);
        border-radius:99px;padding:7px 15px;font-size:13px;font-weight:700;margin-top:16px;position:relative;z-index:1;
        box-shadow:0 6px 16px rgba(6,40,61,.15)}
  main{max-width:460px;margin:-72px auto 40px;padding:0 16px;position:relative;z-index:2}
  .card{background:var(--card);border-radius:var(--radius);padding:26px 20px;box-shadow:0 12px 40px rgba(6,40,61,.10)}
  label{display:block;font-weight:700;color:var(--ink);font-size:15px;margin:22px 0 10px}
  textarea{width:100%;font:inherit;font-size:16px;color:var(--ink);border:1.5px solid var(--line);
        border-radius:14px;padding:13px 14px;background:#fff;min-height:130px;resize:vertical;
        transition:border-color .15s,box-shadow .15s}
  textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-soft)}
  .stars{display:flex;flex-direction:row-reverse;justify-content:center;gap:6px}
  .stars input{position:absolute;opacity:0;pointer-events:none}
  .stars label{margin:0;cursor:pointer;font-size:42px;line-height:1;color:var(--line);transition:transform .12s,color .12s}
  .stars label:hover,.stars label:hover ~ label,.stars input:checked ~ label{color:var(--star)}
  .stars label:active{transform:scale(.88)}
  .rating-hint{text-align:center;font-size:14px;margin-top:8px;min-height:20px;font-weight:700;color:var(--ink)}
  .btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;border:0;cursor:pointer;
       font:inherit;font-weight:800;font-size:16px;border-radius:14px;padding:16px;margin-top:22px;transition:transform .1s,filter .15s}
  .btn:active{transform:scale(.98)}
  .btn-primary{background:var(--brand);color:var(--on-brand);box-shadow:0 8px 20px rgba(2,194,255,.35)}
  .btn-primary:hover{filter:brightness(1.05)}
  .btn-primary[disabled]{opacity:.6;cursor:wait}
  .error{background:#FDECEE;color:var(--danger);border-radius:12px;padding:12px 14px;font-size:14px;
         font-weight:600;margin-top:16px;display:none}
  .hp{position:absolute;left:-9999px}
  .fine{font-size:12px;text-align:center;margin-top:14px}

  /* Voucher ticket */
  .ticket{border-radius:var(--radius);overflow:hidden;box-shadow:0 12px 40px rgba(6,40,61,.14)}
  .ticket-top{background:linear-gradient(155deg,var(--brand) 0%,#0399E0 55%,var(--brand-deep) 100%);color:#fff;
        padding:28px 22px 24px;text-align:center;text-shadow:0 1px 2px rgba(6,40,61,.25)}
  .ticket-top .tag{font-size:12px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase}
  .ticket-top .value{font-size:48px;font-weight:800;letter-spacing:-1px;margin-top:6px}
  .ticket-top .what{font-size:15px;font-weight:600}
  .perf{height:24px;background:#fff;position:relative;
        background-image:linear-gradient(90deg,var(--line) 50%,transparent 50%);
        background-size:14px 2px;background-repeat:repeat-x;background-position:center}
  .perf::before,.perf::after{content:"";position:absolute;top:0;width:24px;height:24px;border-radius:50%;background:var(--bg)}
  .perf::before{left:-12px}.perf::after{right:-12px}
  .ticket-body{background:#fff;padding:6px 22px 26px;text-align:center}
  .code{display:flex;align-items:center;justify-content:space-between;gap:10px;border:2px dashed var(--brand);
        background:var(--brand-soft);border-radius:14px;padding:12px 12px 12px 18px;margin-top:10px}
  .code strong{font-size:26px;letter-spacing:3px;color:var(--ink);font-weight:800}
  .copy{border:0;background:var(--brand);color:var(--on-brand);font:inherit;font-weight:800;font-size:14px;
        border-radius:10px;padding:10px 16px;cursor:pointer}
  .thanks{font-size:13px;margin-top:16px}
  .hidden{display:none}
  .pop{animation:pop .45s cubic-bezier(.2,1.4,.4,1)}
  @keyframes pop{from{transform:scale(.9);opacity:0}to{transform:none;opacity:1}}
  footer{text-align:center;font-size:12px;padding:0 16px 28px}
</style>
</head>
<body>

<header class="hero">
  <img class="logo" src="assets/logo.png" alt="<?= e(BRAND_NAME) ?>">
  <h1 id="heroTitle"><?= $claimed ? 'Here’s your voucher 🎉' : 'How did we do today?' ?></h1>
  <p id="heroSub"><?= $claimed ? 'Thank you for your feedback!' : 'Tell us in 30 seconds and get a gift from us' ?></p>
  <?php if (!$claimed): ?><div class="pill">🎁 <?= e(VOUCHER_VALUE) ?> IV Therapy voucher</div><?php endif; ?>
</header>

<main>
  <!-- STEP 1: feedback form -->
  <form id="form" class="card <?= $claimed ? 'hidden' : '' ?>" novalidate>
    <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

    <label style="text-align:center;margin-top:0">Rate your experience</label>
    <div class="stars" role="radiogroup" aria-label="Star rating">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" id="s<?= $i ?>" name="rating" value="<?= $i ?>">
        <label for="s<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
      <?php endfor; ?>
    </div>
    <div class="rating-hint" id="ratingHint"></div>

    <label for="comment">Tell us about your experience</label>
    <textarea id="comment" name="comment" maxlength="1000"
      placeholder="What did you like? What could we do better?"></textarea>

    <div class="error" id="error" role="alert"></div>
    <button class="btn btn-primary" id="submitBtn" type="submit">Submit &amp; get my voucher</button>
    <p class="fine">One voucher per client.</p>
  </form>

  <!-- STEP 2: voucher (code is filled in only after submit.php approves) -->
  <section id="reward" class="<?= $claimed ? '' : 'hidden' ?>">
    <div class="ticket pop">
      <div class="ticket-top">
        <div class="tag">Thank-you gift</div>
        <div class="value"><?= e(VOUCHER_VALUE) ?></div>
        <div class="what"><?= e(VOUCHER_TITLE) ?></div>
      </div>
      <div class="perf"></div>
      <div class="ticket-body">
        <p style="font-size:14px">Use this code when you book</p>
        <div class="code">
          <strong id="code"><?= $claimed ? e(VOUCHER_CODE) : '' ?></strong>
          <button class="copy" id="copyBtn" type="button">Copy</button>
        </div>
        <p class="thanks">Take a screenshot to keep your code handy.</p>
      </div>
    </div>
  </section>
</main>

<footer>© <?= date('Y') ?> <?= e(BRAND_NAME) ?> · Voucher subject to availability and booking terms</footer>

<script>
const hints = {1:'We’re sorry 😔',2:'Not great',3:'It was okay',4:'Good 🙂',5:'Excellent! 🤩'};
document.querySelectorAll('.stars input').forEach(r =>
  r.addEventListener('change', () => document.getElementById('ratingHint').textContent = hints[r.value]));

const form = document.getElementById('form');
const err  = document.getElementById('error');
const btn  = document.getElementById('submitBtn');

form.addEventListener('submit', async (ev) => {
  ev.preventDefault();
  err.style.display = 'none';
  btn.disabled = true; btn.textContent = 'Sending…';
  try {
    const res  = await fetch('submit.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Something went wrong.');
    document.getElementById('code').textContent = data.voucher;
    form.classList.add('hidden');
    document.getElementById('reward').classList.remove('hidden');
    document.getElementById('heroTitle').textContent = 'Here’s your voucher 🎉';
    document.getElementById('heroSub').textContent   = 'Thank you for your feedback!';
    document.querySelector('.pill')?.remove();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (e) {
    err.textContent = e.message; err.style.display = 'block';
    btn.disabled = false; btn.textContent = 'Submit & get my voucher';
  }
});

document.getElementById('copyBtn').addEventListener('click', async (ev) => {
  const code = document.getElementById('code').textContent;
  try { await navigator.clipboard.writeText(code); } catch (_) {}
  ev.target.textContent = 'Copied ✓';
  setTimeout(() => ev.target.textContent = 'Copy', 1800);
});
</script>
</body>
</html>
