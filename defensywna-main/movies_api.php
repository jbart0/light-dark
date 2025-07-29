<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once('db.php');

try {
    // Pobierz wszystkie filmy z bazy danych wraz z kategoriami
    $stmt = $conn->prepare("
        SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
               f.miniaturka_url, f.dostepne_jakosci, k.kategoria,
               GROUP_CONCAT(CONCAT(a.imie, ' ', a.nazwisko) SEPARATOR ', ') as autorzy
        FROM Filmy f
        LEFT JOIN Kategorie k ON f.kategoria = k.id
        LEFT JOIN Autorzy a ON f.id = a.film_id
        GROUP BY f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
                 f.miniaturka_url, f.dostepne_jakosci, k.kategoria
        ORDER BY f.id DESC
    ");
    
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
                // Placeholder URLs - w przyszłości można dodać rzeczywiste ścieżki do plików wideo
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
            'videoUrl' => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4", // Placeholder
            'videoSources' => $qualities,
            'duration' => $durationFormatted,
            'categories' => $categories,
            'authors' => $row['autorzy'] ?: 'Nieznany'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'movies' => $movies,
        'count' => count($movies)
    ]);
    
} catch (Exception $e) {
    error_log("Movies API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'movies' => []
    ]);
}
?>