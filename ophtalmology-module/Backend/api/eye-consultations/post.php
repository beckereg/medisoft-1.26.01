<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    sendResponse(false, null, 'Invalid data', 400);
}

try {
    $db->beginTransaction();
    
    // Insert into eye_consultations
    $query = "INSERT INTO eye_consultations (patient_id, consultation_type, consultation_notes, consultation_date, status) 
              VALUES (:patient_id, :consultation_type, :consultation_notes, :consultation_date, 'completed')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':patient_id', $data['patientId']);
    $stmt->bindParam(':consultation_type', $data['consultation']['type']);
    $stmt->bindParam(':consultation_notes', $data['consultation']['notes']);
    $stmt->bindParam(':consultation_date', $data['consultation']['date']);
    $stmt->execute();
    
    $consultationId = $db->lastInsertId();
    
    // Insert symptoms
    if (isset($data['symptoms']) && !empty($data['symptoms'])) {
        $query = "INSERT INTO eye_symptoms (consultation_id, symptom_name, present, laterality) 
                  VALUES (:consultation_id, :symptom_name, :present, :laterality)";
        $stmt = $db->prepare($query);
        
        foreach ($data['symptoms'] as $symptom) {
            $symptomName = $symptom['name'] ?? '';
            $present = isset($symptom['present']) ? ($symptom['present'] ? 1 : 0) : 0;
            $laterality = $symptom['laterality'] ?? '';
            
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':symptom_name', $symptomName);
            $stmt->bindParam(':present', $present);
            $stmt->bindParam(':laterality', $laterality);
            $stmt->execute();
        }
    }
    
    // Insert assessments
    if (isset($data['assessments']) && !empty($data['assessments'])) {
        $query = "INSERT INTO eye_assessments (consultation_id, assessment_name, right_eye_value, left_eye_value) 
                  VALUES (:consultation_id, :assessment_name, :right_eye_value, :left_eye_value)";
        $stmt = $db->prepare($query);
        
        foreach ($data['assessments'] as $assessment) {
            $assessmentName = $assessment['name'] ?? '';
            $rightEye = $assessment['rightEye'] ?? '';
            $leftEye = $assessment['leftEye'] ?? '';
            
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':assessment_name', $assessmentName);
            $stmt->bindParam(':right_eye_value', $rightEye);
            $stmt->bindParam(':left_eye_value', $leftEye);
            $stmt->execute();
        }
    }
    
    // Insert diagnosis
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
    
    // Insert treatments
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
    
    // Insert consumables
    if (isset($data['consumables']) && !empty($data['consumables'])) {
        $query = "INSERT INTO eye_consumables (consultation_id, consumable_name, quantity, unit, date_issued) 
                  VALUES (:consultation_id, :consumable_name, :quantity, :unit, :date_issued)";
        $stmt = $db->prepare($query);
        
        foreach ($data['consumables'] as $consumable) {
            $stmt->bindParam(':consultation_id', $consultationId);
            $stmt->bindParam(':consumable_name', $consumable['name']);
            $stmt->bindParam(':quantity', $consumable['quantity']);
            $stmt->bindParam(':unit', $consumable['unit']);
            $stmt->bindParam(':date_issued', $consumable['dateIssued']);
            $stmt->execute();
        }
    }
    
    // Insert discharge
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
    
    $db->commit();
    
    sendResponse(true, ['consultationId' => $consultationId], 'Eye consultation saved successfully', 201);
    
} catch(PDOException $e) {
    $db->rollBack();
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>