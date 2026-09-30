<?php
declare(strict_types=1);

/*
 * BAM Security Website — Version 1 Installer
 *
 * This installer is intentionally self-contained so a fresh server can be
 * configured before the application's database configuration exists.
 */

$lockFile = __DIR__ . '/install.lock';
$installed = is_file($lockFile);
$message = '';
$error = '';
$step = '';

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function clean_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    return rtrim($url, '/');
}
function split_sql(string $sql): array {
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $parts = preg_split('/;\s*(?:\R|$)/', $sql);
    return array_values(array_filter(array_map('trim', $parts), static fn($s) => $s !== ''));
}
function write_atomic(string $path, string $contents): void {
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $contents, LOCK_EX) === false || !rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to write ' . basename($path) . '. Check server file permissions.');
    }
}
function config_template(string $host, string $port, string $name, string $user, string $pass, string $appUrl): string {
    $template = file_get_contents(__DIR__ . '/config/config.php');
    if ($template === false) throw new RuntimeException('Unable to read the configuration template.');
    $pairs = [
        '__DB_HOST__' => addslashes($host),
        '__DB_PORT__' => addslashes($port),
        '__DB_NAME__' => addslashes($name),
        '__DB_USER__' => addslashes($user),
        '__DB_PASS__' => addslashes($pass),
        '__APP_URL__' => addslashes($appUrl),
    ];
    return str_replace(array_keys($pairs), array_values($pairs), $template);
}

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $appUrl = clean_url((string)($_POST['app_url'] ?? ''));
    $dbHost = trim((string)($_POST['db_host'] ?? ''));
    $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $adminName = trim((string)($_POST['admin_name'] ?? 'Administrator'));
    $adminUsername = trim((string)($_POST['admin_username'] ?? 'Admin'));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPassword = (string)($_POST['admin_password'] ?? '');

    try {
        if (!filter_var($appUrl, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $appUrl)) {
            throw new RuntimeException('Enter a valid website URL, for example https://example.com');
        }
        if ($dbHost === '' || $dbName === '' || $dbUser === '') throw new RuntimeException('Database host, database name and database username are required.');
        if (!ctype_digit($dbPort) || (int)$dbPort < 1 || (int)$dbPort > 65535) throw new RuntimeException('Database port must be a valid number.');
        if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $adminUsername)) throw new RuntimeException('Admin username must be 3–80 characters and use only letters, numbers, dot, underscore or hyphen.');
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid administrator email address.');
        if (strlen($adminPassword) < 4) throw new RuntimeException('Administrator password must be at least 4 characters.');
        if ($adminName === '') throw new RuntimeException('Administrator name is required.');

        $dsn = 'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $step = 'Database connection successful.';

        $schemaFile = __DIR__ . '/database/schema_bamId.sql';
        $schema = file_get_contents($schemaFile);
        if ($schema === false) throw new RuntimeException('Database schema file is missing.');

        foreach (split_sql($schema) as $sql) {
            $pdo->exec($sql);
        }
        $step .= ' Core database tables created.';

        // Tables created dynamically by the application are created now too,
        // so a fresh installation is ready before the first admin login.
        $config = config_template($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $appUrl);
        write_atomic(__DIR__ . '/config/config.php', $config);
        require __DIR__ . '/config/config.php';
        ensure_admins_table();
        ensure_settings_table();
        ensure_frontend_content_table();
        ensure_events_table();
        ensure_invoice_table();
        ensure_letters_table();
        ensure_audit_table();
        ensure_member_schema();
        ensure_member_password_column();

        $check = $pdo->prepare('SELECT id FROM admins WHERE email = ? OR username = ? LIMIT 1');
        $check->execute([$adminEmail, $adminUsername]);
        if ($check->fetch()) throw new RuntimeException('An administrator with that email or username already exists in this database.');

        $stmt = $pdo->prepare('INSERT INTO admins (username, email, password_hash, active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$adminUsername, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
        $adminId = (int)$pdo->lastInsertId();

        // Make the configured site URL appear in the SEO/canonical tags of the
        // static public entry pages.
        foreach (['home.html', 'index.html'] as $publicFile) {
            $path = __DIR__ . '/' . $publicFile;
            if (is_file($path)) {
                $html = file_get_contents($path);
                if ($html !== false) write_atomic($path, str_replace('__APP_URL__', $appUrl, $html));
            }
        }

        foreach (['uploads', 'logs'] as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
                throw new RuntimeException('Unable to create ' . $dir . ' directory.');
            }
        }

        $lockData = "BAM Security Website Version 1 installed\n" .
                    'Installed: ' . date('c') . "\n" .
                    'Application URL: ' . $appUrl . "\n" .
                    'Administrator ID: ' . $adminId . "\n";
        write_atomic($lockFile, $lockData);
        @unlink(__DIR__ . '/setup_admin.php');
        @unlink(__DIR__ . '/health.php');

        $installed = true;
        $message = 'Installation completed successfully. Your database is connected and the administrator account has been created.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
        error_log('BAM installer: ' . $e->getMessage());
    }
} elseif ($installed) {
    $message = 'This installation has already been completed. For security, the installer is locked.';
}

