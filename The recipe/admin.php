<?php
// Access control: Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle admin actions like deleting a recipe, banning a user, etc.
// This will involve more PHP code to perform these actions, which includes:
// - Fetching all recipes
// - Deleting a recipe
// - Managing users (e.g., changing roles, removing users)
?>
