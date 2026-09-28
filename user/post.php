<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$banCheck = $pdo->prepare("SELECT is_banned FROM users WHERE id=?");
$banCheck->execute([$_SESSION['user_id']]);
$banStatus = $banCheck->fetch();
if ($banStatus && $banStatus['is_banned']) {
    die("<div style='text-align:center;padding:60px;font-family:sans-serif'>
         <h2>Account Banned</h2><p>Contact support.</p></div>");
}

$id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.photo, users.is_premium, users.bio
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) { die("Blog not found."); }

$pdo->prepare("UPDATE posts SET views = views + 1 WHERE id=?")->execute([$id]);

if (isset($_GET['follow'])) {
    $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)")
        ->execute([$_SESSION['user_id'], $_GET['follow']]);
    if ($_GET['follow'] != $_SESSION['user_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id,from_user_id,type) VALUES (?,?,?)")
            ->execute([$_GET['follow'], $_SESSION['user_id'], 'follow']);
    }
    header("Location: post.php?id=$id"); exit;
}

if (isset($_GET['unfollow'])) {
    $pdo->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")
        ->execute([$_SESSION['user_id'], $_GET['unfollow']]);
    header("Location: post.php?id=$id"); exit;
}

if (isset($_GET['like'])) {
    $check = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?");
    $check->execute([$id, $_SESSION['user_id']]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM likes WHERE post_id=? AND user_id=?")->execute([$id, $_SESSION['user_id']]);
    } else {
        $pdo->prepare("INSERT INTO likes (post_id,user_id) VALUES (?,?)")->execute([$id, $_SESSION['user_id']]);
        if ($post['user_id'] != $_SESSION['user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id,from_user_id,type,post_id) VALUES (?,?,?,?)")
                ->execute([$post['user_id'], $_SESSION['user_id'], 'like', $id]);
        }
    }
    header("Location: post.php?id=$id"); exit;
}

if (isset($_POST['comment'])) {
    $body = trim($_POST['comment']);
    if ($body) {
        $pdo->prepare("INSERT INTO comments (post_id,user_id,body) VALUES (?,?,?)")
            ->execute([$id, $_SESSION['user_id'], $body]);
        if ($post['user_id'] != $_SESSION['user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id,from_user_id,type,post_id) VALUES (?,?,?,?)")
                ->execute([$post['user_id'], $_SESSION['user_id'], 'comment', $id]);
        }
    }
    header("Location: post.php?id=$id"); exit;
}

if (isset($_POST['reply_comment_id'])) {
    $comment_id = $_POST['reply_comment_id'];
    $body = trim($_POST['reply_body']);
    if ($body) {
        $pdo->prepare("INSERT INTO replies (comment_id,user_id,body) VALUES (?,?,?)")
            ->execute([$comment_id, $_SESSION['user_id'], $body]);
        $co = $pdo->prepare("SELECT user_id FROM comments WHERE id=?");
        $co->execute([$comment_id]);
        $coOwner = $co->fetchColumn();
        if ($coOwner && $coOwner != $_SESSION['user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id,from_user_id,type,post_id) VALUES (?,?,?,?)")
                ->execute([$coOwner, $_SESSION['user_id'], 'reply', $id]);
        }
    }
    header("Location: post.php?id=$id"); exit;
}

$lc = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id=?");
$lc->execute([$id]);
$likes = $lc->fetchColumn();

$ul = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?");
$ul->execute([$id, $_SESSION['user_id']]);
$already_liked = $ul->fetch();

$bm = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id=? AND user_id=?");
$bm->execute([$id, $_SESSION['user_id']]);
$isBookmarked = $bm->fetch();

$fc = $pdo->prepare("SELECT id FROM follows WHERE follower_id=? AND following_id=?");
$fc->execute([$_SESSION['user_id'], $post['user_id']]);
$isFollowing = $fc->fetch();

$rp = $pdo->prepare("SELECT id FROM reports WHERE post_id=? AND user_id=?");
$rp->execute([$id, $_SESSION['user_id']]);
$alreadyReported = $rp->fetch();

$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$unread->execute([$_SESSION['user_id']]);
$unreadCount = $unread->fetchColumn();

$cs = $pdo->prepare("
    SELECT comments.*, users.username, users.photo
    FROM comments JOIN users ON comments.user_id=users.id
    WHERE comments.post_id=? ORDER BY comments.created_at ASC
");
$cs->execute([$id]);
$comments = $cs->fetchAll();

// related posts
$related = $pdo->prepare("
    SELECT posts.*, users.username FROM posts
    JOIN users ON posts.user_id=users.id
    WHERE posts.status='published' AND posts.domain=? AND posts.id!=?
    ORDER BY posts.views DESC LIMIT 3
");
$related->execute([$post['domain'], $id]);
$related = $related->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($post['title']) ?> — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    .post-content { font-size:17px; line-height:1.85; color:var(--text); }
    .post-content h1,.post-content h2,.post-content h3 { margin:28px 0 12px; font-weight:800; }
    .post-content p  { margin-bottom:16px; }
    .post-content ul,.post-content ol { margin:12px 0 12px 24px; }
    .post-content blockquote {
      border-left:3px solid var(--primary);
      padding:12px 20px;
      background:var(--primary-light);
      border-radius:0 4px 4px 0;
      margin:20px 0;
      font-style:italic;
    }
    .post-content img { max-width:100%; border-radius:4px; margin:16px 0; }

    .comment-box {
      padding:16px;
      background:var(--bg-card);
      border:1px solid var(--border);
      border-radius:4px;
      margin-bottom:12px;
      transition:border 0.2s;
    }
    .comment-box:hover { border-color:var(--primary); }

    .reply-box {
      margin-left:24px;
      margin-top:10px;
      padding:12px;
      background:var(--bg-input);
      border-left:2px solid var(--primary);
      border-radius:0 4px 4px 0;
    }

    .modal-overlay {
      display:none;
      position:fixed;
      top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,0.5);
      z-index:2000;
      align-items:center;
      justify-content:center;
    }

    .modal-overlay.open { display:flex; }
  </style>
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div style="max-width:1300px;margin:0 auto;padding:40px 40px 60px;
              display:grid;grid-template-columns:1fr 300px;gap:48px">

    <!-- ===== LEFT: POST CONTENT ===== -->
    <div>

      <!-- breadcrumb -->
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:20px;
                  display:flex;align-items:center;gap:4px">
        <a href="home.php" style="color:var(--text-muted);text-decoration:none">Home</a>
        <span class="material-icons" style="font-size:14px">chevron_right</span>
        <?php if ($post['domain']): ?>
          <a href="home.php?category=<?= urlencode($post['domain']) ?>"
             style="color:var(--primary);text-decoration:none;font-weight:600">
            <?= htmlspecialchars($post['domain']) ?>
          </a>
          <span class="material-icons" style="font-size:14px">chevron_right</span>
        <?php endif; ?>
        <span><?= htmlspecialchars(substr($post['title'],0,40)) ?>...</span>
      </div>

      <!-- category -->
      <?php if ($post['domain']): ?>
        <div style="font-size:10px;font-weight:800;letter-spacing:2px;
                    text-transform:uppercase;color:var(--primary);margin-bottom:14px">
          <?= htmlspecialchars($post['domain']) ?>
        </div>
      <?php endif; ?>

      <!-- title -->
      <h1 style="font-size:38px;font-weight:900;line-height:1.2;
                  color:var(--text);margin-bottom:20px;letter-spacing:-1px">
        <?= htmlspecialchars($post['title']) ?>
      </h1>

      <?php if ($post['featured']): ?>
        <div style="display:inline-flex;align-items:center;gap:4px;
                    background:var(--primary);color:white;
                    padding:4px 12px;border-radius:2px;font-size:11px;
                    font-weight:800;letter-spacing:1px;text-transform:uppercase;
                    margin-bottom:20px">
          <span class="material-icons" style="font-size:14px">star</span>
          Featured Story
        </div>
      <?php endif; ?>

      <!-- author row -->
      <div style="display:flex;align-items:center;gap:14px;
                  padding:16px 0;border-top:1px solid var(--border);
                  border-bottom:1px solid var(--border);margin-bottom:32px">
        <div class="avatar avatar-md">
          <?php if ($post['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($post['photo']) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($post['username'],0,1)) ?>
          <?php endif; ?>
        </div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:14px;margin-bottom:2px">
            <a href="author.php?id=<?= $post['user_id'] ?>"
               style="color:var(--text);text-decoration:none">
              <?= htmlspecialchars($post['username']) ?>
            </a>
            <?php if ($post['is_premium']): ?>
              <span class="badge badge-premium" style="margin-left:6px">Premium</span>
            <?php endif; ?>
          </div>
          <div style="font-size:12px;color:var(--text-muted);
                      display:flex;align-items:center;gap:8px">
            <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
            <span>·</span>
            <span class="material-icons" style="font-size:13px">visibility</span>
            <span><?= number_format($post['views']+1) ?> views</span>
            <span>·</span>
            <span class="material-icons" style="font-size:13px">favorite</span>
            <span><?= $likes ?> likes</span>
            <span>·</span>
            <span class="material-icons" style="font-size:13px">chat_bubble</span>
            <span><?= count($comments) ?> comments</span>
          </div>
        </div>

        <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
          <a href="post.php?id=<?= $id ?>&<?= $isFollowing?'unfollow':'follow' ?>=<?= $post['user_id'] ?>"
             class="btn <?= $isFollowing?'btn-secondary':'btn-outline' ?> btn-sm">
            <span class="material-icons" style="font-size:15px">
              <?= $isFollowing?'how_to_reg':'person_add' ?>
            </span>
            <?= $isFollowing?'Following':'Follow' ?>
          </a>
        <?php endif; ?>
      </div>

      <!-- cover image -->
      <?php if ($post['image']): ?>
        <img src="../uploads/<?= htmlspecialchars($post['image']) ?>"
             style="width:100%;max-height:500px;object-fit:cover;
                    border-radius:4px;margin-bottom:36px" alt="">
      <?php endif; ?>

      <!-- content -->
      <div class="post-content">
        <?= $post['content'] ?>
      </div>

      <!-- action bar -->
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;
                  padding:20px 0;margin:32px 0;
                  border-top:1px solid var(--border);
                  border-bottom:1px solid var(--border)">

        <a href="post.php?id=<?= $id ?>&like=1"
           class="btn <?= $already_liked?'btn-primary':'btn-secondary' ?> btn-sm">
          <span class="material-icons" style="font-size:16px">
            <?= $already_liked?'favorite':'favorite_border' ?>
          </span>
          <?= $likes ?> <?= $likes==1?'Like':'Likes' ?>
        </a>

        <a href="bookmarks.php?toggle=<?= $id ?>&ref=post.php%3Fid%3D<?= $id ?>"
           class="btn <?= $isBookmarked?'btn-primary':'btn-secondary' ?> btn-sm">
          <span class="material-icons" style="font-size:16px">
            <?= $isBookmarked?'bookmark':'bookmark_border' ?>
          </span>
          <?= $isBookmarked?'Saved':'Save' ?>
        </a>

        <button onclick="copyLink()" class="btn btn-secondary btn-sm">
          <span class="material-icons" style="font-size:16px">share</span>
          Share
        </button>

        <span id="copy-msg"
              style="display:none;font-size:12px;color:var(--primary);font-weight:600">
          ✓ Copied!
        </span>

        <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
          <?php if ($alreadyReported): ?>
            <span class="btn btn-secondary btn-sm" style="margin-left:auto;cursor:default;opacity:0.6">
              <span class="material-icons" style="font-size:16px">flag</span>
              Reported
            </span>
          <?php else: ?>
            <button onclick="openReport()"
                    class="btn btn-secondary btn-sm" style="margin-left:auto">
              <span class="material-icons" style="font-size:16px">flag</span>
              Report
            </button>
          <?php endif; ?>
        <?php endif; ?>

      </div>

      <!-- author bio box -->
      <div style="background:var(--bg-input);border-radius:4px;
                  padding:24px;margin-bottom:40px;
                  display:flex;gap:16px;align-items:flex-start">
        <div class="avatar avatar-md">
          <?php if ($post['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($post['photo']) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($post['username'],0,1)) ?>
          <?php endif; ?>
        </div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:15px;margin-bottom:4px">
            Written by
            <a href="author.php?id=<?= $post['user_id'] ?>"
               style="color:var(--primary);text-decoration:none">
              <?= htmlspecialchars($post['username']) ?>
            </a>
          </div>
          <?php if (!empty($post['bio'])): ?>
            <p style="font-size:13px;color:var(--text-sub);line-height:1.6">
              <?= htmlspecialchars($post['bio']) ?>
            </p>
          <?php endif; ?>
        </div>
        <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
          <a href="post.php?id=<?= $id ?>&<?= $isFollowing?'unfollow':'follow' ?>=<?= $post['user_id'] ?>"
             class="btn <?= $isFollowing?'btn-secondary':'btn-primary' ?> btn-sm">
            <?= $isFollowing?'Following':'Follow' ?>
          </a>
        <?php endif; ?>
      </div>

      <!-- comments -->
      <div id="comments">
        <div class="section-title">
          <span class="material-icons">chat_bubble</span>
          Comments (<?= count($comments) ?>)
        </div>

        <!-- add comment -->
        <form method="POST" style="margin-bottom:28px">
          <div style="display:flex;gap:12px;align-items:flex-start">
            <div class="avatar avatar-sm" style="margin-top:4px">
              <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
            </div>
            <div style="flex:1">
              <textarea name="comment"
                        placeholder="Share your thoughts..."
                        style="width:100%;padding:12px 14px;
                               border:1.5px solid var(--border);
                               border-radius:4px;font-size:14px;
                               font-family:Inter,sans-serif;
                               background:var(--bg-input);
                               color:var(--text);resize:vertical;
                               min-height:90px;transition:border 0.2s"
                        onfocus="this.style.borderColor='var(--primary)'"
                        onblur="this.style.borderColor='var(--border)'"
                        required></textarea>
              <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px">
                <span class="material-icons" style="font-size:15px">send</span>
                Post Comment
              </button>
            </div>
          </div>
        </form>

        <!-- comment list -->
        <?php if (count($comments)==0): ?>
          <div class="empty-state" style="padding:30px">
            <span class="material-icons">chat_bubble_outline</span>
            <p>Be the first to comment!</p>
          </div>
        <?php endif; ?>

        <?php foreach ($comments as $c): ?>
        <div class="comment-box">
          <div style="display:flex;gap:10px;align-items:flex-start">
            <div class="avatar avatar-sm">
              <?php if ($c['photo']): ?>
                <img src="../uploads/<?= htmlspecialchars($c['photo']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($c['username'],0,1)) ?>
              <?php endif; ?>
            </div>
            <div style="flex:1">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span style="font-weight:700;font-size:13px;color:var(--text)">
                  <?= htmlspecialchars($c['username']) ?>
                </span>
                <span style="font-size:11px;color:var(--text-muted)">
                  <?= date('d M Y', strtotime($c['created_at'])) ?>
                </span>
              </div>
              <p style="font-size:14px;color:var(--text);line-height:1.6">
                <?= nl2br(htmlspecialchars($c['body'])) ?>
              </p>
            </div>
          </div>

          <!-- replies -->
          <?php
            $rs = $pdo->prepare("
                SELECT replies.*,users.username,users.photo
                FROM replies JOIN users ON replies.user_id=users.id
                WHERE replies.comment_id=? ORDER BY replies.created_at ASC
            ");
            $rs->execute([$c['id']]);
            $replies = $rs->fetchAll();
          ?>

          <?php foreach ($replies as $r): ?>
          <div class="reply-box" style="margin-top:10px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
              <div class="avatar avatar-xs">
                <?php if ($r['photo']): ?>
                  <img src="../uploads/<?= htmlspecialchars($r['photo']) ?>" alt="">
                <?php else: ?>
                  <?= strtoupper(substr($r['username'],0,1)) ?>
                <?php endif; ?>
              </div>
              <span style="font-weight:700;font-size:12px;color:var(--primary)">
                <?= htmlspecialchars($r['username']) ?>
              </span>
            </div>
            <p style="font-size:13px;color:var(--text);line-height:1.5">
              <?= nl2br(htmlspecialchars($r['body'])) ?>
            </p>
          </div>
          <?php endforeach; ?>

          <!-- reply toggle -->
          <button onclick="toggleReply(<?= $c['id'] ?>)"
                  style="margin-top:10px;background:none;border:none;
                         color:var(--primary);font-size:12px;font-weight:700;
                         cursor:pointer;font-family:Inter,sans-serif;
                         display:flex;align-items:center;gap:4px">
            <span class="material-icons" style="font-size:15px">reply</span>
            Reply
          </button>

          <div id="reply-<?= $c['id'] ?>" style="display:none;margin-top:10px">
            <form method="POST" style="display:flex;gap:10px">
              <input type="hidden" name="reply_comment_id" value="<?= $c['id'] ?>">
              <textarea name="reply_body"
                        placeholder="Write a reply..."
                        style="flex:1;padding:10px;border:1.5px solid var(--border);
                               border-radius:4px;font-size:13px;
                               font-family:Inter,sans-serif;
                               background:var(--bg-input);color:var(--text);
                               resize:vertical;min-height:70px"
                        required></textarea>
              <button type="submit" class="btn btn-primary btn-sm">Post</button>
            </form>
          </div>

        </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- ===== RIGHT SIDEBAR ===== -->
    <div style="position:sticky;top:100px;height:fit-content">

      <!-- author card -->
      <div class="sidebar-card" style="margin-bottom:24px">
        <div style="text-align:center;padding:8px 0">
          <div class="avatar avatar-lg" style="margin:0 auto 12px">
            <?php if ($post['photo']): ?>
              <img src="../uploads/<?= htmlspecialchars($post['photo']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($post['username'],0,1)) ?>
            <?php endif; ?>
          </div>
          <h3 style="font-size:16px;font-weight:800;margin-bottom:4px">
            <?= htmlspecialchars($post['username']) ?>
          </h3>
          <?php if (!empty($post['bio'])): ?>
            <p style="font-size:12px;color:var(--text-sub);line-height:1.5;margin-bottom:12px">
              <?= htmlspecialchars(substr($post['bio'],0,80)) ?>...
            </p>
          <?php endif; ?>
          <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
            <a href="post.php?id=<?= $id ?>&<?= $isFollowing?'unfollow':'follow' ?>=<?= $post['user_id'] ?>"
               class="btn <?= $isFollowing?'btn-secondary':'btn-primary' ?> btn-sm btn-block">
              <span class="material-icons" style="font-size:15px">
                <?= $isFollowing?'how_to_reg':'person_add' ?>
              </span>
              <?= $isFollowing?'Following':'Follow Author' ?>
            </a>
          <?php endif; ?>
          <a href="author.php?id=<?= $post['user_id'] ?>"
             class="btn btn-secondary btn-sm btn-block" style="margin-top:8px">
            View Profile
          </a>
        </div>
      </div>

      <!-- related posts -->
      <?php if (count($related) > 0): ?>
      <div class="sidebar-card">
        <div class="section-title">
          <span class="material-icons">auto_stories</span>
          Related Stories
        </div>
        <?php foreach ($related as $r): ?>
        <a href="post.php?id=<?= $r['id'] ?>" class="sidebar-trending-item">
          <div>
            <?php if ($r['domain']): ?>
              <div style="font-size:10px;font-weight:800;letter-spacing:1px;
                          text-transform:uppercase;color:var(--primary);margin-bottom:3px">
                <?= htmlspecialchars($r['domain']) ?>
              </div>
            <?php endif; ?>
            <div class="title"><?= htmlspecialchars(substr($r['title'],0,50)) ?>...</div>
            <div class="views"><?= htmlspecialchars($r['username']) ?> · <?= $r['views'] ?> views</div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- report modal -->
  <div class="modal-overlay" id="reportModal">
    <div style="background:var(--bg-card);border-radius:4px;padding:28px;
                max-width:420px;width:90%;border:1px solid var(--border)">
      <h3 style="font-size:16px;font-weight:800;margin-bottom:6px">Report Story</h3>
      <p style="font-size:13px;color:var(--text-sub);margin-bottom:16px">
        Select a reason for reporting this story.
      </p>
      <form method="POST" action="report.php">
        <input type="hidden" name="post_id" value="<?= $id ?>">
        <?php foreach(['Spam','Offensive Content','Fake Information','Hate Speech','Other'] as $r): ?>
        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;
                      border:1px solid var(--border);border-radius:3px;
                      margin-bottom:8px;cursor:pointer;font-size:13px;
                      transition:border 0.2s"
               onmouseover="this.style.borderColor='var(--primary)'"
               onmouseout="this.style.borderColor='var(--border)'">
          <input type="radio" name="reason" value="<?= $r ?>" required>
          <?= $r ?>
        </label>
        <?php endforeach; ?>
        <div style="display:flex;gap:10px;margin-top:16px">
          <button type="submit" class="btn btn-danger">Submit Report</button>
          <button type="button" onclick="closeReport()"
                  class="btn btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleReply(id) {
      var f = document.getElementById('reply-'+id);
      f.style.display = f.style.display==='none'||f.style.display==='' ? 'block':'none';
    }
    function copyLink() {
      navigator.clipboard.writeText(window.location.href).then(function() {
        var m = document.getElementById('copy-msg');
        m.style.display = 'inline';
        setTimeout(function(){ m.style.display='none'; }, 2000);
      });
    }
    function openReport()  { document.getElementById('reportModal').classList.add('open'); }
    function closeReport() { document.getElementById('reportModal').classList.remove('open'); }
    document.getElementById('reportModal').addEventListener('click', function(e) {
      if (e.target===this) closeReport();
    });
  </script>

</body>
</html>