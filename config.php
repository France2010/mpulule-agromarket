<?php
/**
 * Config loader — safe to commit.
 * Loads real secrets from config.local.php (gitignored).
 * Falls back to config.example.php for new setups.
 */

$local = __DIR__ . '/config.local.php';
$example = __DIR__ . '/config.example.php';

if (file_exists($local)) {
    require $local;
} elseif (file_exists($example)) {
    require $example;
} else {
    die('Missing config.local.php. Copy config.example.php and fill in your credentials.');
}