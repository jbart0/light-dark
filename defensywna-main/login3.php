<?php
header('Content-Type: application/json');
session_start();

// Połączenie z bazą
$conn = new mysqli('192.168.1.1', 'f22305', 'ItrjzveHI8', 'db_f22305');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd połączenia z bazą']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Brakuje e-maila lub hasła']);
    exit;
}

// Pobierz użytkownika
$stmt = $conn->prepare("SELECT id, login, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        
        // Create session cookie
        $sessionData = [
            'id' => $user['id'],
            'login' => $user['login'],
            'email' => $email
        ];
        
        setcookie('wfo_session', json_encode($sessionData), time() + (7 * 24 * 60 * 60), '/');
        
        echo json_encode([
            'user' => $sessionData,
            'token' => session_id()
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Nieprawidłowe hasło']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Użytkownik nie istnieje']);
}
?>
