<?php
// Start the session to access session variables
session_start();

// Function to connect to the database
require_once 'inc/functions.php';
// Database connection
$db = connectDb();

// Fetch all recipes from the database if no category is selected
if (!isset($_GET['category'])) {
    $stmt = $db->query("SELECT * FROM recipes ORDER BY date_posted DESC");
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch recipes of the selected category
    $category_id = $_GET['category'];
    $stmt = $db->prepare("SELECT * FROM recipes WHERE category_id = ? ORDER BY date_posted DESC");
    $stmt->execute([$category_id]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch top 5 highest rated recipes
$topRatedRecipes = getTopRatedRecipes(); // Assuming getTopRatedRecipes() function is defined in functions.php

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Recipe - Home</title>
    <link rel="stylesheet" href="inc/style.css">  
    <script src="script.js"></script> <!-- Add this line to include the script -->
 
</head>
<body>

<?php generateNavbar($isLoggedIn); ?>

    <main>
        <!-- Section 1: Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome to The Recipe Community</h1>
                <p>Discover delicious recipes shared by food enthusiasts like you.</p>
            </div>
        </section>

        <!-- Section 2: Category Section -->
        <section class="just-for-you-section">
            <h2>Explore Categories</h2>
            <div class="category-container">
                <!-- Circle 1: Vegan -->
                <div class="circle">
                    <a href="?category=1"><img src="img/vegan.png" alt="Vegan"></a>
                </div>
                <!-- Circle 2: Meat -->
                <div class="circle">
                    <a href="?category=2"><img src="img/meat.png" alt="Meat"></a>
                </div>
                <!-- Circle 3: Gluten-Free -->
                <div class="circle">
                    <a href="?category=3"><img src="img/free.png" alt="Gluten-Free"></a>
                </div>
                <!-- Circle 4: Insert your category here -->
                <div class="circle">
                    <a href="?category=4"><img src="img/milk-box.png" alt="circle"></a>
                </div>
                <!-- Circle 5: Insert your category here -->
                <div class="circle">
                    <a href="?category=5"><img src="img/harvest.png" alt="fruit"></a>
                </div>
            </div>
        </section>

       <!-- Section 3: Top 5 Highest Rated Recipes -->
<section class="top-rated-section">
    <h2>Top 5 Highest Rated Recipes</h2>
    <div class="recipe-grid">
        <?php foreach ($topRatedRecipes as $recipe): ?>
            <a href="detail_product.php?recipe_id=<?php echo $recipe['id']; ?>" class="recipe-link">
                <div class="recipe-card">
                    <img src="<?php echo ($recipe['image_path']); ?>" alt="<?php echo ($recipe['title']); ?>">
                    <div class="recipe-info">
                        <h3><?php echo ($recipe['title']); ?></h3>
                        <p>Average Rating: <?php echo number_format($recipe['avg_rating'], 1); ?>/5 <?php echo str_repeat('&#9733;', round($recipe['avg_rating'])); ?></p> 
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>


        <!-- Section 4: Just for You Section -->
        <section class="just-for-you-section">
            <h2>Just for You</h2>
            
            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <a href="detail_product.php?recipe_id=<?php echo $recipe['id']; ?>" class="recipe-link">
                        <div class="recipe-card">
                            <img src="<?php echo ($recipe['image_path']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                            <div class="recipe-info">
                                <h3><?php echo ($recipe['title']); ?></h3>
                                <?php
                                        $user = getUserById($recipe['user_id']);
                                        if ($user) {
                                            $username = ($user['username']);
                                        } else {
                                            $username = 'Unknown';
                                        }
                                        ?>
                                <p>Uploaded by: <?php echo $username; ?></p>
                                <p>Average Rating: <?php echo calculateAverageRating($recipe['id']); ?>/5</p> <!-- Display average rating -->
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 The Recipe Community</p>
    </footer>

</body>
</html>
