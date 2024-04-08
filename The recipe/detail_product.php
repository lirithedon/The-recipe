<?php
include 'inc/functions.php'; // Include functions file

error_reporting(E_ALL);
ini_set('display_errors', 1);


// Check if user is logged in
if(isset($_SESSION['user_id'])) {
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if delete comment request is submitted
        if (isset($_POST['delete_comment'])) {
            // Retrieve comment ID, recipe ID, and user ID
            $commentId = $_POST['comment_id'];
            $recipeId = $_POST['recipe_id'];
            $userId = $_SESSION['user_id'];
            
            // Check if the user is authorized to delete the comment for this recipe
            if (isUserCommentInRecipe($commentId, $userId, $recipeId)) {
                // Call function to delete the comment from the database
                $deleteResult = deleteComment($commentId);
                // Check if deletion was successful
                if ($deleteResult['success']) {
                    // Optionally, redirect or display a success message
                    header("Location: {$_SERVER['PHP_SELF']}?recipe_id=$recipeId");
                    exit();
                } else {
                    // Optionally, display an error message
                    echo "Error: Failed to delete comment";
                }
            } else {
                // User is not authorized to delete this comment
                echo "Error: You are not authorized to delete this comment.";
            }
        } elseif (isset($_POST['edit_comment'])) {
            // Process edit comment request
            $commentId = $_POST['edit_comment_id'];
            $newComment = $_POST['new_comment'];
            // Check if the user is authorized to edit the comment
            if (isUserComment($commentId, $_SESSION['user_id'])) {
                // Call function to update the comment in the database
                $updateResult = updateComment($commentId, $newComment);
                // Check if update was successful
                if ($updateResult) {
                    // Optionally, redirect or display a success message
                    header("Location: {$_SERVER['PHP_SELF']}?recipe_id={$_POST['recipe_id']}");
                    exit();
                } else {
                    // Optionally, display an error message
                    echo "Error: Failed to update comment";
                }
            } else {
                // User is not authorized to edit this comment
                echo "Error: You are not authorized to edit this comment.";
            }
        } else {
            // Process form data
            $recipeId = $_POST['recipe_id'];
            $userId = $_SESSION['user_id'];
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
    }
} else {
    // User is not logged in, handle the scenario accordingly
    echo "You must be logged in to perform this action. <a href='login.php'>Log in</a>";
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
            <img src="<?php echo $recipe['image_path']; ?>" alt="<?php echo $recipe['title']; ?>">
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
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
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

        // Add edit form only if the comment belongs to the current user
        if (isset($_SESSION['user_id']) && $comment['user_id'] == $_SESSION['user_id']) {
            echo '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
            echo '<input type="hidden" name="edit_comment_id" value="' . $comment['id'] . '">';
            echo '<input type="hidden" name="recipe_id" value="' . $recipeId . '">';
            echo '<textarea name="new_comment" cols="30" rows="3">' . $comment['comment'] . '</textarea>';
            echo '<div class="action-buttons">';
            echo '<button type="submit" name="edit_comment">Update</button>';
            // Include comment_id for delete operation
            echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
            echo '<button type="submit" name="delete_comment">Delete</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<p><strong>Comment: </strong>' . $comment['comment'] . '</p>';
        }
        echo '</div>';
    }
    ?>
</body>

</html>
