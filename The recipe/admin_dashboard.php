<?php
session_start();
require_once 'inc/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = connectDb(); // Connect to the database

// Fetch all users from the database
$stmt = $db->query("SELECT id, username, email, account_type FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all recipes from the database
$stmt = $db->query("SELECT id, title, content FROM recipes ORDER BY date_posted DESC");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all comments from the database
$stmt = $db->query("SELECT id, recipe_id, comment, date_posted FROM ratings ORDER BY date_posted DESC");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['recipe_id'])) {
    $recipe_id = $_GET['recipe_id'];

    try {
        $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
        if ($stmt->execute([$recipe_id])) {
            // Recipe deleted successfully
            echo "<script>alert('Recipe deleted successfully.');</script>";
        } else {
            // Error deleting recipe
            echo "<script>alert('Error deleting recipe.');</script>";
        }
    } catch (PDOException $e) {
        // Output any database error message for debugging
        echo "Error: " . $e->getMessage();
    }
    // Redirect to refresh and prevent resubmission
    header("Location: admin_dashboard.php");
    exit;
}


// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    if ($user_id != $_SESSION['user_id']) { // Check if the user is trying to delete their own account
        if (deleteUser($db, $user_id)) {
            echo "<script>alert('User deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting user.');</script>";
        }
    } else {
        echo "<script>alert('You cannot delete your own account.');</script>";
    }
    // Redirect to refresh and prevent resubmission
    header("Location: admin_dashboard.php");
    exit;
}

// Update user's account type
if (isset($_GET['action']) && $_GET['action'] == 'update_account_type' && isset($_GET['user_id']) && isset($_GET['new_account_type'])) {
    $user_id = $_GET['user_id'];
    $new_account_type = $_GET['new_account_type'];

    if ($user_id != $_SESSION['user_id']) { // Check if the user is trying to change their own account type
        if (updateUserAccountType($db, $user_id, $new_account_type)) {
            echo "<script>alert('User account type updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating user account type.');</script>";
        }
    } else {
        echo "<script>alert('You cannot change your own account type.');</script>";
    }
    // Redirect to refresh and prevent resubmission
    header("Location: admin_dashboard.php");
    exit;
}

// Handle comment deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_comment' && isset($_GET['comment_id'])) {
    $comment_id = $_GET['comment_id'];

    $stmt = $db->prepare("DELETE FROM ratings WHERE id = ?");
    if ($stmt->execute([$comment_id])) {
        echo "<script>alert('Comment deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting comment.');</script>";
    }
    // Redirect to refresh and prevent resubmission
    header("Location: admin_dashboard.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="inc/style.css">
    <script src="script.js"></script>

    <style>
   

h1, h2, h3 {
    color: #333;
}

a {
    color: #0066cc;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f8f8f8;
}

tr:hover {
    background-color: #f1f1f1;
}

    </style>
    <?php generateNavbar($isLoggedIn); ?>

</head>
<body>
<main class="admin-container">
<h1>Admin dashboard</h1>

    <div class="table-wrapper">
        <div class="scrollable-table">
            <h3>All Recipes</h3>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td><?php echo ($recipe['title']); ?></td>
                        <td><?php echo ($recipe['content']); ?></td>
                        <td><a href="admin_dashboard.php?action=delete&recipe_id=<?php echo $recipe['id']; ?>">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="scrollable-table">
            <h3>All Comments</h3>
            <table>
                <tr>
                    <th>Comment</th>
                    <th>Date Posted</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($comments as $comment): ?>
                    <tr>              
                        <td><?php echo ($comment['comment']); ?></td>
                        <td><?php echo ($comment['date_posted']); ?></td>
                        <td><a href="admin_dashboard.php?action=delete_comment&comment_id=<?php echo $comment['id']; ?>">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="scrollable-table">
            <h3>All Users</h3>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Account Type</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo ($user['username']); ?></td>
                        <td><?php echo ($user['email']); ?></td>
                        <td><?php echo ($user['account_type']); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <select onchange="updateAccountType(<?php echo $user['id']; ?>, this.value)">
                                    <option value="admin" <?php if ($user['account_type'] === 'admin') echo 'selected'; ?>>Admin</option>
                                    <option value="user" <?php if ($user['account_type'] === 'user') echo 'selected'; ?>>User</option>
                                </select>
                                <a href="admin_dashboard.php?action=delete_user&user_id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 The Recipe Community</p>
</footer>

<script>
 
</script>
</body>
</html>
