<?php
session_start();
require_once 'inc/functions.php';

// Ensure PDO connection is established
$db = connectDb();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$profile_info = isset($user['profile_info']) ? $user['profile_info'] : 'No bio yet!';

$user_id = $_SESSION['user_id'];

// Handle recipe editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_recipe' && isset($_POST['recipe_id'])) {
    // Retrieve the edited recipe data
    $recipe_id = $_POST['recipe_id'];
    $title = filter_var($_POST['title']);
    $content = filter_var($_POST['content']);
    $category_id = $_POST['category'];
    $calories = intval($_POST['calories']);
    $minutes = intval($_POST['minutes']);
    $ingredients = filter_var($_POST['ingredients']);

    // Check if image is uploaded
    $imagePath = '';
    if (isset($_FILES['recipeImage']) && $_FILES['recipeImage']['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES['recipeImage']['tmp_name'];
        $fileName = $_FILES['recipeImage']['name'];
        $destination = 'uploads/' . uniqid('', true) . '-' . $fileName;

        if (move_uploaded_file($tempName, $destination)) {
            $imagePath = $destination;
        } else {
            echo "<script>alert('Error uploading file.');</script>";
        }
    }

    // Update the recipe data in the database
    $updateQuery = "UPDATE recipes SET title=?, content=?, category_id=?, calories=?, minutes=?, ingredients=?";
    $params = array($title, $content, $category_id, $calories, $minutes, $ingredients);
    if (!empty($imagePath)) {
        $updateQuery .= ", image_path=?";
        $params[] = $imagePath;
    }
    $updateQuery .= " WHERE id=? AND user_id=?";
    $params[] = $recipe_id;
    $params[] = $user_id;

    $stmt = $db->prepare($updateQuery);
    if ($stmt->execute($params)) {
        echo "<script>alert('Recipe edited successfully.');</script>";
    } else {
        echo "<script>alert('Error editing recipe.');</script>";
    }
}

