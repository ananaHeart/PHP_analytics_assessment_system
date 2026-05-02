<?php
$isRender = getenv("RENDER") || getenv("RENDER_SERVICE_ID");

if ($isRender) {
    // Render + TiDB Cloud
    $host = getenv("DB_HOST");
    $user = getenv("DB_USER");
    $password = getenv("DB_PASS");
    $dbname = getenv("DB_NAME");
    $port = getenv("DB_PORT") ?: 4000;
} else {
    // Local XAMPP
    $host = "localhost";
    $user = "root";
    $password = "";
    $dbname = "assessment_db";
    $port = 3306;
}

$conn = new mysqli($host, $user, $password, $dbname, (int)$port);

if ($conn->connect_error) {
    http_response_code(500);
    exit("Service temporarily unavailable.");
}

$conn->set_charset("utf8mb4");
?>
