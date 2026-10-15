/**
 * Justlife QR Feedback — free backend on Google Apps Script.
 *
 * Saves everything to:
 *   - this Google Sheet (tabs: Stats, Events)
 *   - stats.txt and vouchers.txt in the Google Drive folder "Justlife QR Feedback"
 *
 * The voucher code is NOT in this file. Set it in:
 *   Project Settings (gear icon) → Script properties → VOUCHER_CODE = <your code>
 */

const TZ = 'Asia/Dubai';
const DRIVE_FOLDER = 'Justlife QR Feedback';
const STAT_LABELS = {
  qr_scans:             'QR scans (unique visits)',
  google_review_clicks: 'Google review button clicks',
  vouchers_issued:      'Vouchers shown',
};

/* ---------------- web endpoints ---------------- */

function doGet(e) {
  const p = (e && e.parameter) || {};
  if (p.action === 'scan') return json_(scan_(p.src || 'direct'));
  if (p.action === 'review_click') return json_(event_(p.token, 'google_review_clicks', 'google_review_click'));
  return json_({ ok: true, service: 'justlife-feedback' });
}

function doPost(e) {
  let body;
  try { body = JSON.parse((e && e.postData && e.postData.contents) || '{}'); }
  catch (err) { return json_({ ok: false, error: 'Bad request.' }); }
  if (body.action === 'voucher') return json_(voucher_(body));
  return json_({ ok: false, error: 'Unknown action.' });
}

/* ---------------- actions ---------------- */

/** A page was opened from the QR code: count it and hand out a one-time token. */
function scan_(src) {
  withLock_(function () {
    bump_('qr_scans');
    sheet_('Events', ['Date', 'Event', 'Detail']).appendRow([now_(), 'qr_scan', 'src=' + clean_(src, 30)]);
  });
  const token = Utilities.getUuid();
  CacheService.getScriptCache().put('t_' + token, '1', 21600); // valid 6 hours
  return { ok: true, token: token };
}

/** Count a Google-review button click (once per token). */
function event_(token, statKey, eventName) {
  const cache = CacheService.getScriptCache();
  const k = 't_' + clean_(token, 64);
  if (!token || !cache.get(k)) return { ok: false };
  if (cache.get(k + '_' + statKey)) return { ok: true };                       // already counted
  cache.put(k + '_' + statKey, '1', 21600);
  withLock_(function () {
    bump_(statKey);
    sheet_('Events', ['Date', 'Event', 'Detail']).appendRow([now_(), eventName, '']);
  });
  return { ok: true };
}

/** Voucher page: needs a real token from a QR visit. Counted once per visit. */
function voucher_(b) {
  const cache = CacheService.getScriptCache();
  const k = 't_' + clean_(b.token, 64);
  if (!b.token || !cache.get(k)) return { ok: false, error: 'Your session expired. Please scan the QR code again.' };
  const voucher = PropertiesService.getScriptProperties().getProperty('VOUCHER_CODE');
  if (!voucher) return { ok: false, error: 'Voucher is not set up yet. Please contact Justlife.' };
  if (!cache.get(k + '_voucher')) {
    cache.put(k + '_voucher', '1', 21600);
    const clicked = cache.get(k + '_google_review_clicks') ? 'yes' : 'no';
    withLock_(function () {
      const t = now_();
      bump_('vouchers_issued');
      sheet_('Events', ['Date', 'Event', 'Detail']).appendRow([t, 'voucher_shown', 'clicked_google=' + clicked]);
      appendTxt_('vouchers.txt', t + ' | voucher shown | clicked_google=' + clicked + '\n');
    });
  }
  return { ok: true, voucher: voucher };
}

/* ---------------- storage helpers ---------------- */

function readStats_() {
  const sh = sheet_('Stats', ['Key', 'Label', 'Value']);
  const stats = {};
  Object.keys(STAT_LABELS).forEach(function (k) { stats[k] = 0; });
  sh.getDataRange().getValues().slice(1).forEach(function (r) {
    if (r[0] in stats) stats[r[0]] = Number(r[2]) || 0;
  });
  return stats;
}

function bump_(key) {
  const stats = readStats_();
  stats[key] = (stats[key] || 0) + 1;
  const rows = Object.keys(STAT_LABELS).map(function (k) { return [k, STAT_LABELS[k], stats[k]]; });
  const sh = sheet_('Stats', ['Key', 'Label', 'Value']);
  sh.getRange(2, 1, rows.length, 3).setValues(rows);
  writeTxt_('stats.txt', renderStats_(stats));
}

function renderStats_(s) {
  const rate = s.qr_scans ? (Math.round(s.google_review_clicks / s.qr_scans * 1000) / 10) + '%' : '—';
  let out = '# Justlife QR feedback campaign — live stats\n';
  out += '# Last updated: ' + now_() + ' (' + TZ + ')\n';
  out += '# Scan → Google review click rate: ' + rate + '\n\n';
  Object.keys(STAT_LABELS).forEach(function (k) {
    out += (k + ': ' + s[k] + '                                ').slice(0, 32) + '# ' + STAT_LABELS[k] + '\n';
  });
  return out;
}

function sheet_(name, headers) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sh = ss.getSheetByName(name);
  if (!sh) {
    sh = ss.insertSheet(name);
    sh.appendRow(headers);
    sh.getRange(1, 1, 1, headers.length).setFontWeight('bold');
    sh.setFrozenRows(1);
  }
  return sh;
}

function folder_() {
  const props = PropertiesService.getScriptProperties();
  const id = props.getProperty('FOLDER_ID');
  if (id) { try { return DriveApp.getFolderById(id); } catch (e) {} }
  const f = DriveApp.createFolder(DRIVE_FOLDER);
  props.setProperty('FOLDER_ID', f.getId());
  return f;
}

function file_(name) {
  const folder = folder_();
  const it = folder.getFilesByName(name);
  return it.hasNext() ? it.next() : folder.createFile(name, '', MimeType.PLAIN_TEXT);
}

function writeTxt_(name, content) { file_(name).setContent(content); }

function appendTxt_(name, line) {
  const f = file_(name);
  f.setContent(f.getBlob().getDataAsString() + line);
}

/* ---------------- small utilities ---------------- */

function withLock_(fn) {
  const lock = LockService.getScriptLock();
  lock.waitLock(15000);              // two scans at the same moment take turns
  try { fn(); } finally { lock.releaseLock(); }
}

function now_() { return Utilities.formatDate(new Date(), TZ, 'yyyy-MM-dd HH:mm:ss'); }

function clean_(v, max) {
  return String(v == null ? '' : v).replace(/[\r\n\t|]+/g, ' ').trim().slice(0, max);
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

/** Run this ONCE from the editor (▶ Run) to grant permissions and create the tabs + txt files. */
function setup() {
  sheet_('Stats', ['Key', 'Label', 'Value']);
  sheet_('Events', ['Date', 'Event', 'Detail']);
  const s = readStats_();
  const rows = Object.keys(STAT_LABELS).map(function (k) { return [k, STAT_LABELS[k], s[k]]; });
  sheet_('Stats', []).getRange(2, 1, rows.length, 3).setValues(rows);
  writeTxt_('stats.txt', renderStats_(s));
  file_('vouchers.txt');
  const v = PropertiesService.getScriptProperties().getProperty('VOUCHER_CODE');
  Logger.log('Setup done. Drive folder: ' + folder_().getUrl());
  Logger.log(v ? 'Voucher code is set ✓' : '⚠ Add VOUCHER_CODE in Project Settings → Script properties');
}
