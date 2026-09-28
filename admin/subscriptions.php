<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Subscriptions";

if (isset($_GET['action'])&&isset($_GET['id'])) {
    $uid=$_GET['id'];
    if ($_GET['action']=='grant') {
        $pdo->prepare("UPDATE users SET is_premium=1 WHERE id=?")->execute([$uid]);
        $pdo->prepare("INSERT INTO subscriptions (user_id,plan) VALUES (?,?) ON DUPLICATE KEY UPDATE plan='premium'")->execute([$uid,'premium']);
    } else {
        $pdo->prepare("UPDATE users SET is_premium=0 WHERE id=?")->execute([$uid]);
        $pdo->prepare("INSERT INTO subscriptions (user_id,plan) VALUES (?,?) ON DUPLICATE KEY UPDATE plan='free'")->execute([$uid,'free']);
    }
    header("Location: subscriptions.php"); exit;
}

$users = $pdo->query("SELECT users.*,subscriptions.plan,subscriptions.started_at FROM users LEFT JOIN subscriptions ON subscriptions.user_id=users.id ORDER BY users.is_premium DESC,users.created_at DESC")->fetchAll();
$premiumCount = $pdo->query("SELECT COUNT(*) FROM users WHERE is_premium=1")->fetchColumn();
$freeCount    = $pdo->query("SELECT COUNT(*) FROM users WHERE is_premium=0")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$premiumPct   = $totalUsers>0 ? round(($premiumCount/$totalUsers)*100) : 0;
$filter = $_GET['filter'] ?? 'all';
$filtered = array_filter($users, function($u) use ($filter) {
    if ($filter=='premium') return $u['is_premium']==1;
    if ($filter=='free')    return $u['is_premium']==0;
    return true;
});
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Subscriptions — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header"><h1>Subscriptions</h1></div>

    <!-- stats -->
    <div class="stats-grid stats-grid-4" style="margin-bottom:20px">
      <div class="stat-card">
        <div class="stat-icon orange"><span class="material-icons">workspace_premium</span></div>
        <div class="stat-info"><div class="num"><?= $premiumCount ?></div><div class="lbl">Premium Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><span class="material-icons">person</span></div>
        <div class="stat-info"><div class="num"><?= $freeCount ?></div><div class="lbl">Free Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><span class="material-icons">group</span></div>
        <div class="stat-info"><div class="num"><?= $totalUsers ?></div><div class="lbl">Total Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><span class="material-icons">percent</span></div>
        <div class="stat-info"><div class="num"><?= $premiumPct ?>%</div><div class="lbl">Premium Rate</div></div>
      </div>
    </div>

    <!-- adoption meter -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <span style="font-size:13px;font-weight:700;color:var(--text)">Premium Adoption</span>
          <span style="font-size:13px;color:var(--primary);font-weight:700"><?= $premiumPct ?>% of users are premium</span>
        </div>
        <div class="progress-bar" style="height:8px">
          <div class="progress-fill" style="width:<?= $premiumPct ?>%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:12px;color:var(--text-muted)">
          <span><?= $premiumCount ?> Premium</span>
          <span><?= $freeCount ?> Free</span>
        </div>
      </div>
    </div>

    <!-- filter -->
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px">
      <div class="filter-tabs" style="margin-bottom:0">
        <a href="subscriptions.php?filter=all"     class="filter-tab <?= $filter=='all'?'active':'' ?>">All (<?= $totalUsers ?>)</a>
        <a href="subscriptions.php?filter=premium" class="filter-tab <?= $filter=='premium'?'active':'' ?>">💎 Premium (<?= $premiumCount ?>)</a>
        <a href="subscriptions.php?filter=free"    class="filter-tab <?= $filter=='free'?'active':'' ?>">Free (<?= $freeCount ?>)</a>
      </div>
    </div>

    <div class="admin-card">
      <?php if (count($filtered)==0): ?>
        <div class="empty-state"><span class="material-icons">workspace_premium</span><h3>No users found</h3></div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <tr><th>User</th><th>Plan</th><th>Member Since</th><th>Premium Since</th><th>Action</th></tr>
          <?php foreach ($filtered as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar avatar-sm">
                  <?php if ($u['photo']): ?><img src="../uploads/<?= htmlspecialchars($u['photo']) ?>" alt=""><?php else: ?><?= strtoupper(substr($u['username'],0,1)) ?><?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($u['username']) ?></div>
                  <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['is_premium']): ?>
                <span class="badge badge-premium">💎 Premium</span>
              <?php else: ?>
                <span class="badge badge-user">Free</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= $u['started_at'] ? date('d M Y',strtotime($u['started_at'])) : '—' ?></td>
            <td>
              <?php if ($u['is_premium']): ?>
                <a href="subscriptions.php?action=revoke&id=<?= $u['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Revoke premium?')">
                  <span class="material-icons">remove_circle</span> Revoke
                </a>
              <?php else: ?>
                <a href="subscriptions.php?action=grant&id=<?= $u['id'] ?>"
                   class="btn btn-warning btn-sm">
                  <span class="material-icons">workspace_premium</span> Grant
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>