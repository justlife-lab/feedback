<?php
/*
 * ===== Justlife QR Feedback & Voucher — settings =====
 * Edit the values below, then upload. Keep this file private:
 * it holds the voucher code and the admin password.
 */

// Your voucher (only revealed AFTER a client submits the feedback form)
const VOUCHER_CODE   = 'IVME150';
const VOUCHER_VALUE  = '150 AED';
const VOUCHER_TITLE  = 'off your IV Therapy at home';

// Password for admin.php (the stats page). CHANGE THIS before going live.
const ADMIN_PASSWORD = 'change-me-now';

// Timezone used in the .txt logs
const APP_TIMEZONE = 'Asia/Dubai';

// Brand
const BRAND_NAME = 'Justlife';

date_default_timezone_set(APP_TIMEZONE);
