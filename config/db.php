<?php
$isRender = getenv("RENDER") || getenv("RENDER_SERVICE_ID") || getenv("DB_HOST");

if ($isRender) {
    $host = getenv("DB_HOST");
    $user = getenv("DB_USER");
    $password = getenv("DB_PASS");
    $dbname = getenv("DB_NAME");
    $port = getenv("DB_PORT") ?: 4000;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = mysqli_init();

        if (!$conn) {
            throw new Exception("mysqli_init failed");
        }

        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

        mysqli_real_connect(
            $conn,
            $host,
            $user,
            $password,
            $dbname,
            (int)$port,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        $conn->set_charset("utf8mb4");

    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact the system administrator.");
    }

} else {
    $host = "localhost";
    $user = "root";
    $password = "";
    $dbname = "assessment_db";
    $port = 3306;

    $conn = new mysqli($host, $user, $password, $dbname, (int)$port);

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
}
?>
