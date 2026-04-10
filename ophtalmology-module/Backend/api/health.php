<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$health = [
    'success' => true,
    'message' => 'API is healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'],
    'database' => [
        'status' => 'checking...'
    ]
];

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=remera1", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $health['database']['status'] = 'connected';
} catch(PDOException $e) {
    $health['database']['status'] = 'error';
    $health['database']['error'] = $e->getMessage();
    $health['success'] = false;
}

// Test write permission
$test_dir = __DIR__ . '/../../test';
if (!is_dir($test_dir)) {
    @mkdir($test_dir, 0777, true);
}
$health['writable'] = is_writable(__DIR__);

http_response_code($health['success'] ? 200 : 500);
echo json_encode($health, JSON_PRETTY_PRINT);
?>
