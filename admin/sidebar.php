<?php
// get counts for badges
$pendingCount  = $pdo->query("SELECT COUNT(*) FROM posts    WHERE status='pending'")->fetchColumn();
$reportsCount  = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$messagesCount = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">

  <!-- logo -->
  <div class="sidebar-logo">
    <span class="logo-text">MY<span>BLOG</span></span>
    <span class="logo-sub">Admin Panel</span>
  </div>

  <!-- nav -->
  <nav class="sidebar-nav">

    <div class="sidebar-section">Main</div>

    <a href="dashboard.php"
       class="sidebar-link <?= $currentPage=='dashboard.php'?'active':'' ?>">
      <span class="material-icons">dashboard</span>
      Dashboard
    </a>

    <a href="profile.php"
       class="sidebar-link <?= $currentPage=='profile.php'?'active':'' ?>">
      <span class="material-icons">account_circle</span>
      My Profile
    </a>

    <div class="sidebar-section">Content</div>

    <a href="blogs.php"
       class="sidebar-link <?= $currentPage=='blogs.php'?'active':'' ?>">
      <span class="material-icons">article</span>
      Manage Blogs
      <?php if ($pendingCount > 0): ?>
        <span class="badge-count"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>

    <a href="categories.php"
       class="sidebar-link <?= $currentPage=='categories.php'?'active':'' ?>">
      <span class="material-icons">folder</span>
      Categories
    </a>

    <a href="comments.php"
       class="sidebar-link <?= $currentPage=='comments.php'?'active':'' ?>">
      <span class="material-icons">chat_bubble</span>
      Comments
    </a>

    <a href="reports.php"
       class="sidebar-link <?= $currentPage=='reports.php'?'active':'' ?>">
      <span class="material-icons">flag</span>
      Reports
      <?php if ($reportsCount > 0): ?>
        <span class="badge-count"><?= $reportsCount ?></span>
      <?php endif; ?>
    </a>

    <div class="sidebar-section">Users</div>

    <a href="users.php"
       class="sidebar-link <?= $currentPage=='users.php'?'active':'' ?>">
      <span class="material-icons">group</span>
      Manage Users
    </a>

    <a href="subscriptions.php"
       class="sidebar-link <?= $currentPage=='subscriptions.php'?'active':'' ?>">
      <span class="material-icons">workspace_premium</span>
      Subscriptions
    </a>

    <div class="sidebar-section">Other</div>

    <a href="messages.php"
       class="sidebar-link <?= $currentPage=='messages.php'?'active':'' ?>">
      <span class="material-icons">mail</span>
      Messages
      <?php if ($messagesCount > 0): ?>
        <span class="badge-count"><?= $messagesCount ?></span>
      <?php endif; ?>
    </a>

    <a href="../user/home.php" class="sidebar-link" target="_blank">
      <span class="material-icons">open_in_new</span>
      View Site
    </a>

    <a href="logout.php" class="sidebar-link danger">
      <span class="material-icons">logout</span>
      Logout
    </a>

  </nav>

  <!-- user info at bottom -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar avatar-sm">
        <?php
        $adminUser = $pdo->prepare("SELECT photo,username FROM users WHERE id=?");
        $adminUser->execute([$_SESSION['user_id']]);
        $adminUser = $adminUser->fetch();
        ?>
        <?php if ($adminUser['photo']): ?>
          <img src="../uploads/<?= htmlspecialchars($adminUser['photo']) ?>" alt="">
        <?php else: ?>
          <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div class="info">
        <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="role">Administrator</div>
      </div>
    </div>
  </div>

</aside>