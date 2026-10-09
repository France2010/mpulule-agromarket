<?php
/**
 * Mpulule AgroMarket — Configuration Template
 *
 * Copy this file to `config.local.php` and fill in your real values.
 * NEVER commit `config.local.php` to version control.
 */

// === Database ===
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mpulule_agro');

// === Mail (SMTP) ===
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-gmail-app-password');
define('MAIL_FROM',     'your-email@gmail.com');
define('MAIL_FROM_NAME','Mpulule AgroMarket');

// === Payment Gateway ===
define('PAYMENT_GATEWAY', 'anypay');

// AnyPay — Collect (customer pays you)
define('ANYPAY_API_KEY', 'your-anypay-api-key-here');
define('ANYPAY_API_URL', 'https://anypaytanzania.com/api/payments/wallet/pull/');

// AnyPay — Payout (you pay customer — loan disbursement)
define('ANYPAY_PAYOUT_URL', 'https://anypaytanzania.com/api/payments/wallet/payout/');
define('ANYPAY_PAYOUT_KEY', ANYPAY_API_KEY);

// Set to true only when AnyPay confirms payout API is stable
define('ANYPAY_PAYOUT_ENABLED', false);