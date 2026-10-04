<?php
// Read environment variables set in Render Dashboard
$host     = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?: 'bnd4x6plqxd6rehuv8fd-mysql.services.clever-cloud.com';
$user     = getenv('DB_USER') ?: $_ENV['DB_USER'] ?: 'ulqrmv972ja3hm6n';
$pass     = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?: 'yI8TdU4OAs08jM4avzrB';
$dbname   = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?: 'bnd4x6plqxd6rehuv8fd';
$port     = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?: 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, (int)$port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