$detectedUrl = clean_url(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'example.com'));
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>BAM Security Website — Version 1 Installer</title>
<style>
:root{color-scheme:dark;--bg:#050505;--panel:#0d0f10;--line:#303438;--text:#f5f7f8;--muted:#aab1b7;--red:#e30613;--red2:#ff3440;--ok:#28c76f}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 50% -10%,rgba(227,6,19,.18),transparent 42%),#050505;color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,Arial,sans-serif;min-height:100vh;padding:28px 14px}.wrap{width:min(900px,100%);margin:auto}.brand{text-align:center;margin-bottom:22px}.brand img{width:min(190px,55vw);max-height:100px;object-fit:contain}.brand h1{margin:10px 0 4px;font-size:clamp(22px,5vw,34px)}.brand p{margin:0;color:var(--muted)}.card{background:rgba(13,15,16,.94);border:1px solid var(--line);border-radius:18px;padding:22px;box-shadow:0 20px 60px rgba(0,0,0,.38)}h2{font-size:19px;margin:0 0 16px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{margin-bottom:14px}.field.full{grid-column:1/-1}.field label{display:block;font-weight:700;font-size:13px;margin-bottom:7px;color:#e8ecef}.field small{display:block;color:var(--muted);margin-top:6px;font-size:12px}.field input{width:100%;padding:13px 14px;border-radius:10px;border:1px solid #3d4247;background:#080a0b;color:#fff;font-size:15px;outline:none}.field input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(227,6,19,.13)}.password{display:flex}.password input{border-radius:10px 0 0 10px}.password button{border:1px solid #3d4247;border-left:0;background:#171a1c;color:#fff;padding:0 13px;border-radius:0 10px 10px 0;cursor:pointer}.actions{margin-top:8px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}.btn{border:0;border-radius:10px;padding:13px 18px;background:var(--red);color:#fff;font-weight:800;cursor:pointer}.btn:hover{background:var(--red2)}.notice,.error,.success{padding:13px 14px;border-radius:10px;margin-bottom:18px;line-height:1.5}.notice{background:#15191b;border:1px solid #33393d;color:#d9dee2}.error{background:rgba(227,6,19,.12);border:1px solid rgba(227,6,19,.5);color:#ffb8bd}.success{background:rgba(40,199,111,.10);border:1px solid rgba(40,199,111,.45);color:#a9f1c8}.checks{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:18px 0}.check{border:1px solid var(--line);border-radius:10px;padding:12px;color:#dce2e6}.check b{display:block;color:#fff;margin-bottom:3px}.small{color:var(--muted);font-size:12px}.links{margin-top:18px;display:flex;gap:12px;flex-wrap:wrap}.links a{color:#ff6a73}.locked{text-align:center;padding:20px}.locked .big{font-size:44px}.locked h2{margin-top:4px}@media(max-width:700px){.grid,.checks{grid-template-columns:1fr}.card{padding:17px}body{padding:18px 10px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand"><img src="assets/bam-logo.png" alt="Bouncer Association of Meghalaya"><h1>Version 1 Installer</h1><p>Bouncer Association of Meghalaya — Security Management Website</p></div>
  <?php if ($installed): ?>
    <div class="card locked">
      <div class="big">✓</div><h2>Installation Complete</h2>
      <div class="success"><?=h($message)?></div>
      <p class="small">The installer is locked by <b>install.lock</b>. Delete <b>install.php</b> from your server after confirming the site works.</p>
      <div class="links"><a href="./">Open Website</a><a href="admin/login.php">Open Admin Login</a><a href="verify.php">Member Verification</a></div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="notice"><b>Fresh server installation.</b> Create the MySQL database in your hosting control panel first, then enter its credentials below. This installer creates the required tables and your first administrator. It does not require an existing BAM database.</div>
      <?php if ($error): ?><div class="error"><b>Installation stopped:</b> <?=h($error)?></div><?php endif; ?>
      <?php if ($step): ?><div class="success"><?=h($step)?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <h2>1. Website</h2>
        <div class="grid">
          <div class="field full"><label>Website URL</label><input type="url" name="app_url" value="<?=h($_POST['app_url'] ?? $detectedUrl)?>" placeholder="https://example.com" required><small>Use the final domain/subdomain where this website will run. Include https:// when SSL is enabled.</small></div>
        </div>
        <h2>2. MySQL Database</h2>
        <div class="grid">
          <div class="field"><label>Database Host</label><input name="db_host" value="<?=h($_POST['db_host'] ?? '')?>" placeholder="localhost or sqlXXX.infinityfree.com" required></div>
          <div class="field"><label>Database Port</label><input name="db_port" value="<?=h($_POST['db_port'] ?? '3306')?>" inputmode="numeric" required></div>
          <div class="field"><label>Database Name</label><input name="db_name" value="<?=h($_POST['db_name'] ?? '')?>" required></div>
          <div class="field"><label>Database Username</label><input name="db_user" value="<?=h($_POST['db_user'] ?? '')?>" required></div>
          <div class="field full"><label>Database Password</label><input type="password" name="db_pass" value="" autocomplete="new-password"><small>The password is written only to your local <b>config/config.php</b> during installation.</small></div>
        </div>
        <h2>3. First Administrator</h2>
        <div class="grid">
          <div class="field"><label>Administrator Name</label><input name="admin_name" value="<?=h($_POST['admin_name'] ?? 'Administrator')?>" required></div>
          <div class="field"><label>Username</label><input name="admin_username" value="<?=h($_POST['admin_username'] ?? 'Admin')?>" minlength="3" maxlength="80" required></div>
          <div class="field"><label>Email / Login Email</label><input type="email" name="admin_email" value="<?=h($_POST['admin_email'] ?? '')?>" required></div>
          <div class="field"><label>Password <span class="small">(minimum 4 characters)</span></label><div class="password"><input id="adminPassword" type="password" name="admin_password" minlength="4" autocomplete="new-password" required><button type="button" id="showPassword">Show</button></div></div>
        </div>
        <div class="actions"><button class="btn" type="submit">Install BAM Version 1</button></div>
      </form>
      <div class="checks"><div class="check"><b>PHP 8+</b><span class="small">Required by the application.</span></div><div class="check"><b>MySQL / MariaDB</b><span class="small">PDO MySQL must be enabled.</span></div><div class="check"><b>HTTPS</b><span class="small">Strongly recommended for admin and PWA.</span></div></div>
      <div class="small">After installation, remove <b>install.php</b>. Never share your database password or administrator password.</div>
    </div>
  <?php endif; ?>
</div>
<script>
const b=document.getElementById('showPassword');
if(b)b.addEventListener('click',()=>{const i=document.getElementById('adminPassword');const show=i.type==='password';i.type=show?'text':'password';b.textContent=show?'Hide':'Show';});
</script>
</body>
</html>
