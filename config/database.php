<?php
/**
 * Database Connection Configuration
 * University-Level Student CRM
 * Supports local XAMPP environment and cloud deployments (Vercel, Railway, PlanetScale, Aiven, TiDB)
 */

// 1. Support full database connection strings (DATABASE_URL, MYSQL_URL)
$databaseUrl = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_ENV['MYSQL_URL'] ?? '')));

if (!empty($databaseUrl)) {
    $dbParts = parse_url($databaseUrl);
    $dbHost = $dbParts['host'] ?? 'localhost';
    $dbPort = $dbParts['port'] ?? 3306;
    $dbUser = isset($dbParts['user']) ? urldecode($dbParts['user']) : 'root';
    $dbPass = isset($dbParts['pass']) ? urldecode($dbParts['pass']) : '';
    $dbName = ltrim($dbParts['path'] ?? '', '/');
} else {
    // 2. Support discrete environment variables with local XAMPP defaults
    $dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
    $dbPort = (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306));
    $dbName = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: ($_ENV['DB_NAME'] ?? ($_ENV['DB_DATABASE'] ?? 'university_crm_demo')));
    $dbUser = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: ($_ENV['DB_USER'] ?? ($_ENV['DB_USERNAME'] ?? 'root')));
    $dbPass = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: ($_ENV['DB_PASS'] ?? ($_ENV['DB_PASSWORD'] ?? '')));
}

$dbCharset = 'utf8mb4';
$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Automatic SSL/TLS encryption for cloud-hosted databases (TiDB, Aiven, PlanetScale, Railway)
if ($dbHost !== 'localhost' && $dbHost !== '127.0.0.1') {
    $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

// Custom CA certificate bundle if explicitly specified
$sslCa = getenv('MYSQL_ATTR_SSL_CA') ?: ($_ENV['MYSQL_ATTR_SSL_CA'] ?? null);
if ($sslCa && file_exists($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    // Ensure compatibility with group-by aggregation across TiDB / MySQL instances
    try {
        $pdo->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
    } catch (Exception $eSqlMode) {
        // Continue if sql_mode cannot be changed in restricted cloud instance
    }
    
    // Check database schema initialization once per session to avoid running SHOW TABLES on every request
    if (!isset($_SESSION['db_initialized'])) {
        try {
            $hasUsers = false;
            $checkTbl = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($checkTbl && $checkTbl->fetch()) {
                $hasUsers = true;
                $_SESSION['db_initialized'] = true;
            }
            
            if (!$hasUsers) {
                $sqlPath = __DIR__ . '/../database/tidb_import.sql';
                if (!file_exists($sqlPath)) {
                    $sqlPath = __DIR__ . '/../database/university_crm.sql';
                }
                if (file_exists($sqlPath)) {
                    $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
                    $initSql = file_get_contents($sqlPath);
                    $initSql = preg_replace('/^\s*USE\s+[^;]+;\s*/mi', '', $initSql);
                    $pdo->exec($initSql);
                    $_SESSION['db_initialized'] = true;
                }
            }
        } catch (Exception $autoEx) {
            // Silently continue if schema exists or driver restricts auto-exec
        }
    }

    // Automatically align system identities and demo profiles with official EdrootsCRM Manual
    require_once __DIR__ . '/../database/sync_edroots_data.php';
    sync_edroots_database_data($pdo);
} catch (PDOException $e) {
    $isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']);
    
    // Display friendly troubleshooting guide for both local and cloud setups
    echo '<div style="font-family:Segoe UI, sans-serif; max-width:650px; margin:60px auto; padding:30px; border:1px solid #fed7aa; background:#fff7ed; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.05);">';
    echo '<h2 style="color:#c2410c; margin-top:0;">Database Connection Notice</h2>';
    echo '<p style="color:#431407; font-size:15px; line-height:1.6;">Could not connect to the MySQL database <strong>' . htmlspecialchars($dbName) . '</strong> on host <code>' . htmlspecialchars($dbHost) . ':' . $dbPort . '</code>.</p>';
    
    if ($isVercel) {
        echo '<p style="color:#78350f; font-size:14px;"><strong>For Vercel Deployment:</strong></p>';
        echo '<ol style="color:#78350f; font-size:14px; line-height:1.8;">';
        echo '<li>Configure your cloud MySQL database credentials in <strong>Vercel &rarr; Project Settings &rarr; Environment Variables</strong>.</li>';
        echo '<li>Set <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, and <code>DB_PASSWORD</code> (or set <code>DATABASE_URL</code>).</li>';
        echo '<li>Import the schema file <code>database/university_crm.sql</code> into your remote MySQL database (e.g., TiDB Cloud, Aiven, PlanetScale, Railway, or AWS RDS).</li>';
        echo '</ol>';
    } else {
        echo '<p style="color:#78350f; font-size:14px;"><strong>For Local XAMPP:</strong></p>';
        echo '<ol style="color:#78350f; font-size:14px; line-height:1.8;">';
        echo '<li>Ensure the XAMPP MySQL service is running.</li>';
        echo '<li>Import the database schema using phpMyAdmin from <code>database/university_crm.sql</code>.</li>';
        echo '</ol>';
    }
    
    echo '<p style="color:#9a3412; font-size:12px; margin-bottom:0;">Error details: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    exit;
}

/**
 * Returns the active PDO connection
 * @return PDO
 */
function getDB() {
    global $pdo;
    return $pdo;
}

