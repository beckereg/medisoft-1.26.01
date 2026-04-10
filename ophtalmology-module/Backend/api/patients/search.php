<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$searchTerm = isset($_GET['q']) ? $_GET['q'] : '';

if (empty($searchTerm)) {
    sendResponse(false, null, 'Search term is required', 400);
}

try {
    $query = "SELECT p.*, 
              d.name as district_name, 
              s.name as sector_name,
              c.name as cell_name,
              v.name as village_name,
              i.name as insurance_name
              FROM patients p
              LEFT JOIN district d ON p.district = d.id
              LEFT JOIN sector s ON p.sector = s.id
              LEFT JOIN cellule c ON p.cellule = c.id
              LEFT JOIN village v ON p.village = v.id
              LEFT JOIN insurances i ON p.insurance_id = i.id
              WHERE p.patient_id LIKE :search 
              OR p.nida LIKE :search 
              OR p.beneficiary LIKE :search 
              OR p.family_name LIKE :search 
              OR p.given_name LIKE :search
              OR p.num_affiliation LIKE :search
              OR p.tel LIKE :search
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $searchParam = "%{$searchTerm}%";
    $stmt->bindParam(':search', $searchParam);
    $stmt->execute();
    
    $patients = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $patients[] = [
            'id' => $row['patient_id'],
            'nida' => $row['nida'],
            'name' => $row['beneficiary'],
            'familyName' => $row['family_name'],
            'givenName' => $row['given_name'],
            'phone' => $row['tel'],
            'insurance' => $row['insurance_name'],
            'numAffiliation' => $row['num_affiliation'],
            'district' => $row['district_name'],
            'sector' => $row['sector_name']
        ];
    }
    
    sendResponse(true, $patients, count($patients) . ' patients found');
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
?>