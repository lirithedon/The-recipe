<?php
include 'inc/functions.php'; // Include functions file

error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check if the recipe_id exists in the recipes table
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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form data
    $recipeId = $_POST['recipe_id'];
    $userId = $_POST['user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Validate recipe_id before proceeding
    if (!validateRecipeId($recipeId)) {
        // Invalid recipe_id, display error message
        $errorMessage = 'Invalid recipe ID';
        echo "Error: $errorMessage";
    } else {
        // Call function to submit the comment with rating
        $result = submitComment($recipeId, $userId, $rating, $comment);

        // Check if submission was successful
        if (isset($result['success'])) {
            // Redirect to the same page to avoid form resubmission
            header("Location: {$_SERVER['PHP_SELF']}?recipe_id=$recipeId");
            exit();
        } else {
            // Display error message
            $errorMessage = $result['error'];
            echo "Error: $errorMessage";
        }
    }
}

// Retrieve recipe details from database based on recipe ID
$recipeId = $_GET['recipe_id'];
$db = connectDb();
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $recipe['title']; ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script> <!-- Add this line to include the script -->

</head>

<body>
    <div class="product-container">
        <div class="product-image">
            <img src="<?php echo $recipe['image']; ?>" alt="<?php echo $recipe['title']; ?>">
        </div>
        <div class="product-details">
            <h2><?php echo $recipe['title']; ?></h2>
            <p><?php echo $recipe['content']; ?></p>
            <!-- You can add more details here -->
        </div>
    </div>

    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="comment-form">
        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
        <!-- Replace user_id with the actual user ID of the logged-in user -->
        <input type="hidden" name="user_id" value="1"> <!-- Example: replace 1 with actual user ID -->
        <label for="rating">Rating:</label>
        <select name="rating" id="rating">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select>
        <br>
        <label for="comment">Comment:</label><br>
        <textarea name="comment" id="comment" cols="30" rows="5" required></textarea>
        <br>
        <button type="submit" id="submit-comment">Submit Comment</button>
    </form>

    <?php
    // Retrieve comments for the recipe from the database
    $comments = getComments($recipeId);

    // Display comments on the page
    foreach ($comments as $comment) {
        // Display each comment with its rating, username, and date posted
        echo '<div class="comment">';
        echo '<p><strong>Rating: </strong>' . $comment['rating'] . '/5</p>';
        // Fetch username from the database based on user ID
        $user = getUserById($comment['user_id']);
        if ($user) {
            echo '<p><strong>Comment by: </strong>' . $user['username'] . '</p>'; // Display username
        } else {
            echo '<p><strong>Comment by: </strong>User Deleted</p>'; // Display placeholder if user not found
        }
        echo '<p><strong>Date: </strong>' . $comment['date_posted'] . '</p>';
        echo '<p><strong>Comment: </strong>' . $comment['comment'] . '</p>';
        echo '</div>';
    }
    ?>

</body>
</html>
