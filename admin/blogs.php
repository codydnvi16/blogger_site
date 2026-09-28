<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Manage Blogs";

if (isset($_GET['action'])&&isset($_GET['id'])) {
    $bid=$_GET['id'];
    switch($_GET['action']) {
        case 'approve':   $pdo->prepare("UPDATE posts SET status='published' WHERE id=?")->execute([$bid]); break;
        case 'reject':    $pdo->prepare("UPDATE posts SET status='rejected'  WHERE id=?")->execute([$bid]); break;
        case 'feature':   $pdo->prepare("UPDATE posts SET featured=1 WHERE id=?")->execute([$bid]); break;
        case 'unfeature': $pdo->prepare("UPDATE posts SET featured=0 WHERE id=?")->execute([$bid]); break;
        case 'delete':
            $pdo->prepare("DELETE FROM comments  WHERE post_id=?")->execute([$bid]);
            $pdo->prepare("DELETE FROM likes     WHERE post_id=?")->execute([$bid]);
            $pdo->prepare("DELETE FROM reports   WHERE post_id=?")->execute([$bid]);
            $pdo->prepare("DELETE FROM bookmarks WHERE post_id=?")->execute([$bid]);
            $pdo->prepare("DELETE FROM posts     WHERE id=?")->execute([$bid]);
            break;
    }
    header("Location: blogs.php"); exit;
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter!='all' ? "WHERE posts.status='$filter'" : "";
$blogs  = $pdo->query("SELECT posts.*,users.username FROM posts JOIN users ON posts.user_id=users.id $where ORDER BY posts.created_at DESC")->fetchAll();

$counts = [
    'all'       => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'pending'   => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
    'rejected'  => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='rejected'")->fetchColumn(),
    'draft'     => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Manage Blogs — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>Manage Blogs</h1>
    </div>

    <!-- filter tabs -->
    <div class="filter-tabs">
      <?php foreach([
        ['all','All',$counts['all'],'article'],
        ['pending','Pending',$counts['pending'],'pending'],
        ['published','Published',$counts['published'],'check_circle'],
        ['rejected','Rejected',$counts['rejected'],'cancel'],
        ['draft','Drafts',$counts['draft'],'save'],
      ] as [$val,$label,$count,$icon]): ?>
      <a href="blogs.php?filter=<?= $val ?>"
         class="filter-tab <?= $filter==$val?'active':'' ?>">
        <span class="material-icons"><?= $icon ?></span>
        <?= $label ?> (<?= $count ?>)
      </a>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <span class="material-icons">article</span>
          <?= count($blogs) ?> blogs found
        </div>
        <div class="search-wrap" style="max-width:260px">
          <span class="material-icons">search</span>
          <input type="text" id="searchInput" placeholder="Search blogs..." onkeyup="filterTable()">
        </div>
      </div>

      <?php if (count($blogs)==0): ?>
        <div class="empty-state">
          <span class="material-icons">article</span>
          <h3>No blogs found</h3>
          <p>No blogs match the current filter.</p>
        </div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table" id="blogsTable">
          <tr>
            <th>Blog</th>
            <th>Author</th>
            <th>Category</th>
            <th>Views</th>
            <th>Status</th>
            <th>Featured</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
          <?php foreach ($blogs as $b): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <?php if ($b['image']): ?>
                  <img src="../uploads/<?= htmlspecialchars($b['image']) ?>"
                       style="width:40px;height:34px;object-fit:cover;border-radius:4px;flex-shrink:0" alt="">
                <?php else: ?>
                  <div style="width:40px;height:34px;background:var(--bg-input);border-radius:4px;
                              flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    <span class="material-icons" style="font-size:16px;color:var(--text-muted)">article</span>
                  </div>
                <?php endif; ?>
                <div style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                            font-weight:600;font-size:13px">
                  <?= htmlspecialchars($b['title']) ?>
                </div>
              </div>
            </td>
            <td style="font-size:12px;color:var(--text-sub)"><?= htmlspecialchars($b['username']) ?></td>
            <td>
              <?php if ($b['domain']): ?>
                <span class="badge badge-primary"><?= htmlspecialchars($b['domain']) ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:12px">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-sub)">
              <span style="display:flex;align-items:center;gap:3px">
                <span class="material-icons" style="font-size:14px">visibility</span>
                <?= number_format($b['views']) ?>
              </span>
            </td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            <td>
              <?= $b['featured']
                ? '<span style="color:#f59e0b;font-size:12px;font-weight:700;display:flex;align-items:center;gap:3px"><span class="material-icons" style="font-size:15px">star</span>Yes</span>'
                : '<span style="color:var(--text-muted);font-size:12px">—</span>' ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
              <?= date('d M Y',strtotime($b['created_at'])) ?>
            </td>
            <td>
              <div style="display:flex;gap:4px;flex-wrap:wrap">
                <?php if ($b['status']=='pending'): ?>
                  <a href="blogs.php?action=approve&id=<?= $b['id'] ?>" class="btn btn-success btn-sm">
                    <span class="material-icons">check</span> Approve
                  </a>
                  <a href="blogs.php?action=reject&id=<?= $b['id'] ?>" class="btn btn-danger btn-sm">
                    <span class="material-icons">close</span> Reject
                  </a>
                <?php endif; ?>
                <?php if ($b['featured']): ?>
                  <a href="blogs.php?action=unfeature&id=<?= $b['id'] ?>" class="btn btn-warning btn-sm">
                    <span class="material-icons">star_border</span>
                  </a>
                <?php else: ?>
                  <a href="blogs.php?action=feature&id=<?= $b['id'] ?>" class="btn btn-warning btn-sm">
                    <span class="material-icons">star</span>
                  </a>
                <?php endif; ?>
                <a href="../user/post.php?id=<?= $b['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                  <span class="material-icons">visibility</span>
                </a>
                <a href="blogs.php?action=delete&id=<?= $b['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this blog permanently?')">
                  <span class="material-icons">delete</span>
                </a>
              </div>
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
    var q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#blogsTable tr:not(:first-child)').forEach(function(r) {
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }
</script>

</body>
</html>