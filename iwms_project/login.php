<?php
require __DIR__.'/includes/config.php';
if (!empty($_SESSION['user'])) { header('Location: index.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $stmt=db()->prepare('SELECT * FROM users WHERE username=? AND active=1 LIMIT 1');
        $stmt->execute([trim($_POST['username'] ?? '')]);
        $u=$stmt->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            $_SESSION['user']=['id'=>$u['id'],'name'=>$u['full_name'],'username'=>$u['username'],'role'=>$u['role']];
            header('Location: index.php'); exit;
        }
        $error='Invalid username or password.';
    } catch(Throwable $e) { $error='Database connection error. Run setup.php first.'; }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>IWMS Login</title><link rel="stylesheet" href="assets/style.css"></head><body class="login-page"><div class="login-card"><div class="brand"><div class="brand-mark">W</div><div><h1>IWMS</h1><span>Inventory & Warehouse Management</span></div></div><h2>Sign in</h2><?php if($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post"><label>Username<input name="username" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn primary full" type="submit">Login</button></form><div class="hint">Demo admin: <b>admin / admin123</b><br>Demo staff: <b>staff / staff123</b></div></div></body></html>
