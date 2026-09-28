<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Comments";
$success = "";

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM replies  WHERE comment_id=?")->execute([$_GET['delete']]);
    $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([$_GET['delete']]);
    header("Location: comments.php?msg=deleted"); exit;
}

if (isset($_GET['msg'])&&$_GET['msg']=='deleted') $success="Comment deleted.";

$filter = $_GET['filter'] ?? 'all';

if ($filter=='recent') {
    $comments = $pdo->query("SELECT comments.*,users.username,users.photo,posts.title as post_title,posts.id as post_id FROM comments JOIN users ON comments.user_id=users.id JOIN posts ON comments.post_id=posts.id WHERE comments.created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY comments.created_at DESC")->fetchAll();
} else {
    $comments = $pdo->query("SELECT comments.*,users.username,users.photo,posts.title as post_title,posts.id as post_id FROM comments JOIN users ON comments.user_id=users.id JOIN posts ON comments.post_id=posts.id ORDER BY comments.created_at DESC")->fetchAll();
}

$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalReplies  = $pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();
$todayComments = $pdo->query("SELECT COUNT(*) FROM comments WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$weekComments  = $pdo->query("SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Comments — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header"><h1>Manage Comments</h1></div>

    <?php if ($success): ?><div class="alert alert-success"><span class="material-icons">check_circle</span><?= $success ?></div><?php endif; ?>

    <div class="stats-grid stats-grid-4" style="margin-bottom:20px">
      <div class="stat-card"><div class="stat-icon purple"><span class="material-icons">chat_bubble</span></div><div class="stat-info"><div class="num"><?= number_format($totalComments) ?></div><div class="lbl">Total</div></div></div>
      <div class="stat-card"><div class="stat-icon blue"><span class="material-icons">reply</span></div><div class="stat-info"><div class="num"><?= number_format($totalReplies) ?></div><div class="lbl">Replies</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><span class="material-icons">today</span></div><div class="stat-info"><div class="num"><?= $todayComments ?></div><div class="lbl">Today</div></div></div>
      <div class="stat-card"><div class="stat-icon orange"><span class="material-icons">date_range</span></div><div class="stat-info"><div class="num"><?= $weekComments ?></div><div class="lbl">This Week</div></div></div>
    </div>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px">
      <div class="filter-tabs" style="margin-bottom:0">
        <a href="comments.php?filter=all"    class="filter-tab <?= $filter=='all'?'active':'' ?>">All (<?= $totalComments ?>)</a>
        <a href="comments.php?filter=recent" class="filter-tab <?= $filter=='recent'?'active':'' ?>">Last 7 Days (<?= $weekComments ?>)</a>
      </div>
      <div class="search-wrap" style="max-width:260px;margin-left:auto">
        <span class="material-icons">search</span>
        <input type="text" id="searchInput" placeholder="Search comments..." onkeyup="filterTable()">
      </div>
    </div>

    <div class="admin-card">
      <?php if (count($comments)==0): ?>
        <div class="empty-state"><span class="material-icons">chat_bubble_outline</span><h3>No comments found</h3></div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table" id="commentsTable">
          <tr><th>User</th><th>Comment</th><th>On Blog</th><th>Date</th><th>Action</th></tr>
          <?php foreach ($comments as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="avatar avatar-sm">
                  <?php if ($c['photo']): ?><img src="../uploads/<?= htmlspecialchars($c['photo']) ?>" alt=""><?php else: ?><?= strtoupper(substr($c['username'],0,1)) ?><?php endif; ?>
                </div>
                <span style="font-weight:600;font-size:13px"><?= htmlspecialchars($c['username']) ?></span>
              </div>
            </td>
            <td style="max-width:260px">
              <div style="font-size:13px;color:var(--text);line-height:1.4;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars(substr($c['body'],0,80)) ?><?= strlen($c['body'])>80?'...':'' ?>
              </div>
            </td>
            <td>
              <a href="../user/post.php?id=<?= $c['post_id'] ?>"
                 style="font-size:12px;color:var(--primary);text-decoration:none;
                        font-weight:600;display:flex;align-items:center;gap:4px"
                 target="_blank">
                <span class="material-icons" style="font-size:14px">open_in_new</span>
                <?= htmlspecialchars(substr($c['post_title'],0,25)) ?>...
              </a>
            </td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
              <?= date('d M Y',strtotime($c['created_at'])) ?>
            </td>
            <td>
              <a href="comments.php?delete=<?= $c['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete comment and replies?')">
                <span class="material-icons">delete</span>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
  function filterTable() {
    var q=document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#commentsTable tr:not(:first-child)').forEach(function(r){
      r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
  }
</script>

</body>
</html>