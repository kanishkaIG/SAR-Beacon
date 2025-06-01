<?php
// Start the session
session_start();

// Include the database configuration file
include 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $deviceID = $_POST['deviceID'];
    $countryCode = $_POST['countryCode'];
    $emergencyContact = $_POST['emergencyContact'];

    // Remove the '+' from the country code if present
    $formattedEmergencyContact = str_replace('+', '', $countryCode) . $emergencyContact;

    // Check if passwords match
    if ($password !== $confirmPassword) {
        triggerUploadData();
        echo "<script>alert('Passwords do not match.'); window.location.href='https://sarbeacon.infinityfreeapp.com/signup/';</script>";
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare the SQL statement using PDO
    $sql = "INSERT INTO users (firstName, lastName, username, password, deviceID, emergencyContact) 
            VALUES (:firstName, :lastName, :username, :password, :deviceID, :emergencyContact)";
    $stmt = $pdo->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':firstName', $firstName);
    $stmt->bindParam(':lastName', $lastName);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':deviceID', $deviceID);
    $stmt->bindParam(':emergencyContact', $formattedEmergencyContact);

    // Execute the query and check for success
    if ($stmt->execute()) {
        triggerUploadData();
        echo "<script>alert('Registration successful!'); window.location.href='https://sarbeacon.infinityfreeapp.com/login/';</script>";
    } else {
        triggerUploadData();
        echo "<script>alert('Error: Failed to register.'); window.location.href='https://sarbeacon.infinityfreeapp.com/signup/';</script>";
    }
}

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
