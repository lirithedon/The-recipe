<?php
// Start the session to access session variables
session_start();
// Function to connect to the database
require_once 'inc/functions.php';
// Database connection
$db = connectDb();

// Fetch all recipes from the database
$stmt = $db->query("SELECT * FROM recipes ORDER BY date_posted DESC");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Recipe - Home</title>
    <link rel="stylesheet" href="style.css">  
    <script src="script.js"></script> <!-- Add this line to include the script -->

</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div id="logo"><a href="index.php">The Recipe</a></div>
        <div id="nav-links">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">Profile</a>
                <a href="index.php" onclick="logout()">Logout</a>
            <?php else: ?>
                <a href="#" onclick="document.getElementById('loginModal').style.display='block'">Login</a>
                <a href="#" onclick="document.getElementById('signUpModal').style.display='block'">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'">&times;</span>
            <h2>Login</h2>
            <form id="loginForm" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="button" onclick="login()">Login</button>
            </form>
        </div>
    </div>

    <!-- Sign Up Modal -->
    <div id="signUpModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('signUpModal').style.display='none'">&times;</span>
            <h2>Sign Up</h2>
            <form id="signupForm" method="post" action="inc/signup.php">
                <div class="form-group">
                    <label for="signupUsername">Username:</label>
                    <input type="text" id="signupUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="signupEmail">Email:</label>
                    <input type="email" id="signupEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="signupPassword">Password:</label>
                    <input type="password" id="signupPassword" name="password" required>
                </div>
                <div class="form-group">
                    <label for="signupPasswordConfirm">Confirm Password:</label>
                    <input type="password" id="signupPasswordConfirm" name="passwordConfirm" required>
                </div>
                <button type="submit">Sign Up</button>
            </form>
        </div>
    </div>

    <main>
        <section class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome to The Recipe Community</h1>
                <p>Discover delicious recipes shared by food enthusiasts like you.</p>
            </div>
        </section>

        <section class="just-for-you-section">
            <h2>Just for You</h2>
            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <a href="detail_product.php?recipe_id=<?php echo $recipe['id']; ?>" class="recipe-link">
                        <div class="recipe-card">
                            <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                            <div class="recipe-info">
                                <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                <p>Uploaded by: <?php echo htmlspecialchars($recipe['user_id']); ?></p>
                                <!-- Assuming rating is implemented -->
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

    <script>
        // Get the modal elements
        var loginModal = document.getElementById('loginModal');
        var signUpModal = document.getElementById('signUpModal');

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == loginModal) {
                loginModal.style.display = "none";
            }
            if (event.target == signUpModal) {
                signUpModal.style.display = "none";
            }
        }
    </script>

</body>
</html>
