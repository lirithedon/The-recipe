<?php
session_start();
require_once 'inc/functions.php';


$db = connectDb(); // Connect to the database

// Handle recipe deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['recipe_id'])) {
    $recipe_id = $_GET['recipe_id'];

    $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
    if ($stmt->execute([$recipe_id])) {
        echo "<script>alert('Recipe deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting recipe.');</script>";
    }
    // Redirect to refresh and prevent resubmission
    header("Location: admin.php");
    exit;
}

// Fetch all recipes from the database
$stmt = $db->query("SELECT id, title, content FROM recipes ORDER BY date_posted DESC");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script> <!-- Add this line to include the script -->

</head>
<body>
<?php generateNavbar($isLoggedIn); ?>


    <main class="admin-container">
        <h2>All Recipes</h2>
        <table>
            <tr>
                <th>Title</th>
                <th>Content</th>
                <th>Action</th>
            </tr>
            <?php foreach ($recipes as $recipe): ?>
            <tr>
                <td><?php echo htmlspecialchars($recipe['title']); ?></td>
                <td><?php echo htmlspecialchars($recipe['content']); ?></td>
                <td><a href="admin.php?action=delete&recipe_id=<?php echo $recipe['id']; ?>">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </main>

    <footer>
        <p>&copy; 2024 The Recipe Community</p>
    </footer>
</body>
</html>
