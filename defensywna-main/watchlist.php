<?php
session_start();
header('Content-Type: application/json');

require_once('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Pobierz filmy z watchlisty użytkownika wraz z danymi filmu
        $stmt = $conn->prepare("
            SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                   f.miniaturka_url, k.kategoria, w.id_uzytkownika
            FROM Watchlist w 
            JOIN Filmy f ON w.id_filmu = f.id 
            LEFT JOIN Kategorie k ON f.kategoria = k.id
            WHERE w.id_uzytkownika = ? 
            ORDER BY f.id DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $watchlist = [];
        while ($row = $result->fetch_assoc()) {
            // Konwertuj czas trwania z TIME na format "Xh Ym"
            $duration = $row['czas_trwania'];
            if ($duration) {
                $time = DateTime::createFromFormat('H:i:s', $duration);
                $hours = $time->format('G');
                $minutes = $time->format('i');
                $durationFormatted = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
            } else {
                $durationFormatted = 'N/A';
            }
            
            $watchlist[] = [
                'movie_title' => $row['tytul'],
                'added_at' => date('Y-m-d H:i:s') // Placeholder, można dodać pole daty do tabeli
            ];
        }
        
        echo json_encode(['watchlist' => $watchlist]);
        break;

    case 'add':
        $data = json_decode(file_get_contents('php://input'), true);
        $movieTitle = $data['movieTitle'] ?? '';

        if (!$movieTitle) {
            http_response_code(400);
            echo json_encode(['error' => 'Movie title is required']);
            exit;
        }

        // Znajdź ID filmu na podstawie tytułu
        $stmt = $conn->prepare("SELECT id FROM Filmy WHERE tytul = ?");
        $stmt->bind_param("s", $movieTitle);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($movie = $result->fetch_assoc()) {
            $movieId = $movie['id'];
            
            // Dodaj do watchlisty
            $stmt = $conn->prepare("INSERT INTO Watchlist (id_uzytkownika, id_filmu) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $movieId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                if ($conn->errno === 1062) { // Duplicate entry
                    http_response_code(409);
                    echo json_encode(['error' => 'Movie already in watchlist']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to add movie to watchlist']);
                }
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Movie not found']);
        }
        break;

    case 'remove':
        $data = json_decode(file_get_contents('php://input'), true);
        $movieTitle = $data['movieTitle'] ?? '';

        if (!$movieTitle) {
            http_response_code(400);
            echo json_encode(['error' => 'Movie title is required']);
            exit;
        }

        // Znajdź ID filmu na podstawie tytułu i usuń z watchlisty
        $stmt = $conn->prepare("
            DELETE w FROM Watchlist w 
            JOIN Filmy f ON w.id_filmu = f.id 
            WHERE w.id_uzytkownika = ? AND f.tytul = ?
        ");
        $stmt->bind_param("is", $userId, $movieTitle);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove movie from watchlist']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>