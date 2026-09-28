<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND user_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) { die("Blog not found or no permission."); }

$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);
    $domain  = trim($_POST['domain']);
    $image   = $post['image'];

    if (empty($title)) { $error = "Title is required."; }
    elseif (empty($content)||$content=='<p><br></p>') { $error = "Content is required."; }
    else {
        if (!empty($_FILES['image']['name'])) {
            $dir = '../uploads/';
            if (!is_dir($dir)) mkdir($dir,0777,true);
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
            if (in_array($ext,$allowed)) {
                $filename = uniqid().'.'.$ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'],$dir.$filename)) {
                    $image = $filename;
                }
            }
        }
        if (!$error) {
            $pdo->prepare("UPDATE posts SET title=?,content=?,domain=?,image=?,status='pending' WHERE id=? AND user_id=?")
                ->execute([$title,$content,$domain,$image,$id,$_SESSION['user_id']]);
            $success = "Story updated! Pending admin approval.";
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id=?");
            $stmt->execute([$id]);
            $post = $stmt->fetch();
        }
    }
}

$categories = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Story — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.6/quill.snow.css" rel="stylesheet">
  <style>
    .ql-toolbar  { border-radius:4px 4px 0 0!important; border-color:var(--border)!important; background:var(--bg-input)!important; }
    .ql-container{ border-radius:0 0 4px 4px!important; border-color:var(--border)!important; font-size:15px!important; font-family:Inter,sans-serif!important; background:var(--bg-card)!important; }
    .ql-editor   { min-height:320px; color:var(--text)!important; }
  </style>
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div style="max-width:860px;margin:40px auto;padding:0 24px 60px">

    <div style="margin-bottom:28px">
      <h1 style="font-size:28px;font-weight:900;letter-spacing:-0.5px;margin-bottom:6px">
        Edit Story
      </h1>
      <p style="color:var(--text-sub);font-size:14px">
        After saving, story will go back to pending review.
      </p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:20px">
        <span class="material-icons">check_circle</span>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editForm">

      <div class="form-group">
        <label class="form-label">Story Title *</label>
        <input type="text" name="title"
               value="<?= htmlspecialchars($post['title']) ?>"
               style="width:100%;padding:14px 16px;font-size:20px;font-weight:700;
                      border:none;border-bottom:2px solid var(--border);
                      background:transparent;color:var(--text);
                      font-family:Inter,sans-serif;transition:border 0.2s;outline:none"
               onfocus="this.style.borderColor='var(--primary)'"
               onblur="this.style.borderColor='var(--border)'"
               required>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Category</label>
          <select name="domain"
                  style="width:100%;padding:11px 14px;background:var(--bg-input);
                         border:1.5px solid var(--border);border-radius:4px;
                         font-size:14px;font-family:Inter,sans-serif;color:var(--text)">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"
                <?= $post['domain']==$cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Cover Image</label>
          <?php if ($post['image']): ?>
            <div style="margin-bottom:8px">
              <img src="../uploads/<?= htmlspecialchars($post['image']) ?>"
                   style="width:100%;height:60px;object-fit:cover;border-radius:3px" alt="">
            </div>
          <?php endif; ?>
          <label for="image"
                 style="display:flex;align-items:center;gap:8px;padding:8px 14px;
                        border:1.5px dashed var(--border);border-radius:4px;
                        cursor:pointer;font-size:12px;color:var(--text-muted);
                        background:var(--bg-input)"
                 id="file-label">
            <span class="material-icons" style="font-size:17px">add_photo_alternate</span>
            <?= $post['image'] ? 'Replace image' : 'Upload image' ?>
          </label>
          <input type="file" id="image" name="image" accept="image/*"
                 style="display:none" onchange="previewImg(this)">
        </div>

      </div>

      <div id="img-preview-wrap" style="display:none;margin-bottom:20px">
        <img id="img-preview"
             style="width:100%;max-height:200px;object-fit:cover;border-radius:4px" alt="">
      </div>

      <div class="form-group">
        <label class="form-label">Story Content *</label>
        <textarea name="content" id="content" style="display:none"></textarea>
        <div id="editor"></div>
      </div>

      <div style="display:flex;gap:12px;padding-top:20px;border-top:1px solid var(--border)">
        <button type="button" onclick="submitEdit()" class="btn btn-primary">
          <span class="material-icons" style="font-size:17px">save</span>
          Save Changes
        </button>
        <a href="profile.php" class="btn btn-secondary" style="margin-left:auto">Cancel</a>
      </div>

    </form>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.6/quill.min.js"></script>
  <script>
    var quill = new Quill('#editor', {
      theme:'snow',
      modules:{ toolbar:[[{'header':[1,2,3,false]}],['bold','italic','underline'],[{'list':'ordered'},{'list':'bullet'}],['blockquote','link'],['clean']] }
    });
    quill.root.innerHTML = <?= json_encode($post['content']) ?>;

    function submitEdit() {
      document.getElementById('content').value = quill.root.innerHTML;
      document.getElementById('editForm').submit();
    }

    function previewImg(input) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('img-preview').src = e.target.result;
          document.getElementById('img-preview-wrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>

</body>
</html>