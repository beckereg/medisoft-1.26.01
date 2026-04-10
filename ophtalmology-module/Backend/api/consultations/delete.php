<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$consultationId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$consultationId) {
    sendResponse(false, null, 'Consultation ID is required', 400);
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Delete related records first (due to foreign key constraints)
    $tables = ['eye_symptoms', 'eye_assessments', 'eye_diagnosis', 'eye_treatments', 'eye_consumables', 'eye_discharge'];
    
    foreach ($tables as $table) {
        $query = "DELETE FROM $table WHERE consultation_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $consultationId);
        $stmt->execute();
    }
    
    // Delete main consultation
    $query = "DELETE FROM eye_consultations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    // Commit transaction
    $db->commit();
    
    sendResponse(true, null, 'Consultation deleted successfully');
    
} catch(PDOException $e) {
    $db->rollBack();
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>