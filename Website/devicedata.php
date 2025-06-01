<?php
session_start();
require 'config.php'; // Include your database configuration

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Trigger upload_data.php asynchronously
triggerUploadData();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: https://sarbeacon.infinityfreeapp.com/login.php"); // Redirect to login if not logged in
    exit();
}

// Get user information from the session
$userID = $_SESSION['user_id'];
$firstName = $_SESSION['firstName'] ?? 'Guest'; // Default to 'Guest' if not set
$lastName = $_SESSION['lastName'] ?? '';

// Get the device ID from the session
$deviceID = $_SESSION['deviceID'];

// Fetch the most recent device data using PDO (for health data and GPS)
$sql = "SELECT heart_rate, spo2, latitude, longitude, timestamp FROM device_data WHERE deviceID = :deviceID ORDER BY timestamp DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':deviceID', $deviceID, PDO::PARAM_STR);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Return the most recent data (for text editors 1 and 2)
$recentData = [
    "heart_rate" => $data['heart_rate'] ?? 'N/A',
    "spo2" => $data['spo2'] ?? 'N/A',
    "latitude" => $data['latitude'] ?? 'N/A',
    "longitude" => $data['longitude'] ?? 'N/A'
];

// Fetch the latest 10 data points for the heart rate and SPO2 charts
$sql_chart = "SELECT heart_rate, spo2, timestamp FROM device_data WHERE deviceID = :deviceID ORDER BY timestamp DESC LIMIT 10";
$stmt_chart = $pdo->prepare($sql_chart);
$stmt_chart->bindParam(':deviceID', $deviceID, PDO::PARAM_STR);
$stmt_chart->execute();
$chart_data = array_reverse($stmt_chart->fetchAll(PDO::FETCH_ASSOC)); // Reverse to maintain chronological order

$stmt->closeCursor();
$stmt_chart->closeCursor();

// Prepare the data for charts (for text editors 3 and 4)
$chartData = [
    "time" => array_map(fn($entry) => (new DateTime($entry['timestamp']))->format('H:i:s'), $chart_data),
    "heart_rate" => array_column($chart_data, 'heart_rate'),
    "spo2" => array_column($chart_data, 'spo2')
];

// Output the data as JSON, including user info
echo json_encode([
    "userInfo" => [
        "firstName" => $firstName,
        "lastName" => $lastName
    ],
    "recentData" => $recentData,
    "chartData" => $chartData
]);

// Function to trigger upload_data.php asynchronously
function triggerUploadData() {
    $urls = [
        "https://sarbeacon.infinityfreeapp.com/upload_data.php", // First PHP file
        "https://sarbeacon.infinityfreeapp.com/heart_rate_alert.php" // Second PHP file
    ];

    foreach ($urls as $url) {
        // Initialize a cURL session for each URL
        $ch = curl_init($url);

        // Set options for a background request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute the request
        curl_exec($ch);

        // Close the cURL session
        curl_close($ch);
    }
}
?>
