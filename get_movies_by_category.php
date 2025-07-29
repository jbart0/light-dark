<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('db.php');

$categoryId = $_GET['category_id'] ?? '';
$categoryName = $_GET['category_name'] ?? '';

if (!$categoryId && !$categoryName) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Category ID or name is required',
        'movies' => []
    ]);
    exit;
}

try {
    // Przygotuj zapytanie w zależności od tego, czy mamy ID czy nazwę kategorii
    if ($categoryId) {
        $stmt = $conn->prepare("
            SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                   f.miniaturka_url, f.dostepne_jakosci, k.kategoria,
                   GROUP_CONCAT(CONCAT(a.imie, ' ', a.nazwisko) SEPARATOR ', ') as autorzy
            FROM Filmy f
            LEFT JOIN Kategorie k ON f.kategoria = k.id
            LEFT JOIN Autorzy a ON f.id = a.film_id
            WHERE f.kategoria = ?
            GROUP BY f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                     f.miniaturka_url, f.dostepne_jakosci, k.kategoria
            ORDER BY f.id DESC
        ");
        $stmt->bind_param("i", $categoryId);
    } else {
        $stmt = $conn->prepare("
            SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                   f.miniaturka_url, f.dostepne_jakosci, k.kategoria,
                   GROUP_CONCAT(CONCAT(a.imie, ' ', a.nazwisko) SEPARATOR ', ') as autorzy
            FROM Filmy f
            LEFT JOIN Kategorie k ON f.kategoria = k.id
            LEFT JOIN Autorzy a ON f.id = a.film_id
            WHERE k.kategoria = ?
            GROUP BY f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                     f.miniaturka_url, f.dostepne_jakosci, k.kategoria
            ORDER BY f.id DESC
        ");
        $stmt->bind_param("s", $categoryName);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $movies = [];
    while ($row = $result->fetch_assoc()) {
        // Konwertuj czas trwania z TIME na format "Xh Ym"
        $duration = $row['czas_trwania'];
        if ($duration) {
            $time = DateTime::createFromFormat('H:i:s', $duration);
            if ($time) {
                $hours = $time->format('G');
                $minutes = $time->format('i');
                $durationFormatted = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
            } else {
                $durationFormatted = 'N/A';
            }
        } else {
            $durationFormatted = 'N/A';
        }
        
        // Parsuj dostępne jakości
        $qualities = [];
        if ($row['dostepne_jakosci']) {
            $qualityList = explode(',', $row['dostepne_jakosci']);
            foreach ($qualityList as $quality) {
                $quality = trim($quality);
                $qualities[$quality] = "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4";
            }
        }
        
        // Jeśli brak jakości, dodaj domyślne
        if (empty($qualities)) {
            $qualities = [
                "1080p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "720p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "480p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "360p" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4"
            ];
        }
        
        // Określ kategorię i gatunki
        $category = $row['kategoria'] ?: 'Nieznana';
        $categories = [$category];
        
        // Mapowanie kategorii na gatunki dla kompatybilności
        $genreMapping = [
            'Dramat' => 'Dramat',
            'Fantasy' => 'Fantasy, Przygodowy',
            'Kryminał' => 'Kryminał, Thriller',
            'Akcja' => 'Akcja, Przygodowy',
            'Komedia' => 'Komedia',
            'Horror' => 'Horror, Thriller',
            'Sci-Fi' => 'Sci-Fi, Akcja',
            'Romans' => 'Romans, Dramat'
        ];
        
        $genre = $genreMapping[$category] ?? $category;
        
        $movies[] = [
            'id' => (int)$row['id'],
            'title' => $row['tytul'],
            'imageUrl' => $row['miniaturka_url'] ?: 'https://via.placeholder.com/300x450?text=Brak+obrazu',
            'year' => (string)$row['rok_produkcji'],
            'rating' => number_format((float)$row['ocena_sr'], 1) . '/5',
            'genre' => $genre,
            'description' => $row['opis'] ?: 'Brak opisu filmu.',
            'dateAdded' => date('Y-m-d'),
            'videoUrl' => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
            'videoSources' => $qualities,
            'duration' => $durationFormatted,
            'categories' => $categories,
            'authors' => $row['autorzy'] ?: 'Nieznany'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'movies' => $movies,
        'count' => count($movies),
        'category' => $categoryName ?: $categoryId
    ]);
    
} catch (Exception $e) {
    error_log("Movies by Category API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'movies' => []
    ]);
}
?>