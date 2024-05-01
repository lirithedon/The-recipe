<?php
session_start();
require_once 'inc/functions.php';

// Ensure PDO connection is established
$db = connectDb();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user_id is set in the URL parameter
if (!isset($_GET['user_id'])) {
    header('Location: index.php'); // Redirect to homepage or appropriate page if user_id is not provided
    exit;
}

// Retrieve user_id from the URL parameter
$user_id = $_GET['user_id'];

// Fetch user's profile info
$stmt = $db->prepare("SELECT username, profile_info, profile_image_path FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Assign the username and profile image path from the fetched user data
$username = isset($user['username']) ? $user['username'] : 'Unknown User';
$profile_info = isset($user['profile_info']) ? $user['profile_info'] : 'No bio yet!';
$profile_image_path = isset($user['profile_image_path']) ? $user['profile_image_path'] : 'img/blank.webp'; // Assuming you have a default profile image

// Fetch user's recipes
$stmt = $db->prepare("SELECT id, title, content, image_path, category_id, calories, minutes, ingredients FROM recipes WHERE user_id = ? ORDER BY date_posted DESC");
$stmt->execute([$user_id]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo "$username's Profile"; ?></title>
    <link rel="stylesheet" href="inc/style.css">
</head>
<body>
    <?php generateNavbar(isset($_SESSION['user_id'])); ?>

    <main class="profile-container">
        <section class="user-profile">
            <h2><?php echo "$username's Profile"; ?></h2>
            <div>
                <?php if (!empty($profile_image_path)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile Picture" style="max-width: 200px;">
                <?php endif; ?>
                <p><strong>Bio:</strong> <?php echo $profile_info; ?></p>
            </div>
        </section>

        <section class="user-recipes">
            <div class="slideshow">
                <?php foreach ($recipes as $recipe): ?>
                    <div class="recipe">
                        <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($recipe['content'])); ?></p>
                        <?php if (!empty($recipe['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image">
                        <?php endif; ?>
                        <p><strong>Calories:</strong> <?php echo ($recipe['calories']); ?></p>
                        <p><strong>Minutes:</strong> <?php echo htmlspecialchars($recipe['minutes']); ?></p>
                        <p><strong>Category:</strong> <?php echo getCategoryName($recipe['category_id']); ?></p>
                        <p><strong>Ingredients:</strong><br><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="prev" onclick="prevSlide()">&#10094;</a>
            <a class="next" onclick="nextSlide()">&#10095;</a>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 The Recipe Community</p>
    </footer>

    <script>
        // JavaScript for slideshow
        let slideIndex = 0;
        const slides = document.querySelectorAll('.recipe');

        function showSlides() {
            if (slides.length > 0) {
                if (slideIndex >= slides.length) {
                    slideIndex = 0;
                } else if (slideIndex < 0) {
                    slideIndex = slides.length - 1;
                }
                slides.forEach(slide => {
                    slide.style.display = 'none';
                });
                slides[slideIndex].style.display = 'block';
                slides[slideIndex].classList.add('fade');
            }
        }

        function nextSlide() {
            slideIndex++;
            showSlides();
        }

        function prevSlide() {
            slideIndex--;
            showSlides();
        }

        document.addEventListener('DOMContentLoaded', showSlides);
    </script>
</body>
</html>