// Handle recipe creation with image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'], $_POST['content'], $_POST['category'], $_FILES['recipeImage'], $_POST['calories'], $_POST['minutes'], $_POST['ingredients'])) {
    $title = filter_var($_POST['title']);
    $content = filter_var($_POST['content']);
    $category_id = $_POST['category']; // Retrieve selected category
    $calories = intval($_POST['calories']); // Convert to integer
    $minutes = intval($_POST['minutes']); // Convert to integer
    $ingredients = filter_var($_POST['ingredients']);

    $imagePath = ''; // Initialize image path variable

    // Check if file was uploaded without errors
    if (isset($_FILES['recipeImage']) && $_FILES['recipeImage']['error'] === UPLOAD_ERR_OK) {
        $tempName = $_FILES['recipeImage']['tmp_name'];
        $fileName = $_FILES['recipeImage']['name'];
        $destination = 'uploads/' . uniqid('', true) . '-' . $fileName; // Adjusted destination folder

        // Copy uploaded file to destination
        if (move_uploaded_file($tempName, $destination)) {
            $imagePath = $destination;
        } else {
            echo "<script>alert('Error uploading file.');</script>";
        }
    } else {
        echo "<script>alert('Error uploading file.');</script>";
    }

    // Proceed if image was copied successfully
    if (!empty($imagePath)) {
        // Proceed to insert recipe into the database
        $stmt = $db->prepare("INSERT INTO recipes (user_id, title, content, image_path, category_id, calories, minutes, ingredients, date_posted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$user_id, $title, $content, $imagePath, $category_id, $calories, $minutes, $ingredients])) {
            echo "<script>alert('Recipe created successfully.');</script>";
        } else {
            echo "<script>alert('Error creating recipe.');</script>";
        }
    }
}
// Fetch profile information including the profile image path from the database
$stmt = $db->prepare("SELECT profile_info, COALESCE(profile_image_path, 'img/blank.webp') AS profile_image_path FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Assign fetched profile information to variables
$profile_info = isset($user['profile_info']) ? $user['profile_info'] : 'No bio yet!';
$profileImagePath = isset($user['profile_image_path']) ? $user['profile_image_path'] : '';

// Construct the URL for the profile image
$profileImageUrl = $profileImagePath; // No need to check for empty path here

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $tempName = $_FILES['profileImage']['tmp_name'];
    $fileName = $_FILES['profileImage']['name'];
    $destination = 'uploads/' . uniqid('', true) . '-' . $fileName;

    // Move uploaded file to destination
    if (move_uploaded_file($tempName, $destination)) {
        // Update user's profile information in the database with the path to the uploaded profile picture
        $stmt = $db->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
        if ($stmt->execute([$destination, $user_id])) {
            echo "<script>alert('Profile picture uploaded successfully.');</script>";
        } else {
            echo "<script>alert('Error uploading profile picture.');</script>";
        }
    } else {
        echo "<script>alert('Error uploading file.');</script>";
    }

    // After processing the upload, redirect to avoid resubmission
    header("Location: profile.php");
    exit();
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
}
// Fetch user's profile info
$stmt = $db->prepare("SELECT profile_info FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Display user's profile info
if ($user && isset($user['profile_info'])) {
    $profile_info = ($user['profile_info']);
    echo "";
} else {
    echo "<p>Profile Info: N/A</p>";
}
$stmt = $db->prepare("SELECT id, title, content, image_path, category_id, calories, minutes, ingredients FROM recipes WHERE user_id = ? ORDER BY date_posted DESC");
$stmt->execute([$user_id]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="inc/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Add Font Awesome for star icons -->
    <script src="script.js"></script>
</head>

<body>
<?php generateNavbar($isLoggedIn); ?>
<main class="profile-container">
<section class="user-profile">
    <?php
    // Retrieve username and profile image path from session data if available
    $username = isset($_SESSION['username']) ? ($_SESSION['username']) : 'Guest';

    // Fetch profile information including the profile image path from the database
    $stmt = $db->prepare("SELECT profile_info, profile_image_path FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Assign fetched profile information to variables
    $profile_info = isset($user['profile_info']) ? $user['profile_info'] : 'No bio yet!';
    $profileImagePath = isset($user['profile_image_path']) ? $user['profile_image_path'] : '';

    // Construct the URL for the profile image
    $profileImageUrl = empty($profileImagePath) ? 'img/blank.webp' : $profileImagePath; // Replace 'path_to_default_image' with the path to a default profile image if no image is uploaded

    ?>
<h2>User Profile: <?php echo htmlspecialchars($username); ?></h2>
<div class="profile-info">
    <div class="profile-image-container">
        <!-- Add JavaScript to trigger file selection when clicking on the profile image -->
        <script>
            function chooseFile() {
                document.getElementById('profileImageInput').click();
            }
        </script>
        <!-- Change the image container to a clickable button -->
        <button type="button" onclick="chooseFile()">
            <img src="<?php echo $profileImageUrl; ?>" alt="Profile Image" class="profile-image">
            <?php if (empty($profileImagePath)): ?>
                <img src="img/default_profile_image.webp" alt="Default Profile Image" class="default-profile-image">
            <?php endif; ?>
        </button>
        <!-- Hidden input field to handle file selection -->
        <form action="profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
            <input type="file" name="profileImage" id="profileImageInput" accept="image/*" onchange="this.form.submit()">
        </form>
    </div>
</div>
        <p><?php echo $profile_info; ?></p>
    </div>
</section>

<section class="edit-bio">
        <!-- Form to update bio -->
        <h2>Edit Bio</h2>
<form action="profile.php" method="POST">
    <textarea name="profile_info" placeholder="Enter your new profile info" required></textarea>
    <button type="submit">Save Profile Info</button>
</form>

    </section>


</main>

<main class="profile-container">
    <section class="recipe-form">
        <h2>Create a New Recipe</h2>
        <form action="profile.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Recipe Title" required>
            <textarea name="content" placeholder="Recipe Content" required></textarea>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="1">Meat</option>
                <option value="2">Vegan</option>
                <option value="3">Dairy</option>
                <option value="4">Fruit</option>
                <option value="5">Gluten Free</option>
                <!-- Add other category options as needed -->
            </select>
            <input type="number" name="calories" placeholder="Calories" required>
            <input type="number" name="minutes" placeholder="Minutes" required>
            <textarea name="ingredients" placeholder="Ingredients" required></textarea>
            <input type="file" name="recipeImage" accept="image/*">
            <button type="submit">Submit Recipe</button>
        </form>
    </section>

    <section class="user-recipes">
        <h2>Your Recipes</h2>
        <?php foreach ($recipes as $recipe): ?>
            <div class="recipe">
                <h3><?php echo ($recipe['title']); ?></h3>
                <p><?php echo nl2br(($recipe['content'])); ?></p>
                <?php if (!empty($recipe['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image" style="max-width: 100%; height: auto;">
                    <p><strong>Calories:</strong> <?php echo ($recipe['calories']); ?></p>
                    <p><strong>Minutes:</strong> <?php echo ($recipe['minutes']); ?></p>
                    <p><strong>Category:</strong> <?php echo getCategoryName($recipe['category_id']); ?></p>
                    <p><strong>Ingredients:</strong><br><?php echo nl2br(($recipe['ingredients'])); ?></p>
                <?php endif; ?>
                <!-- Edit Recipe Form -->
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                    <input type="hidden" name="action" value="edit_recipe"> <!-- Add this hidden input for identifying the action -->
                    <input type="text" name="title" placeholder="New Title" required>
                    <textarea name="content" placeholder="New Content" required></textarea>
                    <input type="number" name="calories" placeholder="New Calories" required>
                    <input type="number" name="minutes" placeholder="New Minutes" required>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="1">Meat</option>
                        <option value="2">Vegan</option>
                        <option value="3">Dairy</option>
                        <option value="4">Fruit</option>
                        <option value="5">Gluten Free</option>
                        <!-- Add other category options as needed -->
                    </select>
                    <textarea name="ingredients" placeholder="New Ingredients" required></textarea>
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
