<?php
session_start();

require 'config.php'; // Include your database configuration

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the statement using PDO
    $stmt = $pdo->prepare("SELECT user_id, password, deviceID, firstName, lastName FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the user and password
    if ($user && password_verify($password, $user['password'])) {
        // Store user details in the session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['deviceID'] = $user['deviceID'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        
        // Trigger upload_data.php by redirection, followed by device data page
        triggerUploadData();

        // Redirect to device data page
        header("Location: https://sarbeacon.infinityfreeapp.com/devicedata/");
        exit(); // Ensure the script stops executing after redirect
    } else {
        // Trigger upload_data.php by redirection, followed by login page
        triggerUploadData();

        // Show alert for login failure and redirect to login page
        echo "<script>alert('Login failed: Invalid username or password.'); window.location.href='https://sarbeacon.infinityfreeapp.com/login.php';</script>";
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
