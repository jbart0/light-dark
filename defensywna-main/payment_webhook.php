<?php
// Ustaw nagłówek odpowiedzi na text/plain
header('Content-Type: text/plain; charset=utf-8');

// Twój sekret i algorytm podpisu
$secret = 'Tp5Ji6Sw3Md8Yh3P@9I@9Rz2Mo8Fk5Of';
$algo = 'sha256';

// Plik logu do debugowania
$logFile = __DIR__ . '/debug_notify.log';

// Połączenie z bazą danych
$conn = new mysqli("192.168.1.1", "f22305", "ItrjzveHI8", "db_f22305");

if ($conn->connect_error) {
    logDebug("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo "Database error";
    exit;
}

$conn->set_charset("utf8");

// Funkcja do logowania
function logDebug($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// 1. Odbierz surowe dane z wejścia
$raw = file_get_contents('php://input');
logDebug("Webhook called");
logDebug("Raw POST: " . $raw);

// 2. Dekoduj JSON
$data = json_decode($raw, true);
if (!$data) {
    logDebug("Błędne dane JSON: " . json_last_error_msg());
    http_response_code(400);
    echo "Błędne dane";
    exit;
}
logDebug("Decoded JSON: " . print_r($data, true));

// 3. Formatuj kwotę z dwoma miejscami po przecinku
$amountPaidFormatted = number_format((float)$data['amountPaid'], 2, '.', '');

// 4. Buduj string do podpisu
$signString = sprintf(
    "%s|%s|%s|%s|%s|%d|%s|%d",
    $secret,
    $data['transactionId'],
    $data['control'],
    $data['email'],
    $amountPaidFormatted,
    $data['notificationAttempt'],
    $data['paymentType'],
    $data['apiVersion']
);
logDebug("Sign string: " . $signString);

// 5. Oblicz podpis
$signatureCalc = hash($algo, $signString);
logDebug("Calculated signature: " . $signatureCalc);
logDebug("Received signature: " . $data['signature']);

// 6. Sprawdź podpis
if (!hash_equals($signatureCalc, $data['signature'])) {
    logDebug("Nieprawidłowy podpis!");
    logDebug("Calculated signature: " . $signatureCalc);
    logDebug("Received signature: " . $data['signature']);
    http_response_code(403);
    echo "Nieprawidłowy podpis";
    exit;
}

// 7. Przetwórz dane kontrolne (format: user_id-movie_id)
$controlParts = explode('-', $data['control']);
if (count($controlParts) !== 2) {
    logDebug("Nieprawidłowy format danych kontrolnych: " . $data['control']);
    http_response_code(400);
    echo "Invalid control data";
    exit;
}

$userId = intval($controlParts[0]);
$movieId = intval($controlParts[1]);

// 8. Sprawdź czy film istnieje i pobierz typ licencji
$stmt = $conn->prepare("SELECT typ_licencji FROM Filmy WHERE id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$result = $stmt->get_result();

if (!$movie = $result->fetch_assoc()) {
    logDebug("Movie not found: " . $movieId);
    http_response_code(400);
    echo "Movie not found";
    exit;
}

$licenseType = $movie['typ_licencji'];

// 9. Dodaj płatność do tabeli Platnosci
$stmt = $conn->prepare("INSERT INTO Platnosci (id_filmu, id_uzytkownika, id_transakcji, typ_platnosci) VALUES (?, ?, ?, ?)");
$paymentType = $data['paymentType'] ?? 'online';
$stmt->bind_param("iiss", $movieId, $userId, $data['transactionId'], $paymentType);

if (!$stmt->execute()) {
    logDebug("Error adding payment: " . $conn->error);
    http_response_code(500);
    echo "Database error";
    exit;
}

$paymentId = $conn->insert_id;

// 10. Dodaj subskrypcję (licencję) do tabeli Subskrybcje
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+1 year')); // Domyślnie rok dostępu

$stmt = $conn->prepare("INSERT INTO Subskrybcje (id_uzytkownika, id_filmu, id_platnosci, data_rozpoczecia, data_wygasniecia) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiss", $userId, $movieId, $paymentId, $startDate, $endDate);

try {
    $stmt->execute();
    logDebug("Subscription added successfully for user $userId, movie $movieId");
} catch (Exception $e) {
    if ($conn->errno === 1062) { // Duplicate entry error
        logDebug("Subscription already exists for user $userId, movie $movieId");
    } else {
        logDebug("Error adding subscription: " . $e->getMessage() . " - Error code: " . $conn->errno);
        http_response_code(500);
        echo "Database error";
        exit;
    }
}

// 11. Odpowiedz bramce sukcesem
http_response_code(200);
echo "OK";
logDebug("Response OK sent");
?>