<?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in and session contains user ID
    if (!isset($_COOKIE['wfo_session'])) {
        // If user is not logged in, redirect to login page with movieId as returnUrl
        $movieId = isset($_POST['movieId']) ? intval($_POST['movieId']) : 0;
        $loginUrl = 'Logowanie.html?returnUrl=' . urlencode($_SERVER['REQUEST_URI'] . ($movieId ? '?movieId=' . $movieId : ''));
        header("Location: $loginUrl");
        exit();
    }

    // Get user ID from wfo_session cookie
    $sessionData = json_decode($_COOKIE['wfo_session'], true);
    if (!isset($sessionData['id'])) {
        // If user ID is not in session, redirect to login page
        $movieId = isset($_POST['movieId']) ? intval($_POST['movieId']) : 0;
        $loginUrl = 'Logowanie.html?returnUrl=' . urlencode($_SERVER['REQUEST_URI'] . ($movieId ? '?movieId=' . $movieId : ''));
        header("Location: $loginUrl");
        exit();
    }

    if (!isset($_POST['movieId'])) {
        die('Brak ID filmu');
    }

    $movieId = intval($_POST['movieId']);
    $userId = $sessionData['id'];

    $shopId             = intval(2139);
    $description        = strval('WFO - Zakup Filmu ' . $movieId);
    $hash               = 'Tp5Ji6Sw3Md8Yh3P@9I@9Rz2Mo8Fk5Of';
    $notifyURL          = strval('https://frog02-32305.wykr.es/payment_webhook.php');
    $returnUrlSuccess   = strval('https://frog02-32305.wykr.es/player.html?id=' . $movieId);
    $control            = strval($userId . '-' . $movieId);
    $price              = floatval(6.00);

    // Budujemy string do podpisu
    $data   = $hash . "|" . $shopId . "|" . sprintf("%.2f", $price);

    if ($control != null && $control != "") {
        $data .= "|" . $control;
    }

    if ($description != null && $description != "") {
        $data .= "|" . $description;
    }

    if ($notifyURL != null && $notifyURL != "") {
        $data .= "|" . $notifyURL;
    }

    if ($returnUrlSuccess != null && $returnUrlSuccess != "") {
        $data .= "|" . $returnUrlSuccess;
    }

    // Obliczamy sygnaturę SHA256
    $signature = hash('sha256', $data);

    // Przygotowujemy dane do API
    $paybylinkData  = [
        'shopId'            => $shopId,
        'price'             => $price,
        'control'           => $control,
        'description'       => $description,
        'notifyURL'         => $notifyURL,
        'returnUrlSuccess'  => $returnUrlSuccess,
        'signature'         => $signature
    ];

    $data_string = json_encode($paybylinkData);

    // Wysyłamy zapytanie do API PayByLink
    $ch = curl_init('https://secure.pbl.pl/api/v1/transfer/generate');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ));

    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result);

    if(isset($result->transactionId) && !empty($result->transactionId)) {
        $payURL = 'https://secure.pbl.pl/transfer/' . $result->transactionId;
        // Przekierowanie do strony płatności
        header("Location: $payURL");
        exit();
    } else {
        echo 'Wystąpił błąd podczas generowania transakcji.';
    }

    ?>
