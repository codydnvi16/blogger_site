<?php
session_start();
require '../include/database.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <!-- hero -->
  <div style="background:var(--primary);color:white;text-align:center;padding:80px 24px">
    <div style="font-size:11px;font-weight:800;letter-spacing:3px;
                text-transform:uppercase;opacity:0.7;margin-bottom:16px">
      About Us
    </div>
    <h1 style="font-size:44px;font-weight:900;letter-spacing:-1.5px;margin-bottom:16px;line-height:1.1">
      Share Your Story<br>With The World
    </h1>
    <p style="font-size:17px;opacity:0.85;max-width:500px;margin:0 auto;line-height:1.7">
      MyBlog is a platform where anyone can write, publish and discover
      stories, ideas and expertise from writers around the world.
    </p>
  </div>

  <div class="container" style="padding-top:60px;padding-bottom:80px">

    <!-- stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:60px">
      <?php
      $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $totalBlogs = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
      $totalViews = $pdo->query("SELECT SUM(views) FROM posts")->fetchColumn() ?? 0;
      $stats = [
        [$totalUsers,'Writers','people'],
        [$totalBlogs,'Stories Published','article'],
        [number_format($totalViews),'Total Views','visibility'],
        ['100%','Free to Use','verified'],
      ];
      foreach ($stats as [$num,$label,$icon]):
      ?>
      <div style="text-align:center;padding:32px 20px;background:var(--bg-card);
                  border:1px solid var(--border);border-radius:4px">
        <div style="display:inline-flex;align-items:center;justify-content:center;
                    width:48px;height:48px;background:var(--primary-light);
                    border-radius:50%;margin-bottom:14px">
          <span class="material-icons" style="color:var(--primary);font-size:22px"><?= $icon ?></span>
        </div>
        <div style="font-size:32px;font-weight:900;color:var(--text);margin-bottom:4px">
          <?= $num ?>
        </div>
        <div style="font-size:13px;color:var(--text-muted);font-weight:600;
                    text-transform:uppercase;letter-spacing:0.5px">
          <?= $label ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- features -->
    <div style="margin-bottom:60px">
      <div style="text-align:center;margin-bottom:36px">
        <div class="section-title" style="justify-content:center">
          <span class="material-icons">star</span>
          Why MyBlog?
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
        <?php
        $features = [
          ['edit','Write Freely','Write and publish blogs on any topic you are passionate about — no restrictions.'],
          ['people','Build Community','Connect with other writers, follow your favorites and engage with their stories.'],
          ['trending_up','Track Growth','See your views, likes and follower growth with detailed analytics.'],
          ['workspace_premium','Go Premium','Unlock premium features, get a badge and support the platform.'],
          ['shield','Safe Platform','Our moderation system ensures quality content and a safe environment.'],
          ['devices','Read Anywhere','Access MyBlog from any device — desktop, tablet or mobile.'],
        ];
        foreach ($features as [$icon,$title,$desc]):
        ?>
        <div style="padding:24px;background:var(--bg-card);border:1px solid var(--border);border-radius:4px">
          <div style="width:40px;height:40px;background:var(--primary-light);border-radius:4px;
                      display:flex;align-items:center;justify-content:center;margin-bottom:14px">
            <span class="material-icons" style="color:var(--primary);font-size:20px"><?= $icon ?></span>
          </div>
          <div style="font-size:15px;font-weight:800;margin-bottom:8px;color:var(--text)"><?= $title ?></div>
          <p style="font-size:13px;color:var(--text-sub);line-height:1.6"><?= $desc ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- cta -->
    <div style="background:var(--primary);border-radius:4px;padding:48px;text-align:center;color:white">
      <h2 style="font-size:28px;font-weight:900;letter-spacing:-0.5px;margin-bottom:10px">
        Ready to Share Your Story?
      </h2>
      <p style="font-size:15px;opacity:0.85;margin-bottom:24px">
        Join thousands of writers sharing their ideas every day.
      </p>
      <a href="<?= isset($_SESSION['user_id']) ? 'write-blog.php' : 'login.php' ?>"
         style="display:inline-flex;align-items:center;gap:8px;
                padding:13px 28px;background:white;color:var(--primary);
                border-radius:4px;font-size:14px;font-weight:800;
                text-decoration:none">
        <span class="material-icons">edit</span>
        Start Writing Today
      </a>
    </div>

  </div>

  <!-- footer -->
  <footer style="border-top:1px solid var(--border);padding:24px 40px;background:var(--bg-card)">
    <div style="max-width:1300px;margin:0 auto;display:flex;justify-content:space-between;
                align-items:center;font-size:12px;color:var(--text-muted)">
      <span>© <?= date('Y') ?> <strong style="color:var(--text)">MYBLOG</strong></span>
      <div style="display:flex;gap:20px">
        <a href="about.php" style="color:var(--primary);text-decoration:none;font-weight:600">About</a>
        <a href="contact.php" style="color:var(--text-muted);text-decoration:none">Contact</a>
      </div>
    </div>
  </footer>

</body>
</html>