<!DOCTYPE html>
<html lang="en">
<head>
  <!-- ... your head elements ... -->
</head>
<body>
  <!-- ... your navigation bar ... -->

  <!-- Main Content -->
  <main>
    <form action="submit_recipe.php" method="POST">
      <label for="title">Recipe Title:</label>
      <input type="text" id="title" name="title" required>

      <label for="content">Recipe Content:</label>
      <textarea id="content" name="content" required></textarea>

      <input type="submit" value="Submit Recipe">
    </form>
  </main>

  <!-- ... your footer ... -->

  <script src="script.js"></script>
</body>
</html>

<?php
// Make sure to check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// If the request method is POST, handle the recipe submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // Input validation and sanitization
    $title = filter_var($title, FILTER_SANITIZE_STRING);
    $content = filter_var($content, FILTER_SANITIZE_STRING);

    // Insert the recipe into the database
    $stmt = $db->prepare("INSERT INTO recipes (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $title, $content]);

    // Redirect to the user's profile or to the new recipe page
    header("Location: recipe.php?id=" . $db->lastInsertId());
    exit;
}
?>
