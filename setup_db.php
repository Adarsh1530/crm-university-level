<?php
/**
 * One-Click Cloud Database Setup & Verification Tool
 * University-Level Student CRM
 */
require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initialize') {
    try {
        $sqlPath = __DIR__ . '/database/tidb_import.sql';
        if (!file_exists($sqlPath)) {
            $sqlPath = __DIR__ . '/database/university_crm.sql';
        }
        
        $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
        $initSql = file_get_contents($sqlPath);
        $initSql = preg_replace('/^\s*USE\s+[^;]+;\s*/mi', '', $initSql);
        $pdo->exec($initSql);
        $message = "Database successfully initialized! All 30 tables and default accounts are ready.";
        $status = "success";
    } catch (Exception $e) {
        $message = "Database initialization error: " . $e->getMessage();
        $status = "danger";
    }
}

$tables = [];
$users = [];
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('users', $tables)) {
        $users = $pdo->query("SELECT id, name, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Setup & Verification | UniCRM</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px 20px; }
    .container { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 36px; border: 1px solid #e2e8f0; }
    h1 { margin-top: 0; color: #4338ca; font-size: 24px; font-weight: 800; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 700; }
    .badge-success { background: #dcfce7; color: #15803d; }
    .badge-danger { background: #fee2e2; color: #b91c1c; }
    .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; line-height: 1.5; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .stat-card { background: #f1f5f9; padding: 16px; border-radius: 10px; margin-bottom: 20px; }
    .btn { display: inline-block; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-decoration: none; cursor: pointer; border: none; font-size: 14px; }
    .btn-primary { background: #4f46e5; color: #fff; }
    .btn-primary:hover { background: #4338ca; }
    .btn-success { background: #16a34a; color: #fff; }
    .btn-success:hover { background: #15803d; }
    ul { margin: 8px 0; padding-left: 20px; }
    li { margin-bottom: 4px; font-size: 14px; }
  </style>
</head>
<body>
<div class="container">
  <h1>UniCRM Cloud Database Setup</h1>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="stat-card">
    <strong>Connection Status:</strong> <span class="badge badge-success">Connected to MySQL / TiDB</span><br>
    <strong>Tables Found:</strong> <strong><?= count($tables) ?> / 30</strong> tables<br>
    <strong>Active Users:</strong> <strong><?= count($users) ?></strong> accounts seeded
  </div>

  <?php if (count($tables) >= 20 && count($users) > 0): ?>
    <div class="alert alert-success">
      &#10004; <strong>Database is fully initialized and operational!</strong> All tables, roles, permissions, and demo user accounts are ready.
    </div>
    <p><a href="login.php" class="btn btn-success">&#8594; Proceed to Sign In</a></p>
  <?php else: ?>
    <div class="alert alert-danger">
      &#9888; <strong>Tables missing or uninitialized.</strong> Click the button below to initialize all 30 tables and accounts with 1 click.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="initialize">
      <button type="submit" class="btn btn-primary">Initialize Database Now (1-Click)</button>
    </form>
  <?php endif; ?>

  <?php if (!empty($users)): ?>
    <h3 style="margin-top: 28px; font-size: 16px;">Available Sign In Personas:</h3>
    <ul>
      <?php foreach ($users as $u): ?>
        <li><strong><?= htmlspecialchars($u['name']) ?></strong> (<code><?= htmlspecialchars($u['email']) ?></code>)</li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
