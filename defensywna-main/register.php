<?php
// Disable error output to response
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
session_start();
require_once('db.php');

$login = $_POST['login'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$recaptcha_response = $_POST['recaptcha_response'] ?? '';

if (!$login || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Brakuje wymaganych danych: login, email lub hasło']);
    exit;
}

if (!$recaptcha_response) {
    http_response_code(400);
    echo json_encode(['error' => 'Brakuje wymaganych danych: recaptcha']);
    exit;
}

// Validate password
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Hasło musi zawierać minimum 8 znaków, w tym jedną dużą literę, jedną małą literę oraz jedną cyfrę']);
    exit;
}

// Validate reCAPTCHA directly
$secretKey = '6Lejx1UrAAAAAGIUVJUiWyO6s-sBK3RwZra-EoeF';
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret' => $secretKey,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

// Log reCAPTCHA request for debugging
error_log('reCAPTCHA validation request: ' . json_encode($recaptcha_data));

// Use cURL for better error handling
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $recaptcha_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($recaptcha_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $recaptcha_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        error_log('cURL error in reCAPTCHA validation: ' . $curl_error);
        http_response_code(500);
        echo json_encode(['error' => 'reCAPTCHA service error: ' . $curl_error]);
        exit;
    }
} else {
    // Fallback to file_get_contents
    $recaptcha_options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptcha_data),
            'timeout' => 10
        ]
    ];

    $recaptcha_context = stream_context_create($recaptcha_options);
    $recaptcha_result = @file_get_contents($recaptcha_url, false, $recaptcha_context);
}

// Log the response for debugging
error_log('reCAPTCHA validation response: ' . $recaptcha_result);

if ($recaptcha_result === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Nie udało się połączyć z usługą reCAPTCHA']);
    exit;
}

$recaptcha_response_data = json_decode($recaptcha_result, true);

if (!$recaptcha_response_data) {
    error_log('Invalid JSON response from reCAPTCHA: ' . $recaptcha_result);
    http_response_code(500);
    echo json_encode(['error' => 'Nieprawidłowa odpowiedź od usługi reCAPTCHA']);
    exit;
}

if (!$recaptcha_response_data['success']) {
    $error_codes = isset($recaptcha_response_data['error-codes']) ? implode(', ', $recaptcha_response_data['error-codes']) : 'Unknown error';
    error_log('reCAPTCHA validation failed with error codes: ' . $error_codes);

    // Provide more specific error messages
    if (in_array('invalid-input-secret', $recaptcha_response_data['error-codes'] ?? [])) {
        echo json_encode(['error' => 'Błąd konfiguracji reCAPTCHA - nieprawidłowy klucz tajny']);
    } elseif (in_array('invalid-input-response', $recaptcha_response_data['error-codes'] ?? [])) {
        echo json_encode(['error' => 'Nieprawidłowa odpowiedź reCAPTCHA - spróbuj ponownie']);
    } elseif (in_array('timeout-or-duplicate', $recaptcha_response_data['error-codes'] ?? [])) {
        echo json_encode(['error' => 'reCAPTCHA wygasła lub jest zduplikowana - spróbuj ponownie']);
    } else {
        echo json_encode(['error' => 'Weryfikacja reCAPTCHA nie powiodła się: ' . $error_codes]);
    }

    http_response_code(400);
    exit;
}

try {
    // Sprawdź, czy użytkownik już istnieje (email lub login)
    $stmt = $conn->prepare("SELECT id FROM Uzytkownicy WHERE e_mail = ? OR login = ?");
    if (!$stmt) {
        throw new Exception('Błąd SQL przy SELECT: ' . $conn->error);
    }

    $stmt->bind_param("ss", $email, $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'E-mail lub login już istnieje']);
        exit;
    }

    // Dodaj nowego użytkownika do tabeli Uzytkownicy
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Uzytkownicy (login, e_mail, haslo) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Błąd SQL przy INSERT: ' . $conn->error);
    }

    $stmt->bind_param("sss", $login, $email, $hashed);

    if ($stmt->execute()) {
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['login'] = $login;
        
        echo json_encode([
            'user' => ['login' => $login, 'email' => $email],
            'token' => session_id()
        ]);
    } else {
        throw new Exception('Nie udało się dodać użytkownika');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>