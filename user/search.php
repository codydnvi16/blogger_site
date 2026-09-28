<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$results = [];
$query   = "";

// runs only when user types something and hits search
if (isset($_GET['q']) && $_GET['q'] != '') {

    $query = $_GET['q'];

    // search in title, content and domain
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id
        WHERE posts.status = 'published'
        AND (
            posts.title   LIKE ? OR
            posts.content LIKE ? OR
            posts.domain  LIKE ?
        )
        ORDER BY posts.created_at DESC
    ");

    // % means anything before or after the word
    $like = '%' . $query . '%';
    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search — MyBlog</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #f4f4f8;
      font-family: sans-serif;
      color: #1a1a2e;
    }

    nav {
      background: #1a1a2e;
      padding: 14px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    nav .logo { font-size: 22px; font-weight: bold; color: white; }
    nav .logo span { color: #7f77dd; }
    nav .links a { color: #ccc; text-decoration: none; margin-left: 20px; font-size: 14px; }
    nav .links a:hover { color: white; }

    .container {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 20px;
    }

    h2 { font-size: 24px; margin-bottom: 24px; }

    /* search bar */
    .search-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .search-bar input {
      flex: 1;
      padding: 13px 16px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
    }

    .search-bar input:focus {
      outline: none;
      border-color: #7f77dd;
    }

    .search-bar button {
      padding: 13px 24px;
      background: #7f77dd;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      cursor: pointer;
      font-weight: bold;
    }

    .search-bar button:hover { background: #534ab7; }

    /* result count */
    .result-info {
      font-size: 14px;
      color: #888;
      margin-bottom: 20px;
    }

    /* each result card */
    .card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }

    .card .domain {
      display: inline-block;
      background: #eeecfc;
      color: #7f77dd;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .card h3 { font-size: 18px; margin-bottom: 6px; }

    .card p {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 12px;
    }

    .card .meta { font-size: 12px; color: #aaa; margin-bottom: 10px; }

    .card a.read {
      font-size: 14px;
      color: #7f77dd;
      text-decoration: none;
      font-weight: bold;
    }

    .empty {
      text-align: center;
      padding: 50px;
      color: #aaa;
      background: white;
      border-radius: 12px;
    }
  </style>
</head>
<body>

  <nav>
    <div class="logo"><span>Blog</span></div>
    <div class="links">
      <a href="home.php">🏠 Home</a>
      <a href="write-blog.php">+ Write Blog</a>
      <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>

  <div class="container">

    <h2>🔍 Search Blogs</h2>

    <!-- search form -->
    <form method="GET">
      <div class="search-bar">
        <input type="text" name="q"
               value="<?= htmlspecialchars($query) ?>"
               placeholder="Search by title, category or keyword...">
        <button type="submit">Search</button>
      </div>
    </form>

    <!-- show results only if user searched -->
    <?php if ($query != ''): ?>

      <p class="result-info">
        <?= count($results) ?> result(s) found for
        "<strong><?= htmlspecialchars($query) ?></strong>"
      </p>

      <?php if (count($results) == 0): ?>
        <div class="empty">No blogs found. Try a different keyword.</div>

      <?php else: ?>

        <?php foreach ($results as $p): ?>
        <div class="card">
          <span class="domain"><?= htmlspecialchars($p['domain']) ?></span>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <p><?= htmlspecialchars(substr($p['content'], 0, 150)) ?>...</p>
          <div class="meta">
            By <strong><?= htmlspecialchars($p['username']) ?></strong>
            • <?= date('d M Y', strtotime($p['created_at'])) ?>
          </div>
          <a class="read" href="post.php?id=<?= $p['id'] ?>">Read more →</a>
        </div>
        <?php endforeach; ?>

      <?php endif; ?>

    <?php endif; ?>

  </div>

</body>
</html>