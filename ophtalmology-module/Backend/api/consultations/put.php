<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$consultationId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$consultationId) {
    sendResponse(false, null, 'Consultation ID is required', 400);
}

// Get PUT data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    sendResponse(false, null, 'Invalid data', 400);
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Update main consultation
    $query = "UPDATE eye_consultations 
              SET consultation_type = :consultation_type, 
                  consultation_notes = :consultation_notes, 
                  consultation_date = :consultation_date,
                  status = :status
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':consultation_type', $data['consultation']['type']);
    $stmt->bindParam(':consultation_notes', $data['consultation']['notes']);
    $stmt->bindParam(':consultation_date', $data['consultation']['date']);
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    // Delete old symptoms and insert new ones
    $query = "DELETE FROM eye_symptoms WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if (isset($data['symptoms']) && !empty($data['symptoms'])) {
        $query = "INSERT INTO eye_symptoms (consultation_id, symptom_name, present, laterality) 
                  VALUES (:consultation_id, :symptom_name, :present, :laterality)";
        $stmt = $db->prepare($query);
        
        foreach ($data['symptoms'] as $symptom) {
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':symptom_name', $symptom['name']);
            $stmt->bindParam(':present', $symptom['present']);
            $stmt->bindParam(':laterality', $symptom['laterality']);
            $stmt->execute();
        }
    }
    
    // Delete old assessments and insert new ones
    $query = "DELETE FROM eye_assessments WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if (isset($data['assessments']) && !empty($data['assessments'])) {
        $query = "INSERT INTO eye_assessments (consultation_id, assessment_name, right_eye_value, left_eye_value) 
                  VALUES (:consultation_id, :assessment_name, :right_eye_value, :left_eye_value)";
        $stmt = $db->prepare($query);
        
        foreach ($data['assessments'] as $assessment) {
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':assessment_name', $assessment['name']);
            $stmt->bindParam(':right_eye_value', $assessment['rightEye']);
            $stmt->bindParam(':left_eye_value', $assessment['leftEye']);
            $stmt->execute();
        }
    }
    
    // Update diagnosis
    $query = "DELETE FROM eye_diagnosis WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if (isset($data['diagnosis']) && !empty($data['diagnosis']['primary'])) {
        $query = "INSERT INTO eye_diagnosis (consultation_id, primary_diagnosis, diagnosis_code, clinical_notes) 
                  VALUES (:consultation_id, :primary_diagnosis, :diagnosis_code, :clinical_notes)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':consultation_id', $consultationId);
        $stmt->bindParam(':primary_diagnosis', $data['diagnosis']['primary']);
        $stmt->bindParam(':diagnosis_code', $data['diagnosis']['code']);
        $stmt->bindParam(':clinical_notes', $data['diagnosis']['notes']);
        $stmt->execute();
    }
    
    // Delete old treatments and insert new ones
    $query = "DELETE FROM eye_treatments WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if (isset($data['treatment']) && !empty($data['treatment'])) {
        $query = "INSERT INTO eye_treatments (consultation_id, medication_name, dosage, frequency, duration) 
                  VALUES (:consultation_id, :medication_name, :dosage, :frequency, :duration)";
        $stmt = $db->prepare($query);
        
        foreach ($data['treatment'] as $treatment) {
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':medication_name', $treatment['medication']);
            $stmt->bindParam(':dosage', $treatment['dosage']);
            $stmt->bindParam(':frequency', $treatment['frequency']);
            $stmt->bindParam(':duration', $treatment['duration']);
            $stmt->execute();
        }
    }
    
    // Update discharge
    $query = "DELETE FROM eye_discharge WHERE consultation_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $consultationId);
    $stmt->execute();
    
    if (isset($data['discharge']) && !empty($data['discharge']['patientStatus'])) {
        $query = "INSERT INTO eye_discharge (consultation_id, patient_status, discharge_date, discharging_service, clinical_summary) 
                  VALUES (:consultation_id, :patient_status, :discharge_date, :discharging_service, :clinical_summary)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':consultation_id', $consultationId);
        $stmt->bindParam(':patient_status', $data['discharge']['patientStatus']);
        $stmt->bindParam(':discharge_date', $data['discharge']['dischargeDate']);
        $stmt->bindParam(':discharging_service', $data['discharge']['dischargingService']);
        $stmt->bindParam(':clinical_summary', $data['discharge']['clinicalSummary']);
        $stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    sendResponse(true, ['consultationId' => $consultationId], 'Consultation updated successfully');
    
} catch(PDOException $e) {
    $db->rollBack();
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>