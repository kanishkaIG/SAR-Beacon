<?php
// Include your database configuration
require 'config.php'; 

// Set the Infobip API URL and authentication headers
$url = 'https://rpve5e.api.infobip.com/sms/2/text/advanced';
$headers = [
    'Authorization: App cfd363e40f724917b522889230f6d653-e509458e-05ba-45b2-ab2b-dfd00223ddbd',
    'Content-Type: application/json',
    'Accept: application/json',
];

try {
    // Fetch devices with heart rate > 170 BPM
    $sql = "
        SELECT DISTINCT d.deviceID, u.emergencyContact
        FROM device_data d
        JOIN users u ON d.deviceID = u.deviceID
        WHERE d.heart_rate > 170
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $criticalDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no devices found, exit
    if (empty($criticalDevices)) {
        echo "No critical heart rate data found.";
        exit();
    }

    // Prepare the message payload dynamically for all critical devices
    $messages = [];
    foreach ($criticalDevices as $device) {
        // Ensure the emergency contact number is not null or empty
        if (!empty($device['emergencyContact'])) {
            $messages[] = [
                "destinations" => [
                    ["to" => $device['emergencyContact']]
                ],
                "from" => "SAR-Beacon",
                "text" => "Alert: over 170 BPM! Heart Rate at Critical Level. Log into https://sarbeacon.infinityfreeapp.com/ Website or 'SAR Beacon' Mobile App to Check the Conditions."
            ];
        }
    }

    // If no valid messages to send, exit
    if (empty($messages)) {
        echo "No valid emergency contacts found.";
        exit();
    }

    $data = ["messages" => $messages];

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

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
