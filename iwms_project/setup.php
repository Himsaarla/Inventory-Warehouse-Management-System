<?php
declare(strict_types=1);
require __DIR__.'/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents(__DIR__.'/database/schema.sql');
    $pdo->exec($sql);
    $stmt = db()->prepare('UPDATE users SET password_hash=? WHERE username=?');
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $stmt->execute([password_hash('staff123', PASSWORD_DEFAULT), 'staff']);
    echo '<h2>IWMS database installed successfully.</h2><p>Admin: <b>admin</b> / <b>admin123</b></p><p>Staff: <b>staff</b> / <b>staff123</b></p><p>Delete or rename setup.php after installation.</p><p><a href="login.php">Open application</a></p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Setup failed</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
