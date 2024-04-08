<?php
session_start();
require_once 'inc/functions.php';

$db = connectDb(); // Ensure this function correctly initializes a PDO connection
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle recipe creation with image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'], $_POST['content'], $_FILES['recipeImage'])) {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);

    // Call the saveImage function to handle the uploaded image
    $imagePath = saveImage($_FILES['recipeImage'], $user_id);

    if ($imagePath) {
        // Image uploaded successfully, proceed to insert recipe into the database
        $stmt = $db->prepare("INSERT INTO recipes (user_id, title, content, image_path, date_posted) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$user_id, $title, $content, $imagePath])) {
            echo "<script>alert('Recipe created successfully.');</script>";
        } else {
            echo "<script>alert('Error creating recipe.');</script>";
        }
    }
    // If $imagePath is null, the saveImage function will have already outputted an error message
}

// Handle recipe deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_recipe' && isset($_GET['recipe_id'])) {
    $recipe_id = $_GET['recipe_id'];

    $stmt = $db->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$recipe_id, $user_id])) {
        echo "<script>alert('Recipe deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting recipe.');</script>";
    }
    // Redirect to refresh and prevent resubmission
    header("Location: profile.php");
    exit;
}

// Fetch user's recipes
$stmt = $db->prepare("SELECT id, title, content, image_path FROM recipes WHERE user_id = ? ORDER BY date_posted DESC");
$stmt->execute([$user_id]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="style.css">
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

    <main class="profile-container">
        <section class="recipe-form">
            <h2>Create a New Recipe</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Recipe Title" required>
                <textarea name="content" placeholder="Recipe Content" required></textarea>
                <input type="file" name="recipeImage" accept="image/*">
                <button type="submit">Submit Recipe</button>
            </form>
        </section>
        
        <section class="user-recipes">
            <h2>Your Recipes</h2>
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe">
                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($recipe['content'])); ?></p>
                    <?php if (!empty($recipe['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image" style="max-width: 100%; height: auto;">
                    <?php endif; ?>
                    <!-- Edit Recipe Form -->
                    <form action="edit_recipe.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                        <input type="text" name="title" placeholder="New Title" required>
                        <textarea name="content" placeholder="New Content" required></textarea>
                        <input type="file" name="recipeImage" accept="image/*">
                        <button type="submit">Edit Recipe</button>
                    </form>
                    <!-- Delete Recipe Link -->
                    <a href="profile.php?action=delete_recipe&recipe_id=<?php echo $recipe['id']; ?>">Delete Recipe</a>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 The Recipe Community</p>
    </footer>
</body>
</html>
