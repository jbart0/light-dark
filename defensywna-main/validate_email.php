<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$email = $_POST['email'] ?? '';

if (!$email) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Email jest wymagany']);
    exit;
}

// Basic email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['valid' => false, 'error' => 'Nieprawidłowy format adresu email']);
    exit;
}

// Extract domain from email
$domain = substr(strrchr($email, "@"), 1);

// Check if domain exists and has MX record
if (!checkdnsrr($domain, 'MX')) {
    echo json_encode(['valid' => false, 'error' => 'Domena email nie istnieje lub nie może odbierać wiadomości']);
    exit;
}

// List of disposable email domains - expanded list
$disposableDomains = [
    'tempmail.com', 'temp-mail.org', 'guerrillamail.com', 'throwawaymail.com',
    'yopmail.com', 'mailinator.com', '10minutemail.com', 'trashmail.com',
    'sharklasers.com', 'guerrillamail.info', 'grr.la', 'maildrop.cc',
    'getairmail.com', 'getnada.com', 'emailondeck.com', 'tempmail.net',
    'dispostable.com', 'tempmailaddress.com', 'emailfake.com', 'fakeinbox.com'
];

// Check for disposable email domains
if (in_array(strtolower($domain), $disposableDomains)) {
    echo json_encode(['valid' => false, 'error' => 'Tymczasowe adresy email nie są dozwolone']);
    exit;
}

// Additional validation for common email providers
$commonProviders = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'wp.pl', 'o2.pl', 'interia.pl', 'onet.pl'];
$isCommonProvider = in_array(strtolower($domain), $commonProviders);

// If domain is not a common provider, do additional checks
if (!$isCommonProvider) {
    // Check if domain has a valid A record (web server)
    if (!checkdnsrr($domain, 'A')) {
        echo json_encode(['valid' => false, 'error' => 'Domena wydaje się być nieprawidłowa']);
        exit;
    }
}

// If all checks pass
echo json_encode(['valid' => true, 'error' => '']);
?>
