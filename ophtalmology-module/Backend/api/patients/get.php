<?php
// Enable error reporting
error_reporting(E_ALL);

ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$patientId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$patientId) {
    echo json_encode([
        'success' => false,
        'message' => 'Patient ID is required'
    ]);
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=remera1", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Main patient query with correct joins using *_client tables
    $query = "SELECT p.*,
              dc.district as district_name,
              sc.sector as sector_name,
              cc.cell as cell_name,
              v.village as village_name,
              i.insurance as insurance_name,
              pc.province as province_name
              FROM patients p
              LEFT JOIN districts_client dc ON p.district = dc.district_id
              LEFT JOIN sectors_client sc ON p.sector = sc.sector_id
              LEFT JOIN cells_client cc ON p.cellule = cc.cell_id
              LEFT JOIN villages v ON p.village = v.village_id
              LEFT JOIN insurances i ON p.insurance_id = i.id
              LEFT JOIN provinces pc ON dc.province_id = pc.province_id
              WHERE p.patient_id = :id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $patientId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        // Format gender
        $gender = '';
        if ($patient['sex'] == 1) {
            $gender = 'Male';
        } elseif ($patient['sex'] == 2) {
            $gender = 'Female';
        }

        $patientData = [
            'id' => $patient['patient_id'],
            'name' => $patient['beneficiary'],
            'fullName' => $patient['beneficiary'],
            'firstName' => $patient['given_name'],
            'lastName' => $patient['family_name'],
            'phone' => $patient['tel'],
            'age' => $patient['age'],
            'gender' => $gender,
            'nida' => $patient['nida'],
            'insurance' => $patient['insurance_name'] ?? '',
            'insuranceCode' => $patient['insurance_code'],
            'insuranceId' => $patient['insurance_id'],
            'insuranceName' => $patient['insurance_name'] ?? '',
            'province' => $patient['province_name'] ?? '',
            'district' => $patient['district_name'] ?? '',
            'sector' => $patient['sector_name'] ?? '',
            'cell' => $patient['cell_name'] ?? '',
            'village' => $patient['village_name'] ?? '',
            'catchmentArea' => ''
        ];

        echo json_encode([
            'success' => true,
            'data' => $patientData,
            'message' => 'Patient found successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Patient not found'
        ]);
    }

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>