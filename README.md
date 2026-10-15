# Justlife — QR Feedback & Voucher (100% free)

The client scans the QR code → **page 1** asks them to write a Google review → **page 2** (`voucher.html`) shows the voucher code.
No server or hosting to pay for:

- **GitHub Pages** shows the page (free).
- **Google Apps Script** saves the data and releases the voucher (free). It runs on your Google account.

The voucher code is **never** in this repo. It's stored privately in your Google account and
sent only to people who scanned the QR code.

## Files

| File | What it is |
|---|---|
| `index.html` | Page 1: "Write a Google review" → how-to popup → Google (same tab) → "Thank you" popup with the gift button |
| `voucher.html` | Page 2: the voucher code (fetched from Google, only for people who scanned the QR) |
| `qr.html` | Branded QR poster: download the PNG or print |
| `assets/logo.png` | Justlife logo |
| `apps-script/Code.gs` | Code you paste into Google Apps Script (step 1) |

## Step 1: Google side (10 minutes)

1. Go to **sheets.new** to create a new Google Sheet. Name it `Justlife QR Feedback`.
2. Menu **Extensions → Apps Script**. Delete everything in the editor, paste all of
   `apps-script/Code.gs`, and click **Save** 💾.
3. Click the **gear icon (Project Settings)** → scroll to **Script properties** → **Add script property**
   - Property: `VOUCHER_CODE`
   - Value: your voucher code
   - **Save**
4. Back in the editor, choose the function **`setup`** at the top and click **▶ Run**.
   Google asks for permission → **Review permissions** → pick your account →
   **Advanced → Go to project (unsafe)** → **Allow**. This is normal for your own scripts.
5. Click **Deploy → New deployment** → gear icon → **Web app**
   - Execute as: **Me**
   - Who has access: **Anyone**
   - **Deploy** → copy the **Web app URL** (ends with `/exec`).

## Step 2: GitHub side

1. In **both** `index.html` and `voucher.html`, find `API_URL: 'PASTE_YOUR_APPS_SCRIPT_URL_HERE'`
   and paste your URL between the quotes. (`GOOGLE_REVIEW_URL` is already set to Justlife
   Healthcare's direct "write a review" link.)
2. Upload all files to your repo (**Add file → Upload files**), then **Commit changes**.
3. Repo **Settings → Pages** → Source: **Deploy from a branch** → Branch: **main** / **(root)** → **Save**.
4. After about a minute your site is live at `https://YOUR-USERNAME.github.io/YOUR-REPO/`

## Step 3: QR code

Open `https://YOUR-USERNAME.github.io/YOUR-REPO/qr.html` → **Download PNG** or **Print**.

## Where the data goes

- **Google Sheet**:
  - `Stats` tab: QR scans, Google review button clicks, vouchers shown
  - `Events` tab: every scan, click and voucher, with time
- **Google Drive** folder `Justlife QR Feedback`:
  - `stats.txt`: live counters
  - `vouchers.txt`: each voucher shown, and whether that client clicked the Google button first

## Good to know

A website can't see whether someone actually posted a Google review. Google doesn't
share that. The page counts clicks on the Google button, and the "Get my gift" button
works either way. Google's rules forbid giving rewards *for* reviews, so the voucher is
presented as a thank-you gift for every client.

## Changing something later

- **New voucher code:** change `VOUCHER_CODE` in Script properties. No redeploy needed.
- **Changed `Code.gs`:** **Deploy → Manage deployments → ✏️ Edit → Version: New version → Deploy**.
  The URL stays the same.
