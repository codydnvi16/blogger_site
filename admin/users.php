<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Manage Users";

if (isset($_GET['action'])&&isset($_GET['id'])) {
    $uid=$_GET['id'];
    if ($uid!=$_SESSION['user_id']) {
        switch($_GET['action']) {
            case 'ban':            $pdo->prepare("UPDATE users SET is_banned=1  WHERE id=?")->execute([$uid]); break;
            case 'unban':          $pdo->prepare("UPDATE users SET is_banned=0  WHERE id=?")->execute([$uid]); break;
            case 'make_admin':     $pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$uid]); break;
            case 'remove_admin':   $pdo->prepare("UPDATE users SET role='user'  WHERE id=?")->execute([$uid]); break;
            case 'grant_premium':  $pdo->prepare("UPDATE users SET is_premium=1 WHERE id=?")->execute([$uid]); break;
            case 'revoke_premium': $pdo->prepare("UPDATE users SET is_premium=0 WHERE id=?")->execute([$uid]); break;
            case 'delete':
                $cs=$pdo->prepare("SELECT id FROM comments WHERE user_id=?"); $cs->execute([$uid]);
                foreach($cs->fetchAll() as $c) { $pdo->prepare("DELETE FROM replies WHERE comment_id=?")->execute([$c['id']]); }
                foreach(['posts','comments','likes','bookmarks','notifications'] as $t) {
                    $pdo->prepare("DELETE FROM $t WHERE user_id=?")->execute([$uid]);
                }
                $pdo->prepare("DELETE FROM follows WHERE follower_id=? OR following_id=?")->execute([$uid,$uid]);
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
                break;
        }
    }
    header("Location: users.php"); exit;
}

$filter = $_GET['filter'] ?? 'all';
$users  = $pdo->query("
    SELECT users.*,
    (SELECT COUNT(*) FROM posts   WHERE posts.user_id=users.id) as blog_count,
    (SELECT COUNT(*) FROM follows WHERE follows.following_id=users.id) as followers
    FROM users ORDER BY users.created_at DESC
")->fetchAll();

$filtered = array_filter($users, function($u) use ($filter) {
    if ($filter=='admin')   return $u['role']=='admin';
    if ($filter=='premium') return $u['is_premium']==1;
    if ($filter=='banned')  return $u['is_banned']==1;
    return true;
});

$totalUsers   = count($users);
$adminCount   = count(array_filter($users, fn($u)=>$u['role']=='admin'));
$premiumCount = count(array_filter($users, fn($u)=>$u['is_premium']==1));
$bannedCount  = count(array_filter($users, fn($u)=>$u['is_banned']==1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Manage Users — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>Manage Users</h1>
    </div>

    <!-- stat cards -->
    <div class="stats-grid stats-grid-4" style="margin-bottom:20px">
      <div class="stat-card">
        <div class="stat-icon blue"><span class="material-icons">group</span></div>
        <div class="stat-info"><div class="num"><?= $totalUsers ?></div><div class="lbl">Total Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><span class="material-icons">admin_panel_settings</span></div>
        <div class="stat-info"><div class="num"><?= $adminCount ?></div><div class="lbl">Admins</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><span class="material-icons">workspace_premium</span></div>
        <div class="stat-info"><div class="num"><?= $premiumCount ?></div><div class="lbl">Premium</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><span class="material-icons">block</span></div>
        <div class="stat-info"><div class="num"><?= $bannedCount ?></div><div class="lbl">Banned</div></div>
      </div>
    </div>

    <!-- filter + search -->
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
      <div class="filter-tabs" style="margin-bottom:0">
        <a href="users.php?filter=all"     class="filter-tab <?= $filter=='all'?'active':'' ?>">All (<?= $totalUsers ?>)</a>
        <a href="users.php?filter=admin"   class="filter-tab <?= $filter=='admin'?'active':'' ?>">Admins</a>
        <a href="users.php?filter=premium" class="filter-tab <?= $filter=='premium'?'active':'' ?>">Premium</a>
        <a href="users.php?filter=banned"  class="filter-tab <?= $filter=='banned'?'active':'' ?>">Banned</a>
      </div>
      <div class="search-wrap" style="max-width:260px;margin-left:auto">
        <span class="material-icons">search</span>
        <input type="text" id="searchInput" placeholder="Search users..." onkeyup="filterTable()">
      </div>
    </div>

    <div class="admin-card">
      <?php if (count($filtered)==0): ?>
        <div class="empty-state">
          <span class="material-icons">person_off</span>
          <h3>No users found</h3>
        </div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table" id="usersTable">
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Blogs</th>
            <th>Followers</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
          <?php foreach ($filtered as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar avatar-sm">
                  <?php if ($u['photo']): ?><img src="../uploads/<?= htmlspecialchars($u['photo']) ?>" alt=""><?php else: ?><?= strtoupper(substr($u['username'],0,1)) ?><?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:13px">
                    <?= htmlspecialchars($u['username']) ?>
                    <?= $u['is_premium']?'<span style="color:#f59e0b;font-size:11px">💎</span>':'' ?>
                  </div>
                  <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="font-size:12px;color:var(--text-sub)"><?= $u['blog_count'] ?></td>
            <td style="font-size:12px;color:var(--text-sub)"><?= $u['followers'] ?></td>
            <td>
              <?php if ($u['is_banned']): ?>
                <span class="badge badge-banned">Banned</span>
              <?php else: ?>
                <span class="badge badge-active">Active</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
              <?= date('d M Y',strtotime($u['created_at'])) ?>
            </td>
            <td>
              <?php if ($u['id']!=$_SESSION['user_id']): ?>
              <div style="display:flex;gap:4px;flex-wrap:wrap">
                <a href="../user/author.php?id=<?= $u['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                  <span class="material-icons">person</span>
                </a>
                <?php if ($u['is_banned']): ?>
                  <a href="users.php?action=unban&id=<?= $u['id'] ?>" class="btn btn-success btn-sm">Unban</a>
                <?php else: ?>
                  <a href="users.php?action=ban&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm">Ban</a>
                <?php endif; ?>
                <?php if ($u['role']=='admin'): ?>
                  <a href="users.php?action=remove_admin&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Remove Admin</a>
                <?php else: ?>
                <!--  <a href="users.php?action=make_admin&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Make Admin</a> !-->
                <?php endif; ?>
              <!--  <?php if ($u['is_premium']): ?>
                  <a href="users.php?action=revoke_premium&id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Revoke 💎</a>
                <?php else: ?>
                  <a href="users.php?action=grant_premium&id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Grant 💎</a>
                <?php endif; ?> !-->
                <a href="users.php?action=delete&id=<?= $u['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete user and all content?')">
                  <span class="material-icons">delete</span>
                </a>
              </div>
              <?php else: ?><span style="font-size:12px;color:var(--text-muted);font-style:italic">You</span><?php endif; ?>
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
    document.querySelectorAll('#usersTable tr:not(:first-child)').forEach(function(r) {
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }
</script>

</body>
</html>