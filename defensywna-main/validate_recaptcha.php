<?php
    $secretKey = '6Lejx1UrAAAAAGIUVJUiWyO6s-sBK3RwZra-EoeF';
    $recaptchaResponse = $_POST['recaptcha_response'];

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
    ];

    // Log the data being sent to reCAPTCHA
    error_log('reCAPTCHA data: ' . json_encode($data));

    // Use curl if available, otherwise fallback to file_get_contents
    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing purposes only, remove in production
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging

        // Add more detailed debugging for SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CAINFO, null);
        curl_setopt($ch, CURLOPT_CAPATH, null);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            error_log('cURL error: ' . $error);
            echo json_encode(['success' => false, 'error' => 'cURL error: ' . $error]);
            exit;
        }

        $info = curl_getinfo($ch);
        error_log('cURL info: ' . json_encode($info));

        curl_close($ch);
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);  // Suppress warnings
        if ($result === FALSE) {
            $error = error_get_last();
            error_log('Failed to contact reCAPTCHA service using file_get_contents: ' . $error['message']);
            echo json_encode(['success' => false, 'error' => 'Failed to contact reCAPTCHA service using file_get_contents: ' . $error['message']]);
            exit;
        }
    }

    // Log the raw response from reCAPTCHA
    error_log('Raw reCAPTCHA response: ' . $result);

    $response = json_decode($result, true);

    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo json_encode(['success' => true]);
        } else {
            // Log the full reCAPTCHA response for failure
            error_log('reCAPTCHA validation failed: ' . json_encode($response));
            echo json_encode(['success' => false, 'error' => 'reCAPTCHA validation failed: ' . json_encode($response)]);
        }
    } else {
        error_log('Invalid response from reCAPTCHA service: ' . $result);
        echo json_encode(['success' => false, 'error' => 'Invalid response from reCAPTCHA service: ' . $result]);
    }
?>
