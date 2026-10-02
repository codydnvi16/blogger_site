<?php
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $unread->execute([$_SESSION['user_id']]);
    $unreadCount = $unread->fetchColumn();
}

// get categories for nav
$navCats = $pdo->query("
    SELECT DISTINCT domain FROM posts
    WHERE status='published' AND domain != ''
    ORDER BY domain ASC
    LIMIT 6
")->fetchAll(PDO::FETCH_COLUMN);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">
<script src="../assets/theme.js"></script>

<nav class="navbar">

  <!-- orange top bar -->
  <div class="navbar-top">
    <?= date('l, F j, Y') ?> &nbsp;·&nbsp; Share Your Story With The World
  </div>

  <!-- main navbar -->
  <div class="navbar-main">

    <!-- logo -->
    <a href="home.php" class="logo">
      MY<span>BLOG</span>
    </a>

    <!-- search bar -->
    <div class="search-wrap">
      <span class="material-icons">search</span>
      <input type="text"
             placeholder="Search what you likes..."
             onkeydown="if(event.key==='Enter') window.location.href='search.php?q='+this.value">
    </div>

    <!-- right links -->
    <div class="nav-links">

      <?php if (isset($_SESSION['user_id'])): ?>

        <a href="write-blog.php" class="btn-write">
          <span class="material-icons">edit</span>
          Write
        </a>

        <div class="notif-wrap">
          <a href="notifications.php" style="padding:7px">
            <span class="material-icons">notifications</span>
            <?php if ($unreadCount > 0): ?>
              <span class="notif-badge"></span>
            <?php endif; ?>
          </a>
        </div>

        <a href="bookmarks.php" style="padding:7px">
          <span class="material-icons">bookmark_border</span>
        </a>

        <a href="profile.php" style="padding:7px">
          <span class="material-icons">account_circle</span>
        </a>

        <a href="logout.php" style="padding:7px">
          <span class="material-icons">logout</span>
        </a>

      <?php else: ?>

        <a href="login.php">Sign In</a>
        <a href="login.php" class="btn-write">Get Started</a>

      <?php endif; ?>

      <!-- dark/light toggle -->
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
        <span class="material-icons">dark_mode</span>
      </button>

    </div>
  </div>

  <!-- category nav bar -->
  <div class="navbar-cats">
    <a href="home.php" class="<?= $currentPage=='home.php' && !isset($_GET['category']) ? 'active' : '' ?>">
      All
    </a>
    <?php foreach ($navCats as $cat): ?>
      <a href="home.php?category=<?= urlencode($cat) ?>"
         class="<?= ($_GET['category'] ?? '') == $cat ? 'active' : '' ?>">
        <?= htmlspecialchars($cat) ?>
      </a>
    <?php endforeach; ?>
    <a href="following-feed.php" style="margin-left:auto">Following</a>
  </div>

</nav>