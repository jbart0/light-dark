<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('db.php');

try {
    // Pobierz wszystkie filmy z bazy danych wraz z kategoriami
    $stmt = $conn->prepare("
        SELECT f.id, f.tytul, f.opis, f.ocena_sr, f.czas_trwania, f.rok_produkcji, 
               f.miniaturka_url, f.dostepne_jakosci, k.kategoria
        FROM Filmy f
        LEFT JOIN Kategorie k ON f.kategoria = k.id
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
            $hours = $time->format('G');
            $minutes = $time->format('i');
            $durationFormatted = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
        } else {
            $durationFormatted = 'N/A';
        }
        
        // Parsuj dostępne jakości
        $qualities = [];
        if ($row['dostepne_jakosci']) {
            $qualityList = explode(',', $row['dostepne_jakosci']);
            foreach ($qualityList as $quality) {
                $quality = trim($quality);
                $qualities[$quality] = "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4"; // Placeholder URL
            }
        }
        
        $movies[] = [
            'id' => $row['id'],
            'title' => $row['tytul'],
            'imageUrl' => $row['miniaturka_url'] ?: 'https://via.placeholder.com/300x450?text=Brak+obrazu',
            'year' => $row['rok_produkcji'],
            'rating' => number_format($row['ocena_sr'], 1) . '/5',
            'genre' => $row['kategoria'] ?: 'Nieznana',
            'description' => $row['opis'],
            'dateAdded' => date('Y-m-d'),
            'videoUrl' => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4", // Placeholder
            'videoSources' => $qualities,
            'duration' => $durationFormatted,
            'categories' => [$row['kategoria'] ?: 'Nieznana']
        ];
    }
    
    echo json_encode(['movies' => $movies]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>