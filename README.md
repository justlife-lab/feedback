# Justlife — QR Feedback & Voucher

A client scans a QR code, gives a star rating and writes a short comment, and only then
receives the **IVME150** voucher (150 AED off IV Therapy). Scans, feedback and vouchers
issued are counted automatically in `.txt` files.

Brand colour: **#02C2FF** (Justlife cyan, from the app icon). Buttons use dark navy text
(#06283D) on cyan because white on #02C2FF is too faint to read (2.1:1 contrast).

## Files

| File | What it does |
|---|---|
| `index.php` | The page clients see after scanning (rating + comment → voucher) |
| `submit.php` | Checks the form and releases the voucher code; nothing is released without a valid form |
| `admin.php` | Password-protected stats dashboard + downloads of the .txt files |
| `qr.html` | Branded QR poster: type your URL, download the PNG or print |
| `config.php` | **Edit this**: voucher code and admin password |
| `lib.php` | Shared helpers (txt counters with file locking) |
| `assets/logo.png` | Justlife app icon |
| `data/` | Created automatically: `stats.txt`, `feedback.txt`, `events.txt` (blocked from public access) |

## Setup

1. **Edit `config.php`**: set a strong `ADMIN_PASSWORD`.
2. **Push to GitHub** (keep the repo **private**, because `config.php` contains the voucher code):
   ```bash
   git init
   git add .
   git commit -m "Justlife QR feedback & voucher page"
   git branch -M main
   git remote add origin https://github.com/YOUR-USER/justlife-review-voucher.git
   git push -u origin main
   ```
3. **Host it on a PHP server.** GitHub Pages can't run PHP. Use cPanel/Hostinger, or any
   Apache + PHP 8 host, and deploy from the repo or upload the files. Make sure the
   `data/` folder is writable (permission `755`, or `775` if needed).
4. **Make the QR code**: open `https://your-site.com/qr.html`, then **Download PNG** or **Print**.
5. **See results**: `https://your-site.com/admin.php`

## What gets saved

- `data/stats.txt`: QR scans, feedback submitted, vouchers issued, scan → feedback rate
- `data/feedback.txt`: `date | rating=5 | comment`
- `data/events.txt`: every scan and submission, one per line

## How "no feedback, no voucher" is enforced

- The voucher code is never in the page HTML. `submit.php` sends it back only after
  the form passes validation (star rating chosen, comment 10+ characters).
- Requests must carry a one-time token from a real page visit, so nobody can request
  the voucher by calling the API directly. A hidden honeypot field blocks bots.
- Each device can claim one voucher (session + 1-year cookie). A second submission shows the same
  code again but is not counted twice.
