<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Categories";
$success=""; $error="";

if (isset($_POST['add'])) {
    $name=trim($_POST['name']);
    if ($name) {
        try { $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]); $success="Category added!"; }
        catch(Exception $e) { $error="Category already exists."; }
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$_GET['delete']]);
    header("Location: categories.php?msg=deleted"); exit;
}

if (isset($_GET['msg'])&&$_GET['msg']=='deleted') $success="Category deleted.";

$categories = $pdo->query("
    SELECT categories.*, COUNT(posts.id) as post_count
    FROM categories
    LEFT JOIN posts ON posts.domain=categories.name AND posts.status='published'
    GROUP BY categories.id ORDER BY post_count DESC
")->fetchAll();

$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Categories — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>Manage Categories</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><span class="material-icons">check_circle</span><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><span class="material-icons">error</span><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start">

      <!-- add form -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">add_circle</span> Add Category</div>
        </div>
        <div class="admin-card-body">
          <form method="POST">
            <div class="form-group">
              <label class="form-label">Category Name</label>
              <div class="input-wrap">
                <span class="material-icons">folder</span>
                <input type="text" name="name" placeholder="e.g. Sports, Health" required>
              </div>
            </div>
            <button type="submit" name="add" class="btn btn-primary btn-block">
              <span class="material-icons">add</span> Add Category
            </button>
          </form>
          <div style="margin-top:16px;padding:12px;background:var(--bg-input);border-radius:6px;
                      font-size:12px;color:var(--text-muted);line-height:1.5">
            <span class="material-icons" style="font-size:14px;vertical-align:middle;color:var(--primary)">info</span>
            Deleting a category won't delete blogs in it.
          </div>
        </div>
      </div>

      <!-- categories table -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">folder</span> All Categories (<?= count($categories) ?>)</div>
          <div class="search-wrap" style="max-width:200px">
            <span class="material-icons">search</span>
            <input type="text" id="searchInput" placeholder="Search..." onkeyup="filterTable()">
          </div>
        </div>
        <?php if (count($categories)==0): ?>
          <div class="empty-state"><span class="material-icons">folder_off</span><h3>No categories yet</h3></div>
        <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table" id="catTable">
            <tr><th>Category</th><th>Blogs</th><th>Share</th><th>Action</th></tr>
            <?php foreach ($categories as $c):
              $pct = $totalPosts>0 ? round(($c['post_count']/$totalPosts)*100) : 0;
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="width:32px;height:32px;background:var(--primary-light);border-radius:6px;
                              display:flex;align-items:center;justify-content:center">
                    <span class="material-icons" style="font-size:16px;color:var(--primary)">folder</span>
                  </div>
                  <span style="font-weight:600;font-size:13px"><?= htmlspecialchars($c['name']) ?></span>
                </div>
              </td>
              <td>
                <span class="badge badge-primary"><?= $c['post_count'] ?> blogs</span>
              </td>
              <td style="min-width:140px">
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="progress-bar" style="flex:1">
                    <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span style="font-size:11px;color:var(--text-muted);width:32px"><?= $pct ?>%</span>
                </div>
              </td>
              <td>
                <a href="categories.php?delete=<?= $c['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete \'<?= htmlspecialchars($c['name']) ?>\'?')">
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
</div>

<script>
  function filterTable() {
    var q=document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#catTable tr:not(:first-child)').forEach(function(r){
      r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
  }
</script>

</body>
</html>