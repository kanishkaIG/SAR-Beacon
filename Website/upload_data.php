<?php
require 'config.php'; // Include the config file for PDO connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the PHP timezone to Sri Lanka (Colombo)
date_default_timezone_set('Asia/Colombo');

// Firebase API endpoint
$firebase_url = "https://sar-beacon-f5e87-default-rtdb.asia-southeast1.firebasedatabase.app/devices.json";

// Fetch data from Firebase
$response = file_get_contents($firebase_url);

if ($response === FALSE) {
    die(json_encode(["status" => "error", "message" => "Failed to fetch data from Firebase"]));
}

// Decode JSON response from Firebase
$data = json_decode($response, true);

if (!$data) {
    die(json_encode(["status" => "error", "message" => "Invalid data format from Firebase"]));
}

// Prepare SQL statement to insert data into the database using PDO
$sql = "INSERT INTO device_data (deviceID, heart_rate, spo2, latitude, longitude, timestamp) 
        VALUES (:deviceID, :heart_rate, :spo2, :latitude, :longitude, :timestamp)";
$stmt = $pdo->prepare($sql);

// Loop through each device and insert data into the database
foreach ($data as $deviceID => $deviceData) {
    // Ensure all required fields are present
    if (isset($deviceData['heart_rate'], $deviceData['spo2'], $deviceData['latitude'], $deviceData['longitude'])) {
        $heartRate = $deviceData['heart_rate'];
        $spo2 = $deviceData['spo2'];
        $latitude = $deviceData['latitude'];
        $longitude = $deviceData['longitude'];
        $colombo_time = date('Y-m-d H:i:s'); // Generate the correct Colombo time

        // Count existing entries for this device
        $countQuery = $pdo->prepare("SELECT COUNT(*) FROM device_data WHERE deviceID = :deviceID");
        $countQuery->execute([':deviceID' => $deviceID]);
        $count = $countQuery->fetchColumn();

        // If there are more than 30 entries, delete the oldest one to keep only the 30 newest
        if ($count >= 30) {
            $deleteQuery = $pdo->prepare("DELETE FROM device_data WHERE deviceID = :deviceID ORDER BY timestamp ASC LIMIT 1");
            $deleteQuery->bindParam(':deviceID', $deviceID, PDO::PARAM_STR);
            $deleteQuery->execute();
        }

        // Bind parameters and execute the prepared statement to insert the new entry
        $stmt->bindParam(':deviceID', $deviceID);
        $stmt->bindParam(':heart_rate', $heartRate);
        $stmt->bindParam(':spo2', $spo2);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':timestamp', $colombo_time);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Data for $deviceID uploaded successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to insert data for $deviceID"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data for $deviceID"]);
    }
}

// Close the connection
$pdo = null;
?>