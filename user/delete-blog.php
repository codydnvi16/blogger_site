<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id) {
    header("Location: profile.php");
    exit;
}

// make sure blog belongs to this user
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND user_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: profile.php");
    exit;
}

// if confirmed delete
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {

    // delete replies of comments
    $cs = $pdo->prepare("SELECT id FROM comments WHERE post_id=?");
    $cs->execute([$id]);
    foreach ($cs->fetchAll() as $c) {
        $pdo->prepare("DELETE FROM replies WHERE comment_id=?")->execute([$c['id']]);
    }

    // delete all related data
    $pdo->prepare("DELETE FROM comments      WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM likes         WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM bookmarks     WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM reports       WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM notifications WHERE post_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM posts         WHERE id=?")->execute([$id]);

    header("Location: profile.php?msg=deleted");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Blog — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div style="min-height:80vh;display:flex;align-items:center;
              justify-content:center;padding:24px">

    <div style="background:var(--bg-card);border:1px solid var(--border);
                border-radius:8px;padding:40px;max-width:480px;
                width:100%;text-align:center;box-shadow:var(--shadow-lg)">

      <!-- icon -->
      <div style="width:64px;height:64px;background:#fee2e2;border-radius:50%;
                  display:flex;align-items:center;justify-content:center;
                  margin:0 auto 20px">
        <span class="material-icons" style="font-size:32px;color:#dc2626">delete</span>
      </div>

      <h2 style="font-size:20px;font-weight:800;color:var(--text);margin-bottom:8px">
        Delete This Blog?
      </h2>

      <p style="font-size:14px;color:var(--text-sub);line-height:1.6;margin-bottom:8px">
        You are about to delete:
      </p>

      <div style="background:var(--bg-input);border-radius:6px;padding:14px;
                  margin-bottom:20px;border-left:3px solid #dc2626">
        <p style="font-size:14px;font-weight:700;color:var(--text)">
          "<?= htmlspecialchars($post['title']) ?>"
        </p>
      </div>

      <p style="font-size:13px;color:#dc2626;font-weight:600;margin-bottom:24px">
        ⚠️ This action cannot be undone. All comments, likes and bookmarks
        for this blog will also be deleted.
      </p>

      <div style="display:flex;gap:12px;justify-content:center">
        <a href="delete-blog.php?id=<?= $id ?>&confirm=yes"
           class="btn btn-danger btn-lg">
          <span class="material-icons">delete</span>
          Yes, Delete It
        </a>
        <a href="profile.php" class="btn btn-secondary btn-lg">
          <span class="material-icons">close</span>
          Cancel
        </a>
      </div>

    </div>
  </div>

</body>
</html>