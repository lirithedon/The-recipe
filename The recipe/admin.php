<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: index.php"); // Redirect unauthorized users to the homepage
    exit;
}

// Fetch all recipes from the database
require_once 'inc/functions.php';
$db = connectDb();
$stmt = $db->query("SELECT * FROM recipes ORDER BY date_posted DESC");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css"> <!-- Include your CSS file -->
</head>
<body>

    <header>
        <nav class="navbar">
            <div id="logo"><a href="index.php">The Recipe</a></div>
            <div id="nav-links">
                <a href="index.php">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main class="admin-panel">
        <h1>Welcome, Admin!</h1>
        <h2>All Recipes</h2>
        <div class="recipe-grid">
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card">
                    <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <div class="recipe-info">
                        <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <p>Uploaded by: <?php echo htmlspecialchars($recipe['user_id']); ?></p>
                        <p>Date Posted: <?php echo htmlspecialchars($recipe['date_posted']); ?></p>
                        <!-- Delete Recipe Form -->
                        <form action="admin.php?action=delete_recipe" method="POST">
                            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 The Recipe Community</p>
    </footer>

</body>
</html>
