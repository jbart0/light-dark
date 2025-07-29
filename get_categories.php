<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('db.php');

try {
    // Pobierz wszystkie kategorie z liczbą filmów w każdej kategorii
    $stmt = $conn->prepare("
        SELECT k.id, k.kategoria, COUNT(f.id) as film_count
        FROM Kategorie k
        LEFT JOIN Filmy f ON k.id = f.kategoria
        GROUP BY k.id, k.kategoria
        HAVING film_count > 0
        ORDER BY k.kategoria ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['kategoria'],
            'count' => (int)$row['film_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);
    
} catch (Exception $e) {
    error_log("Categories API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'categories' => []
    ]);
}
?>