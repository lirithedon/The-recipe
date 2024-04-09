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
// Function to calculate the average rating for a recipe
function calculateAverageRating($recipeId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("SELECT AVG(rating) AS average_rating FROM ratings WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
        $averageRating = $stmt->fetchColumn();
        return $averageRating !== null ? round($averageRating, 1) : 0; // Return the average rating rounded to 1 decimal place
    } catch(PDOException $e) {
        // Handle database error
        return false;
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
// Function to check if a comment belongs to the current user for a specific recipe
function isUserCommentInRecipe($commentId, $userId, $recipeId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM ratings WHERE id = ? AND user_id = ? AND recipe_id = ?");
        $stmt->execute([$commentId, $userId, $recipeId]);
        $count = $stmt->fetchColumn();
        return $count > 0; // Return true if the comment belongs to the user and the recipe, false otherwise
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}

// Function to delete a comment by comment ID
function deleteComment($commentId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("DELETE FROM ratings WHERE id = ?");
        $stmt->execute([$commentId]);
        // Check if deletion was successful
        if ($stmt->rowCount() > 0) {
            return ['success' => true];
        } else {
            return ['error' => 'Failed to delete comment'];
        }
    } catch(PDOException $e) {
        // Handle database error
        return ['error' => $e->getMessage()];
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
function validateRecipeId($recipeId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $count = $stmt->fetchColumn();
        return $count > 0; // Return true if recipe_id exists, false otherwise
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}

function isUserComment($commentId, $userId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM ratings WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        $count = $stmt->fetchColumn();
        return $count > 0; // Return true if the comment belongs to the user, false otherwise
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}
// Function to retrieve a comment by its ID
function getCommentById($commentId) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("SELECT * FROM ratings WHERE id = ?");
        $stmt->execute([$commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}


function generateNavbar($isLoggedIn) {
    ?>
    <!-- Navbar -->
    <nav class="navbar">
        <div id="logo"><a href="index.php">The Recipe</a></div>
        <div id="nav-links">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">Profile</a>
                <a href="index.php" onclick="logout()">Logout</a>
            <?php else: ?>
                <a href="#" onclick="document.getElementById('loginModal').style.display='block'">Login</a>
                <a href="#" onclick="document.getElementById('signUpModal').style.display='block'">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'">&times;</span>
            <h2>Login</h2>
            <form id="loginForm" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="button" onclick="login()">Login</button>
            </form>
        </div>
    </div>

  
   <!-- Sign Up Modal -->
   <div id="signUpModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('signUpModal').style.display='none'">&times;</span>
            <h2>Sign Up</h2>
            <?php if (isset($errorMessage)): ?>
                <p class="error-message"><?php echo $errorMessage; ?></p>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="signupUsername">Username:</label>
                    <input type="text" id="signupUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="signupEmail">Email:</label>
                    <input type="email" id="signupEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="signupPassword">Password:</label>
                    <input type="password" id="signupPassword" name="password" required>
                </div>
                <div class="form-group">
                    <label for="signupPasswordConfirm">Confirm Password:</label>
                    <input type="password" id="signupPasswordConfirm" name="passwordConfirm" required>
                </div>
                <button type="submit">Sign Up</button>
            </form>
        </div>
    </div>
    <?php
}

// Handle sign-up form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['passwordConfirm'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];

    // Validate password confirmation
    if ($password !== $passwordConfirm) {
        $errorMessage = "Password confirmation doesn't match.";
    } else {
        // Sign up user
        $signUpResult = signUp($username, $email, $password);
        if (isset($signUpResult['success'])) {
            $successMessage = $signUpResult['success'];
        } elseif (isset($signUpResult['error'])) {
            $errorMessage = $signUpResult['error'];
        }
    }
}




// Function to update a comment in the database
function updateComment($commentId, $newComment) {
    $db = connectDb();
    try {
        $stmt = $db->prepare("UPDATE ratings SET comment = ? WHERE id = ?");
        return $stmt->execute([$newComment, $commentId]);
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}
function getTopRatedRecipes() {
    $db = connectDb(); // Connect to the database

    try {
        // Prepare SQL statement to fetch top 5 highest rated recipes
        $stmt = $db->prepare("SELECT recipes.*, AVG(ratings.rating) AS avg_rating 
                              FROM recipes 
                              LEFT JOIN ratings ON recipes.id = ratings.recipe_id 
                              GROUP BY recipes.id 
                              ORDER BY avg_rating DESC 
                              LIMIT 5");
        // Execute the statement
        $stmt->execute();
        // Fetch top 5 highest rated recipes
        $topRatedRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $topRatedRecipes; // Return top 5 highest rated recipes
    } catch (PDOException $e) {
        // Handle database error
        return false; // Return false if an error occurs
    }
}
// Define the getCategoryName() function to retrieve the category name based on its ID
function getCategoryName($category_id) {
    // Replace this with your logic to fetch category name from the database based on category_id
    // Example: You might have a categories table with category_id and category_name columns
    // and you would query the database to fetch the category name based on the category_id
    // Here, for demonstration purposes, we'll just return a static category name based on category_id
    switch ($category_id) {
        case 1:
            return 'Meat';
        case 2:
            return 'Vegan';
        case 3:
            return 'Dairy';
        case 4:
            return 'Fruit';
        case 5:
            return 'Gluten Free';
        default:
            return 'Unknown';
    }
}
function handleFormActions() {
    if(isset($_SESSION['user_id'])) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['delete_comment'])) {
                $commentId = $_POST['comment_id'];
                $recipeId = $_POST['recipe_id'];
                $userId = $_SESSION['user_id'];
                if (isUserCommentInRecipe($commentId, $userId, $recipeId)) {
                    $deleteResult = deleteComment($commentId);
                    if ($deleteResult['success']) {
                        header("Location: {$_SERVER['PHP_SELF']}?recipe_id=$recipeId");
                        exit();
                    } else {
                        echo "Error: Failed to delete comment";
                    }
                } else {
                    echo "Error: You are not authorized to delete this comment.";
                }
            } elseif (isset($_POST['edit_comment'])) {
                $commentId = $_POST['edit_comment_id'];
                $newComment = $_POST['new_comment'];
                if (isUserComment($commentId, $_SESSION['user_id'])) {
                    $updateResult = updateComment($commentId, $newComment);
                    if ($updateResult) {
                        header("Location: {$_SERVER['PHP_SELF']}?recipe_id={$_POST['recipe_id']}");
                        exit();
                    } else {
                        echo "Error: Failed to update comment";
                    }
                } else {
                    echo "Error: You are not authorized to edit this comment.";
                }
            } else {
                $recipeId = $_POST['recipe_id'];
                $userId = $_SESSION['user_id'];
                $rating = $_POST['rating'];
                $comment = $_POST['comment'];
                if (!validateRecipeId($recipeId)) {
                    $errorMessage = 'Invalid recipe ID';
                    echo "Error: $errorMessage";
                } else {
                    $result = submitComment($recipeId, $userId, $rating, $comment);
                    if (isset($result['success'])) {
                        header("Location: {$_SERVER['PHP_SELF']}?recipe_id=$recipeId");
                        exit();
                    } else {
                        $errorMessage = $result['error'];
                        echo "Error: $errorMessage";
                    }
                }
            }
        }
    } 
}

// Check if the user is logged in
$isLoggedIn = isLoggedIn();
?>