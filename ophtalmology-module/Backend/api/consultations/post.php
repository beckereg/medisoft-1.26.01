<?php
// Set execution time and memory limits
set_time_limit(30);
ini_set('memory_limit', '128M');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

// Get and decode input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON received. Error: ' . json_last_error_msg()]);
    exit();
}

if (!isset($data['patientId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

if (!isset($data['consultation']) || !is_array($data['consultation'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Consultation data is required']);
    exit();
}

try {
    // Database connection with error handling
    $pdo = new PDO(
        "mysql:host=localhost;dbname=remera1;charset=utf8",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create consultation table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS eye_consultations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        consultation_type VARCHAR(100),
        consultation_notes TEXT,
        consultation_date DATE,
        status VARCHAR(20) DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient_id (patient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    
    $stmt = $pdo->prepare("INSERT INTO eye_consultations 
        (patient_id, consultation_type, consultation_notes, consultation_date, status) 
        VALUES (:pid, :type, :notes, :date, 'completed')");
    
    $patientId = isset($data['patientId']) ? (int)$data['patientId'] : 0;
    $type = $data['consultation']['type'] ?? '';
    $notes = $data['consultation']['notes'] ?? '';
    $date = $data['consultation']['date'] ?? date('Y-m-d');
    
    $stmt->bindParam(':pid', $patientId, PDO::PARAM_INT);
    $stmt->bindParam(':type', $type, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':date', $date, PDO::PARAM_STR);
    
    $result = $stmt->execute();
    
    if ($result) {
        $consultationId = $pdo->lastInsertId();
        
        // Insert symptoms
        if (isset($data['symptoms']) && is_array($data['symptoms'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_symptoms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                symptom_name VARCHAR(100),
                present TINYINT(1) DEFAULT 0,
                laterality VARCHAR(10),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_symptoms (consultation_id, symptom_name, present, laterality) VALUES (?, ?, ?, ?)");
            foreach ($data['symptoms'] as $symptom) {
                $symptomName = $symptom['name'] ?? '';
                $present = isset($symptom['present']) ? ($symptom['present'] ? 1 : 0) : 0;
                $laterality = $symptom['laterality'] ?? '';
                $stmt->execute([$consultationId, $symptomName, $present, $laterality]);
            }
        }
        
        // Insert assessments
        if (isset($data['assessments']) && is_array($data['assessments'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_assessments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                assessment_name VARCHAR(100),
                right_eye_value VARCHAR(50),
                left_eye_value VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_assessments (consultation_id, assessment_name, right_eye_value, left_eye_value) VALUES (?, ?, ?, ?)");
            foreach ($data['assessments'] as $assessment) {
                $assessmentName = $assessment['name'] ?? '';
                $rightEye = $assessment['rightEye'] ?? '';
                $leftEye = $assessment['leftEye'] ?? '';
                $stmt->execute([$consultationId, $assessmentName, $rightEye, $leftEye]);
            }
        }
        
        // Insert diagnosis
        if (isset($data['diagnosis']) && is_array($data['diagnosis']) && !empty($data['diagnosis']['primary'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_diagnosis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                primary_diagnosis VARCHAR(200),
                diagnosis_code VARCHAR(20),
                clinical_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_diagnosis (consultation_id, primary_diagnosis, diagnosis_code, clinical_notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $consultationId,
                $data['diagnosis']['primary'] ?? '',
                $data['diagnosis']['code'] ?? '',
                $data['diagnosis']['notes'] ?? ''
            ]);
        }
        
        // Insert treatments
        if (isset($data['treatment']) && is_array($data['treatment'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_treatments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                medication_name VARCHAR(200),
                dosage VARCHAR(100),
                frequency VARCHAR(50),
                duration VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_treatments (consultation_id, medication_name, dosage, frequency, duration) VALUES (?, ?, ?, ?, ?)");
            foreach ($data['treatment'] as $treatment) {
                $stmt->execute([
                    $consultationId,
                    $treatment['medication'] ?? '',
                    $treatment['dosage'] ?? '',
                    $treatment['frequency'] ?? '',
                    $treatment['duration'] ?? ''
                ]);
            }
        }
        
        // Insert consumables
        if (isset($data['consumables']) && is_array($data['consumables'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_consumables (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                consumable_name VARCHAR(200),
                quantity VARCHAR(50),
                unit VARCHAR(50),
                date_issued DATE DEFAULT CURRENT_DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_consumables (consultation_id, consumable_name, quantity, unit) VALUES (?, ?, ?, ?)");
            foreach ($data['consumables'] as $consumable) {
                $stmt->execute([
                    $consultationId,
                    $consumable['name'] ?? '',
                    $consumable['quantity'] ?? '',
                    $consumable['unit'] ?? ''
                ]);
            }
        }
        
        // Insert discharge
        if (isset($data['discharge']) && is_array($data['discharge'])) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS eye_discharge (
                id INT AUTO_INCREMENT PRIMARY KEY,
                consultation_id INT NOT NULL,
                patient_status VARCHAR(50),
                discharge_date DATE,
                discharging_service VARCHAR(100),
                clinical_summary TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            
            $stmt = $pdo->prepare("INSERT INTO eye_discharge (consultation_id, patient_status, discharge_date, discharging_service, clinical_summary) VALUES (?, ?, ?, ?, ?)");
            $dischargeDate = !empty($data['discharge']['dischargeDate']) ? $data['discharge']['dischargeDate'] : null;
            $stmt->execute([
                $consultationId,
                $data['discharge']['patientStatus'] ?? '',
                $dischargeDate,
                $data['discharge']['dischargingService'] ?? '',
                $data['discharge']['clinicalSummary'] ?? ''
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => ['consultationId' => (int)$consultationId],
            'message' => 'Consultation saved successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to insert consultation']);
    }
    
} catch(PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch(Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
