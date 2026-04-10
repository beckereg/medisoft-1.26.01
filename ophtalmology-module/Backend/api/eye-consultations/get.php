<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$consultationId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$consultationId) {
    sendResponse(false, null, 'Consultation ID is required', 400);
}

try {
    // Get consultation main data
    $query = "SELECT * FROM eye_consultations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        sendResponse(false, null, 'Consultation not found', 404);
    }
    
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get symptoms
    $query = "SELECT * FROM eye_symptoms WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assessments
    $query = "SELECT * FROM eye_assessments WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get diagnosis
    $query = "SELECT * FROM eye_diagnosis WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get treatments
    $query = "SELECT * FROM eye_treatments WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get consumables
    $query = "SELECT * FROM eye_consumables WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get discharge
    $query = "SELECT * FROM eye_discharge WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    $discharge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'consultation' => [
            'type' => $consultation['consultation_type'],
            'notes' => $consultation['consultation_notes'],
            'date' => $consultation['consultation_date']
        ],
        'symptoms' => $symptoms,
        'assessments' => $assessments,
        'diagnosis' => $diagnosis ? [
            'primary' => $diagnosis['primary_diagnosis'],
            'code' => $diagnosis['diagnosis_code'],
            'notes' => $diagnosis['clinical_notes']
        ] : null,
        'treatment' => $treatments,
        'consumables' => $consumables,
        'discharge' => $discharge ? [
            'patientStatus' => $discharge['patient_status'],
            'dischargeDate' => $discharge['discharge_date'],
            'dischargingService' => $discharge['discharging_service'],
            'clinicalSummary' => $discharge['clinical_summary']
        ] : null
    ];
    
    sendResponse(true, $response, 'Consultation found successfully');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>