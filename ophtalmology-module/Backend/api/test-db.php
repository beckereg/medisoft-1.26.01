<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=remera1", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Connected to remera1 database',
        'patientCount' => $result['total']
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>