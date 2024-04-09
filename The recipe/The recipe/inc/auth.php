<?php
session_start();
require_once '../inc/functions.php'; // Adjust path as needed
$db = connectDb();

$response = ['success' => false, 'message' => 'Invalid credentials'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug output
    error_log("Received POST request");

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // More debug output
    error_log("Username: $username");

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        $response['success'] = true;
        $response['message'] = 'Login successful';
    } else {
        // Additional debug output on failure
        error_log("Login failed for user: $username");
    }
}

header('Content-Type: application/json');
echo json_encode($response);
