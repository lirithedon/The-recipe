<?php
session_start();

// Database connection
function connectDb() {
    $host = 'localhost';
    $dbname = 'the_recipe';
    $username = 'root';
    $password = '';
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
}

// Sign-up function
function signUp($username, $email, $password) {
    $db = connectDb();
    // Assume validation and sanitization are done beforehand
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashedPassword])) {
        return true; // Sign-up successful
    } else {
        return false; // Sign-up failed
    }
}

// Login function
function logIn($username, $password) {
    $db = connectDb();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return ['success' => true]; // Login successful
    } else {
        return ['success' => false, 'message' => 'Invalid username or password.']; // Login failed
    }
}

function saveImage($file, $directory)
{
    $targetDir = "uploads/" . $directory . "/";

    // Create the destination directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Check if the file is uploaded successfully
    if (!empty($file["tmp_name"])) {
        $targetFile = $targetDir . basename($file['name']);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($file["tmp_name"]);

        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Additional checks and file processing go here

        // Move the uploaded file
        if ($uploadOk == 1 && move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $targetFile;
        } else {
            echo "Sorry, there was an error uploading your file.";
            return null;
        }
    } else {
        echo "No file uploaded.";
        return null;
    }
}
// Logout function
function logOut() {
    // Ensure session is started before trying to destroy it
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    // Optionally redirect to homepage or login page here, or handle redirection in the caller script
}

// Function to check if the user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Example Usage (not to be included in functions.php):
/*
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        // Call signUp function and handle response
    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
        // Call logIn function and handle response
    }
}
*/
// inc/functions.php

// Function to retrieve comments for a recipe from the database
function getComments($recipeId) {
    $db = connectDb(); // Connect to the database (assuming connectDb() is your database connection function)
    try {
        // Prepare SQL statement to select comments for the specified recipe ID
        $stmt = $db->prepare("SELECT * FROM ratings WHERE recipe_id = ?");
        // Bind parameter
        $stmt->bindParam(1, $recipeId, PDO::PARAM_INT);
        // Execute the statement
        $stmt->execute();
        // Fetch all comments for the recipe
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $comments; // Return comments
    } catch(PDOException $e) {
        // Handle database error (e.g., log the error, return error message, etc.)
        return false; // Return false if an error occurs
    }
}

// Function to get user details by user ID
function getUserById($userId) {
    $db = connectDb(); // Connect to the database (assuming connectDb() is your database connection function)
    try {
        // Prepare SQL statement to select user details by user ID
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        // Bind parameter
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        // Execute the statement
        $stmt->execute();
        // Fetch user details
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user; // Return user details
    } catch(PDOException $e) {
        // Handle database error (e.g., log the error, return error message, etc.)
        return false; // Return false if an error occurs
    }
}
// inc/functions.php

// Function to submit comment with rating
function submitComment($recipeId, $userId, $rating, $comment) {
    $db = connectDb(); // Connect to the database (assuming connectDb() is your database connection function)
    if (!$db) {
        return ['error' => 'Failed to connect to the database'];
    }

    try {
        // Prepare SQL statement to insert comment into ratings table
        $stmt = $db->prepare("INSERT INTO ratings (recipe_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        // Bind parameters
        $stmt->bindParam(1, $recipeId, PDO::PARAM_INT);
        $stmt->bindParam(2, $userId, PDO::PARAM_INT);
        $stmt->bindParam(3, $rating, PDO::PARAM_INT);
        $stmt->bindParam(4, $comment, PDO::PARAM_STR);
        // Execute the statement
        if ($stmt->execute()) {
            // Return success message
            return ['success' => 'Comment submitted successfully'];
        } else {
            // Return error message
            return ['error' => 'Failed to insert comment'];
        }
    } catch(PDOException $e) {
        // Handle database error
        return ['error' => $e->getMessage()];
    }
}
// functions.php

// Function to create a new recipe
function createRecipe($title, $content, $image, $userId) {
    $db = connectDb(); // Connect to the database

    // Handle image upload
    $uploadDir = 'uploads/'; // Specify upload directory
    $imageName = uniqid() . '_' . $image['name']; // Generate unique image name
    $targetPath = $uploadDir . $imageName; // Set target path for uploaded image

    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
        // Image uploaded successfully, proceed to insert recipe into the database
        $stmt = $db->prepare("INSERT INTO recipes (user_id, title, content, image_path, date_posted) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$userId, $title, $content, $targetPath])) {
            return true; // Recipe created successfully
        } else {
            return false; // Error creating recipe
        }
    } else {
        return false; // Failed to upload image
    }
}

// Function to edit an existing recipe
function editRecipe($recipeId, $title, $content, $image) {
    $db = connectDb(); // Connect to the database

    // Handle image upload
    $uploadDir = 'uploads/'; // Specify upload directory
    $imageName = uniqid() . '_' . $image['name']; // Generate unique image name
    $targetPath = $uploadDir . $imageName; // Set target path for uploaded image

    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
        // Image uploaded successfully, proceed to update recipe in the database
        $stmt = $db->prepare("UPDATE recipes SET title = ?, content = ?, image_path = ? WHERE id = ?");
        if ($stmt->execute([$title, $content, $targetPath, $recipeId])) {
            return true; // Recipe edited successfully
        } else {
            return false; // Error editing recipe
        }
    } else {
        return false; // Failed to upload image
    }
}

// Check if the user is logged in
$isLoggedIn = isLoggedIn();
?>
