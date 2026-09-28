<?php
session_name('admin_session');
session_start();
require '../include/database.php';

if(!isset($_SESSION['user_id'])){header("Location: login.php");exit;}

// toggle bookmark
if(isset($_GET['toggle'])){
    $pid=$_GET['toggle'];
    $check=$pdo->prepare("SELECT id FROM bookmarks WHERE post_id=? AND user_id=?");
    $check->execute([$pid,$_SESSION['user_id']]);
    if($check->fetch()){
        $pdo->prepare("DELETE FROM bookmarks WHERE post_id=? AND user_id=?")->execute([$pid,$_SESSION['user_id']]);
    } else {
        $pdo->prepare("INSERT IGNORE INTO bookmarks (post_id,user_id) VALUES (?,?)")->execute([$pid,$_SESSION['user_id']]);
    }
    // go back to where user came from
    $ref=$_GET['ref'] ?? 'bookmarks.php';
    header("Location: $ref"); exit;
}

$bookmarks=$pdo->prepare("
    SELECT posts.*,users.username
    FROM bookmarks
    JOIN posts ON bookmarks.post_id=posts.id
    JOIN users ON posts.user_id=users.id
    WHERE bookmarks.user_id=?
    ORDER BY bookmarks.created_at DESC
");
$bookmarks->execute([$_SESSION['user_id']]);
$bookmarks=$bookmarks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>Bookmarks — MyBlog</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:#f4f4f8;font-family:sans-serif;color:#1a1a2e}
    nav{background:#1a1a2e;padding:14px 30px;display:flex;justify-content:space-between;align-items:center}
    nav .logo{font-size:22px;font-weight:bold;color:white}
    nav .logo span{color:#7f77dd}
    nav .links a{color:#ccc;text-decoration:none;margin-left:20px;font-size:14px}
    .container{max-width:900px;margin:40px auto;padding:0 20px}
    h2{font-size:24px;margin-bottom:20px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
    .card{background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);position:relative}
    .card img{width:100%;height:150px;object-fit:cover}
    .card .no-img{width:100%;height:150px;background:#eeecfc;display:flex;align-items:center;justify-content:center;font-size:30px}
    .card .info{padding:14px}
    .card .info h3{font-size:15px;margin-bottom:6px}
    .card .info .meta{font-size:12px;color:#aaa;margin-bottom:10px}
    .card .info .actions{display:flex;gap:8px}
    .btn-read{padding:7px 14px;background:#7f77dd;color:white;border-radius:6px;text-decoration:none;font-size:12px;font-weight:bold}
    .btn-remove{padding:7px 14px;background:#fde8e8;color:#e74c3c;border-radius:6px;text-decoration:none;font-size:12px;font-weight:bold}
    .empty{text-align:center;padding:60px;color:#aaa;background:white;border-radius:12px}
  </style>
</head>
<body>
  <nav>
    <div class="logo">My<span>Blog</span></div>
    <div class="links">
      <a href="home.php">🏠 Home</a>
      <a href="profile.php">👤 <?=htmlspecialchars($_SESSION['username'])?></a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>
  <div class="container">
    <h2>🔖 Saved Blogs</h2>
    <?php if(count($bookmarks)==0): ?>
      <div class="empty">No saved blogs yet.<br><br><a href="home.php" style="color:#7f77dd;font-weight:bold">Browse blogs →</a></div>
    <?php else: ?>
    <div class="grid">
      <?php foreach($bookmarks as $p): ?>
      <div class="card">
        <?php if($p['image']): ?>
          <img src="../uploads/<?=htmlspecialchars($p['image'])?>" alt="">
        <?php else: ?>
          <div class="no-img">📝</div>
        <?php endif; ?>
        <div class="info">
          <h3><?=htmlspecialchars($p['title'])?></h3>
          <div class="meta">By <?=htmlspecialchars($p['username'])?> • <?=htmlspecialchars($p['domain'])?></div>
          <div class="actions">
            <a href="post.php?id=<?=$p['id']?>" class="btn-read">Read</a>
            <a href="bookmarks.php?toggle=<?=$p['id']?>&ref=bookmarks.php" class="btn-remove">🗑 Remove</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>