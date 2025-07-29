<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Log for debugging
error_log("check_license.php: Request received");

// Pobierz userid z ciasteczka
$sessionCookie = $_COOKIE['wfo_session'] ?? '';

if (!$sessionCookie) {
    error_log("check_license.php: No session cookie found");
    http_response_code(400);
    echo json_encode(['error' => 'Brakuje danych sesji', 'hasLicense' => false]);
    exit;
}

// dekodowanie URL
$sessionCookie = urldecode($sessionCookie);

// Parsowanie JSON
$sessionData = json_decode($sessionCookie, true);

// Sprawdzenie czy dekodowanie się powiodło i czy istnieje pole 'id'
if ($sessionData === null || !isset($sessionData['id'])) {
    error_log("check_license.php: Invalid session data");
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowe dane sesji', 'hasLicense' => false]);
    exit;
}

// Pobierz userid
$userId = $sessionData['id'];

// Pobierz videoid z parametru GET - sprawdź oba możliwe nazwy
$videoId = $_GET['id'] ?? $_GET['videoid'] ?? $_GET['movieId'] ?? '';

// Log the received user ID and video ID
error_log("check_license.php: Received userid = " . $userId . ", videoid = " . $videoId);

if (!$userId || !$videoId) {
    error_log("check_license.php: Missing user ID or video ID");
    http_response_code(400);
    echo json_encode(['error' => 'Brakuje ID użytkownika lub filmu', 'hasLicense' => false]);
    exit;
}

// Sprawdź licencję w nowej strukturze bazy danych
$hasLicense = false;

try {
    $conn = new mysqli('192.168.1.1', 'f22305', 'ItrjzveHI8', 'db_f22305');

    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        
        // Sprawdź czy użytkownik ma aktywną subskrypcję dla tego filmu
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM Subskrybcje s
            WHERE s.id_uzytkownika = ? 
            AND s.id_filmu = ? 
            AND (s.data_wygasniecia IS NULL OR s.data_wygasniecia >= CURDATE())
        ");
        
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $videoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $hasLicense = true;
            }
            
            $stmt->close();
        }
        $conn->close();
    }
} catch (Exception $e) {
    error_log("check_license.php: Database connection failed: " . $e->getMessage());
}

// Log the result
error_log("check_license.php: License check result for user $userId, movie $videoId: " . ($hasLicense ? 'true' : 'false'));

echo json_encode([
    'hasLicense' => $hasLicense,
    'userId' => $userId,
    'movieId' => $videoId,
    'message' => $hasLicense ? 'Licencja aktywna' : 'Licencja wymagana'
]);
?>