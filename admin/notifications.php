<?php
session_start();
require '../include/database.php';

if(!isset($_SESSION['user_id'])){header("Location: login.php");exit;}

// mark all as read
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user_id']]);

$notifications=$pdo->prepare("
    SELECT notifications.*,users.username as from_username,users.photo as from_photo
    FROM notifications
    JOIN users ON notifications.from_user_id=users.id
    WHERE notifications.user_id=?
    ORDER BY notifications.created_at DESC
    LIMIT 50
");
$notifications->execute([$_SESSION['user_id']]);
$notifications=$notifications->fetchAll();

function notifText($type,$from,$postId=null){
    switch($type){
        case 'like':    return "<strong>$from</strong> liked your blog post.";
        case 'comment': return "<strong>$from</strong> commented on your blog.";
        case 'follow':  return "<strong>$from</strong> started following you.";
        case 'reply':   return "<strong>$from</strong> replied to your comment.";
        default:        return "<strong>$from</strong> interacted with you.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>Notifications — MyBlog</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box
}

    body{background:#f4f4f8;font-family:sans-serif;color:#1a1a2e
}
    nav{
background:#1a1a2e;padding:14px 30px;display:flex;justify-content:space-between;align-items:center
}
    nav .logo{
font-size:22px;font-weight:bold;color:white
}
    nav .logo span{color:#7f77dd
}
    nav .links a{
color:#ccc;text-decoration:none;margin-left:20px;font-size:14px
}
    nav .links a:hover{color:white}
    .container{max-width:700px;margin:40px auto;padding:0 20px}
    h2{font-size:24px;margin-bottom:20px}
    .notif-list{display:flex;flex-direction:column;gap:10px}
    .notif-item{background:white;border-radius:10px;padding:16px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
    .notif-avatar{width:42px;height:42px;background:#7f77dd;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:16px;flex-shrink:0;overflow:hidden}
    .notif-avatar img{width:100%;height:100%;object-fit:cover}
    .notif-body{flex:1}
    .notif-body p{font-size:14px;color:#333;line-height:1.5}
    .notif-body .time{font-size:12px;color:#aaa;margin-top:4px}
    .notif-icon{font-size:20px;flex-shrink:0}
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
    <h2>🔔 Notifications</h2>

    <?php if(count($notifications)==0): ?>
      <div class="empty">No notifications yet.</div>
    <?php else: ?>

    <div class="notif-list">
      <?php foreach($notifications as $n): ?>

      <div class="notif-item">
        <div class="notif-avatar">
          <?php if($n['from_photo']): ?>
            <img src="../uploads/<?=htmlspecialchars($n['from_photo'])?>" alt="">
          <?php else: ?>
            <?=strtoupper(substr($n['from_username'],0,1))?>
          <?php endif; ?>
        </div>
        <div class="notif-body">
          <p><?=notifText($n['type'],htmlspecialchars($n['from_username']),$n['post_id'])?></p>
          <?php if($n['post_id']): ?>
            <a href="post.php?id=<?=$n['post_id']?>" style="font-size:12px;color:#7f77dd;text-decoration:none">View Blog →</a>
          <?php endif; ?>
          <div class="time"><?=date('d M Y, h:i A',strtotime($n['created_at']))?></div>
        </div>
        <div class="notif-icon">
          <?= $n['type']=='like'?'❤️':($n['type']=='comment'?'💬':($n['type']=='follow'?'👤':'↩️')) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>