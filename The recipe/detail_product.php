<?php
include 'inc/functions.php'; // Include functions file

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
handleFormActions();

// Retrieve recipe details from database based on recipe ID
$recipeId = $_GET['recipe_id'];
$db = connectDb();
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve comments for the recipe from the database
$comments = getComments($recipeId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $recipe['title']; ?></title>
    <link rel="stylesheet" href="inc/style.css">
    <script src="script.js"></script> <!-- Add this line to include the script -->

    <style>
   /* Global Styles */
body {
    font-family: 'Helvetica Neue', Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

/* Container for the entire page content */
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Header Styles */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
    margin-bottom: 20px;
}

.header .title {
    margin: 0;
    color: #333;
}

/* Main Content Styles */
.main-content {
    display: flex;
}

/* Recipe Image */
.recipe-image img {
    width: 400px;
    height: auto;
    border-radius: 4px;
    margin-right: 20px;
}

/* Recipe Details */
.recipe-details {
    flex-grow: 1;
}

.recipe-details h1 {
    margin-top: 0;
    color: #e67e22;
}

.recipe-info {
    font-size: 0.9em;
    color: #888;
}

/* Ingredient List */
.ingredient-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ingredient-list li {
    padding: 5px 0;
}

/* Comment Section */
.comments-container {
    margin-top: 40px;
}

.comment-form {
    background-color: #f9f9f9;
    padding: 15px;
    margin-bottom: 20px;
}

.comment-form textarea,
.comment-form select,
.comment-form input[type="submit"] {
    width: 100%;
    margin-bottom: 10px;
}

.comment {
    background-color: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.comment p {
    margin: 0 0 10px 0;
}

.comment .meta {
    font-size: 0.8em;
    color: #888;
}

.comment .actions {
    text-align: right;
}

.comment .actions button {
    background-color: #e67e22;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.comment .actions button:hover {
    background-color: #d35400;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        flex-direction: column;
    }

    .recipe-image img {
        width: 100%;
        margin-bottom: 20px;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header .title {
        margin-bottom: 10px;
    }
}

      
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php generateNavbar($isLoggedIn); ?>

    <div class="container">
        <div class="recipe-container">
            <div class="recipe-image">
                <img src="<?php echo $recipe['image_path']; ?>" alt="<?php echo $recipe['title']; ?>">
            </div>
            <div class="recipe-details">
                <h2><?php echo $recipe['title']; ?></h2>
                <div class="recipe-info">
                    <p><strong>Calories:</strong> <?php echo $recipe['calories']; ?></p>
                    <p><strong>Minutes:</strong> <?php echo $recipe['minutes']; ?></p>
                </div>
                <h3>Ingredients:</h3>
                <ul class="ingredient-list">
                    <?php
                    // Assuming ingredients are stored as a comma-separated string in the database
                    $ingredients = explode(',', $recipe['ingredients']);
                    foreach ($ingredients as $ingredient) {
                        echo '<li>' . $ingredient . '</li>';
                    }
                    ?>
                </ul>
                <p><?php echo $recipe['content']; ?></p>
                <!-- Add more recipe details here if needed -->
            </div>
        </div>

        <!-- Comment form and comments section -->
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
            <label for="comment">Comment:</label>
            <textarea name="comment" id="comment" cols="30" rows="5" required></textarea>
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
    </div>
</body>
</html>
