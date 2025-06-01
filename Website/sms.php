<?php
// Set the API URL and authentication headers
$url = 'https://rpve5e.api.infobip.com/sms/2/text/advanced';
$headers = [
    'Authorization: App cfd363e40f724917b522889230f6d653-e509458e-05ba-45b2-ab2b-dfd00223ddbd',
    'Content-Type: application/json',
    'Accept: application/json',
];

// Prepare the message payload
$data = [
    "messages" => [
        [
            "destinations" => [
                ["to" => "94719201676"] // Replace with the recipient's phone number
            ],
            "from" => "SAR-Beacon",
            "text" => "Alert: over 170 BPM! Heart Rate at Critical Level. Log into https://sarbeacon.infinityfreeapp.com/ Website or 'SAR Beacon' Mobile App to Check the Conditions."
        ]
    ]
];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Encode data as JSON
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the request and capture the response
$response = curl_exec($ch);

// Check for errors
if ($response === false) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo 'Response: ' . $response;
}

// Close the cURL session
curl_close($ch);
?>
