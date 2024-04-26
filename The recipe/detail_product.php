<?php
include 'inc/functions.php'; // Include functions file

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
handleFormActions();
// Assuming you have a function to fetch recipes from the database
$recipes = fetchRecipes(); // Replace fetchRecipes() with the actual function to fetch recipes

// Retrieve recipe details from database based on recipe ID
$recipeId = $_GET['recipe_id'];
$db = connectDb();
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
$averageRating = calculateAverageRating($recipeId);
$totalComments = count(getComments($recipeId));
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Add Font Awesome for star icons -->
    <script src="script.js"></script> <!-- Add this line to include the script -->

    <style>
        /* Global Styles */


        /* Container for the entire page content */
        .container {
            max-width: 2000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Star Rating Styles */
        .star-rating {
            display: flex;
            flex-direction: row-reverse; /* Reverse the direction to make it left-to-right */
            justify-content: flex-end; /* Align items to the start of the flex container (left side) */
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
        }

        .star-rating i {
            color: #ccc; /* Default star color */
        }

        .star-rating input[type="radio"]:checked ~ label i {
            color: #ffcc00; /* Change color for checked stars */
        }

        .star-rating label i:hover,
        .star-rating label i:hover ~ i {
            color: #ffcc00; /* Change color for hovered stars */
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
            <p><strong>Average Rating:</strong> <?php displayStars($averageRating); ?></p>
            <p><strong>Total Reviews:</strong> <?php echo $totalComments; ?></p>

            <div class="recipe-info">
                <p><strong>Calories:</strong> <?php echo $recipe['calories']; ?></p>
                <p><strong>Minutes:</strong> <?php echo $recipe['minutes']; ?></p>
                <p><strong>Uploaded by:</strong> <a href="user_profile.php?user_id=<?php echo $recipe['user_id']; ?>"><?php echo getUsernameById($recipe['user_id']); ?></a></p>
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
    <?php if($isLoggedIn): ?> <!-- Check if the user is logged in -->
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="comment-form">
            <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
            <!-- Replace user_id with the actual user ID of the logged-in user -->
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
            <label for="comment">Comment:</label>
            <textarea name="comment" id="comment" cols="30" rows="5" required></textarea>
            <div class="star-rating">
            <input type="radio" name="rating" id="rate-5" value="5"><label for="rate-5"><i class="fas fa-star"></i></label>
            <input type="radio" name="rating" id="rate-4" value="4"><label for="rate-4"><i class="fas fa-star"></i></label>
            <input type="radio" name="rating" id="rate-3" value="3"><label for="rate-3"><i class="fas fa-star"></i></label>
            <input type="radio" name="rating" id="rate-2" value="2"><label for="rate-2"><i class="fas fa-star"></i></label>
            <input type="radio" name="rating" id="rate-1" value="1"><label for="rate-1"><i class="fas fa-star"></i></label>
            </div>
            <button type="submit" id="submit-comment">Submit Comment</button>
        </form>
    <?php else: ?>
        <a href="#" onclick="document.getElementById('loginModal').style.display='block'">Login to comment</a>
    <?php endif; ?>


    <?php
    // Retrieve comments for the recipe from the database
    $comments = getComments($recipeId);

    // Display comments on the page
    foreach ($comments as $comment) {
        // Display each comment with its rating, username, profile picture, and date posted
        echo '<div class="comment">';
        echo '<p><strong>Rating: </strong>' . $comment['rating'] . '/5</p>';

        // Fetch username and profile picture path from the database based on user ID
        $user = getUserById($comment['user_id']);
        if ($user) {
            $username = $user['username'];
            $profilePic = $user['profile_image_path']; // Assuming 'profile_pic' is the column name for the profile picture path
            
            // Display profile picture if available, otherwise display default profile picture
            if (!empty($profilePic)) {
                // Make the profile picture clickable with a link to the user's profile page
                echo '<a href="user_profile.php?user_id=' . $comment['user_id'] . '">';
                echo '<img src="' . $profilePic . '" alt="Profile Picture" class="profile-pic">';
                echo '</a>';
            } else {
                // Define the default comment profile picture path
                $defaultCommentProfilePic = 'img/blank.webp'; // Replace 'img/default_comment_profile.jpg' with the path to your default comment profile picture
                echo '<img src="' . $defaultCommentProfilePic . '" alt="Default Profile Picture" class="profile-pic">';
            }

            // Make the username clickable with a link to the user's profile page
            echo '<p><strong>Comment by: </strong><a href="user_profile.php?user_id=' . $comment['user_id'] . '">' . $username . '</a></p>'; // Display username
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
<!-- Just for You Section -->
<section class="just-for-you-section">
    <h2>Just for You</h2>

    <div class="recipe-grid">
        <?php 
        // Get the category ID of the current recipe
        $categoryId = $recipe['category_id'];

        // Fetch recipes from the same category
        $db = connectDb();
        $stmt = $db->prepare("SELECT * FROM recipes WHERE category_id = ? AND id != ?");
        $stmt->execute([$categoryId, $recipeId]);
        $similarRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Display similar recipes
        foreach ($similarRecipes as $similarRecipe): 
        ?>
            <a href="detail_product.php?recipe_id=<?php echo $similarRecipe['id']; ?>" class="recipe-link">
                <div class="recipe-card category-card">
                    <div class="category-label"><?php echo getCategoryName($similarRecipe['category_id']); ?></div>
                    <img src="<?php echo $similarRecipe['image_path']; ?>" alt="<?php echo ($similarRecipe['title']); ?>">
                    <div class="recipe-info">
                        <h3><?php echo $similarRecipe['title']; ?></h3>
                        <?php
                        $user = getUserById($similarRecipe['user_id']);
                        $username = $user ? $user['username'] : 'Unknown';
                        ?>
                        <p>Uploaded by: <?php echo $username; ?></p>
                        <p>Average Rating: <?php echo calculateAverageRating($similarRecipe['id']); ?>/5</p> <!-- Display average rating -->
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<footer>
    <p>&copy; 2024 The Recipe Community</p>
</footer>
</body>
</html>
