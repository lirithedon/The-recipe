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
// Function to retrieve the account type of the logged-in user
function getAccountType() {
    if (isset($_SESSION['user_id'])) {
        $db = connectDb();
        try {
            $stmt = $db->prepare("SELECT account_type FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $accountType = $stmt->fetchColumn();
            return $accountType ? $accountType : 'regular'; // Return the account type or 'regular' if not found
        } catch(PDOException $e) {
            // Handle database error
            return 'regular'; // Return 'regular' in case of error
        }
    } else {
        return 'regular'; // Return 'regular' if user is not logged in
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

// Function to fetch recipes from the database
function fetchRecipes() {
    // Connect to the database
    $db = connectDb();
    
    // Prepare and execute the SQL query to fetch recipes
    $stmt = $db->query("SELECT * FROM recipes");
    
    // Fetch all recipes as an associative array
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the fetched recipes
    return $recipes;
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
function getRecipesByCategory($categoryId, $limit) {
    $db = connectDb();
    $stmt = $db->prepare("SELECT * FROM recipes WHERE category_id = ? LIMIT ?");
    $stmt->execute([$categoryId, (int)$limit]); // Cast $limit to an integer
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Login function
function logIn($username, $password) {
    
    $db = connectDb();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Debugging: Output user data
        echo "Logged in user data: " . print_r($user, true) . "<br>";

        // Set session variables including account_type
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['account_type'] = $user['account_type']; // Assuming 'account_type' column exists in your users table

        // Debugging: Output session data
        echo "Session data after login: " . print_r($_SESSION, true) . "<br>";

        // Redirect to appropriate page based on account type
        if ($_SESSION['account_type'] === 'admin') {
            header("Location: admin.php");
            exit();
        } else {
            header("Location: profile.php");
            exit();
        }
    } else {
        // If login fails, return false
        return false;
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
    session_unset();
    session_destroy();
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
// Function to get a user's username by their ID
function getUsernameById($user_id) {
    // Connect to the database
    $db = connectDb();

    // Prepare and execute the query to fetch the username
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Fetch the username from the database
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the username
    return $user['username'];
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
    <div id="logo"><a href="index.php"><img src="img/therecipe.png" alt="The Recipe Logo" style="height: 50px;"></a></div>
        <div id="nav-links">
            <?php if ($isLoggedIn): ?>
                <?php if (getAccountType() === 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a> <!-- Link to admin dashboard -->
                <?php else: ?>
                    <a href="profile.php">Profile</a>
                <?php endif; ?>
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


function updateUserAccountType($db, $user_id, $new_account_type) {
    // Prepare and execute SQL query to update account type
    $stmt = $db->prepare("UPDATE users SET account_type = ? WHERE id = ?");
    return $stmt->execute([$new_account_type, $user_id]);
}
function displayStars($rating) {
    $fullStars = intval($rating); // Get the integer part of the rating
    $halfStar = ($rating - $fullStars) >= 0.5 ? true : false; // Check if there's a half star
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0); // Calculate the number of empty stars
    
    // Output full stars
    for ($i = 0; $i < $fullStars; $i++) {
        echo '<i class="fas fa-star"></i>';
    }
    
    // Output half star if present
    if ($halfStar) {
        echo '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Output empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        echo '<i class="far fa-star"></i>';
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
// Connect to the database and assign the PDO object to $db
$db = connectDb();

// Fetch the $user_id from the session if available
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    // Handle the case when the user is not logged in
}

// Check if $db is available and $user_id is defined
if (isset($db) && isset($user_id)) {
    // Fetch profile information including the profile image path from the database
    $stmt = $db->prepare("SELECT profile_info, profile_image_path FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Assign fetched profile information to variables
    $profile_info = isset($user['profile_info']) ? $user['profile_info'] : 'No bio yet!';
    $profileImagePath = isset($user['profile_image_path']) ? $user['profile_image_path'] : '';

    // If profile image path is empty, use the default image path
    if (empty($profileImagePath)) {
        $profileImagePath = "img/blank.webp";
    }

    // Construct the URL for the profile image
    $profileImageUrl = $profileImagePath;
} else {
    // Handle the case when $db or $user_id is not available
}

// Function to delete a user
function deleteUser($db, $user_id) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$user_id]);
}

$isLoggedIn = isLoggedIn();


// Function to update user's bio
function updateBio($newBio, $userId, $db) {
    $stmt = $db->prepare("UPDATE users SET profile_info = ? WHERE id = ?");
    if ($stmt->execute([$newBio, $userId])) {
        return true;
    } else {
        return false;
    }
}

// Function to update user's profile picture
function updateProfilePicture($tempName, $fileName, $userId, $db) {
    $destination = 'uploads/' . uniqid('', true) . '-' . $fileName;

    // Move uploaded file to destination
    if (move_uploaded_file($tempName, $destination)) {
        $stmt = $db->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
        if ($stmt->execute([$destination, $userId])) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if updating bio
    if (isset($_POST['profile_info'])) {
        $newBio = filter_var($_POST['profile_info']);
        $userId = $_SESSION['user_id'];
        
        if (updateBio($newBio, $userId, $db)) {
            echo "<script>alert('Bio updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating bio.');</script>";
        }
    }

    // Check if updating profile picture
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES['profileImage']['tmp_name'];
        $fileName = $_FILES['profileImage']['name'];
        $userId = $_SESSION['user_id'];

        if (updateProfilePicture($tempName, $fileName, $userId, $db)) {
            echo "<script>alert('Profile picture uploaded successfully.');</script>";
        } else {
            echo "<script>alert('Error uploading profile picture.');</script>";
        }
    }
}
?>
