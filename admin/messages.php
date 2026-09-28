<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Messages";

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([$_GET['delete']]);
    header("Location: messages.php"); exit;
}

$messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Messages — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>Contact Messages</h1>
      <span style="font-size:13px;color:var(--text-muted)"><?= count($messages) ?> messages</span>
    </div>

    <?php if (count($messages)==0): ?>
      <div class="admin-card">
        <div class="empty-state">
          <span class="material-icons">mail</span>
          <h3>No messages yet</h3>
          <p>Contact form submissions will appear here.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($messages as $m): ?>
        <div class="admin-card">
          <div class="admin-card-body">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;
                        gap:16px;flex-wrap:wrap">
              <div style="flex:1">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                  <div class="avatar avatar-md">
                    <?= strtoupper(substr($m['name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($m['name']) ?></div>
                    <div style="font-size:12px;color:var(--primary)"><?= htmlspecialchars($m['email']) ?></div>
                  </div>
                  <div style="margin-left:auto;font-size:11px;color:var(--text-muted)">
                    <?= date('d M Y, h:i A',strtotime($m['created_at'])) ?>
                  </div>
                </div>
                <div style="font-size:14px;color:var(--text);line-height:1.6;padding:14px;
                            background:var(--bg-input);border-radius:6px;
                            border-left:3px solid var(--primary)">
                  <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
              </div>
              <a href="messages.php?delete=<?= $m['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete this message?')" style="flex-shrink:0">
                <span class="material-icons">delete</span>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>