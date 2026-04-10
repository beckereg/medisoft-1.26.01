<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

function getSingleConsultation($db, $consultationId) {
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
    
    // Transform symptoms to match expected format
    $transformedSymptoms = array_map(function($symptom) {
        return [
            'name' => $symptom['symptom_name'],
            'present' => (bool)$symptom['present'],
            'laterality' => $symptom['laterality'] ?? ''
        ];
    }, $symptoms);
    
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
        'id' => $consultation['id'],
        'patient_id' => $consultation['patient_id'],
        'consultation' => [
            'type' => $consultation['consultation_type'],
            'notes' => $consultation['consultation_notes'],
            'date' => $consultation['consultation_date']
        ],
        'symptoms' => $transformedSymptoms,
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
        ] : null,
        'status' => $consultation['status'],
        'created_at' => $consultation['created_at'],
        'updated_at' => $consultation['updated_at']
    ];
    
    sendResponse(true, $response, 'Consultation found successfully');
}

function getPatientConsultations($db, $patientId) {
    // Get all consultations for the patient
    $query = "SELECT * FROM eye_consultations WHERE patient_id = :patient_id ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':patient_id', $patientId);
    $stmt->execute();
    
    $consultations = [];
    while ($consultation = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $consultationId = $consultation['id'];
        
        // Get symptoms for this consultation
        $query = "SELECT * FROM eye_symptoms WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $symptoms = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Get assessments for this consultation
        $query = "SELECT * FROM eye_assessments WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $assessments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Get diagnosis for this consultation
        $query = "SELECT * FROM eye_diagnosis WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $diagnosis = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Get treatments for this consultation
        $query = "SELECT * FROM eye_treatments WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $treatments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Get consumables for this consultation
        $query = "SELECT * FROM eye_consumables WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $consumables = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Get discharge for this consultation
        $query = "SELECT * FROM eye_discharge WHERE consultation_id = :id";
        $stmt2 = $db->prepare($query);
        $stmt2->bindParam(':id', $consultationId);
        $stmt2->execute();
        $discharge = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Transform symptoms to match expected format
        $transformedSymptoms = array_map(function($symptom) {
            return [
                'name' => $symptom['symptom_name'],
                'present' => (bool)$symptom['present'],
                'laterality' => $symptom['laterality'] ?? ''
            ];
        }, $symptoms);
        
        // Format consultation data
        $consultations[] = [
            'id' => $consultation['id'],
            'patient_id' => $consultation['patient_id'],
            'consultation' => [
                'type' => $consultation['consultation_type'],
                'notes' => $consultation['consultation_notes'],
                'date' => $consultation['consultation_date']
            ],
            'symptoms' => $transformedSymptoms,
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
            ] : null,
            'status' => $consultation['status'],
            'created_at' => $consultation['created_at'],
            'updated_at' => $consultation['updated_at']
        ];
    }
    
    sendResponse(true, $consultations, 'Patient consultations retrieved successfully');
}

// Get parameters from URL
$consultationId = isset($_GET['id']) ? $_GET['id'] : null;
$patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;

if (!$consultationId && !$patientId) {
    sendResponse(false, null, 'Either consultation ID or patient ID is required', 400);
}

try {
    if ($consultationId) {
        // Get single consultation by ID
        getSingleConsultation($db, $consultationId);
    } elseif ($patientId) {
        // Get all consultations for a patient
        getPatientConsultations($db, $patientId);
    }
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
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
        'id' => $consultation['id'],
        'patient_id' => $consultation['patient_id'],
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
        ] : null,
        'status' => $consultation['status'],
        'created_at' => $consultation['created_at'],
        'updated_at' => $consultation['updated_at']
    ];
    
    sendResponse(true, $response, 'Consultation found successfully');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>