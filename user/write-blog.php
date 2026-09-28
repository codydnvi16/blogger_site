<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);
    $domain  = trim($_POST['domain']);
    $image   = '';
    $status  = (isset($_POST['submit_type']) && $_POST['submit_type']=='draft') ? 'draft' : 'pending';

    if (empty($title)) {
        $error = "Title is required.";
    } elseif (empty($content) || $content=='<p><br></p>') {
        $error = "Content is required.";
    } else {
        if (!empty($_FILES['image']['name'])) {
            if ($_FILES['image']['error']!==UPLOAD_ERR_OK) {
                $error = "Upload error: ".$_FILES['image']['error'];
            } else {
                $dir = '../uploads/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = "Only JPG PNG GIF WEBP allowed.";
                } else {
                    $filename = uniqid().'.'.$ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir.$filename)) {
                        $image = $filename;
                    } else {
                        $error = "Failed to save image.";
                    }
                }
            }
        }
        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id,title,content,domain,image,status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$_SESSION['user_id'],$title,$content,$domain,$image,$status]);
            if ($status=='draft') {
                header("Location: profile.php?msg=draft_saved");
            } else {
                header("Location: home.php?msg=pending");
            }
            exit;
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
  <title>Write a Story — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.6/quill.snow.css" rel="stylesheet">
  <style>
    .ql-toolbar  { border-radius:4px 4px 0 0!important; border-color:var(--border)!important; background:var(--bg-input)!important; }
    .ql-container{ border-radius:0 0 4px 4px!important; border-color:var(--border)!important; font-size:15px!important; font-family:Inter,sans-serif!important; background:var(--bg-card)!important; }
    .ql-editor   { min-height:320px; color:var(--text)!important; }
    .ql-editor.ql-blank::before { color:var(--text-muted)!important; }
  </style>
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div style="max-width:860px;margin:40px auto;padding:0 24px 60px">

    <!-- header -->
    <div style="margin-bottom:28px">
      <h1 style="font-size:28px;font-weight:900;letter-spacing:-0.5px;margin-bottom:6px">
        Write a Story
      </h1>
      <p style="color:var(--text-sub);font-size:14px">
        Share your knowledge and ideas with the world.
      </p>
    </div>

    <!-- alerts -->
    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="blogForm">

      <!-- title -->
      <div class="form-group">
        <label class="form-label">Story Title *</label>
        <input type="text" name="title"
               placeholder="Enter a compelling title..."
               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
               style="width:100%;padding:14px 16px;font-size:20px;font-weight:700;
                      border:none;border-bottom:2px solid var(--border);
                      background:transparent;color:var(--text);
                      font-family:Inter,sans-serif;transition:border 0.2s;outline:none"
               onfocus="this.style.borderColor='var(--primary)'"
               onblur="this.style.borderColor='var(--border)'"
               required>
      </div>

      <!-- category + cover image row -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Category</label>
          <div class="input-wrap no-icon">
            <select name="domain"
                    style="padding:11px 14px;background:var(--bg-input);
                           border:1.5px solid var(--border);border-radius:4px;
                           font-size:14px;font-family:Inter,sans-serif;
                           color:var(--text);width:100%;cursor:pointer">
              <option value="">-- Select Category --</option>
                      <option value=""> Travel</option>
                       <option value=""> Education </option>
                <option value="">Food</option>
                <option value="">LifeStyle</option>
                <option value="">Sports</option>
                  <option value="">Movie</option>
                 <option value="">Business</option>
                                 <option value="">Technology</option>

                <option value="">Others</option>











              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                  <?= (isset($_POST['domain']) && $_POST['domain']==$cat) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Cover Image (optional)</label>
          <label for="image"
                 style="display:flex;align-items:center;gap:8px;
                        padding:10px 14px;border:1.5px dashed var(--border);
                        border-radius:4px;cursor:pointer;font-size:13px;
                        color:var(--text-muted);transition:all 0.2s;background:var(--bg-input)"
                 onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
                 onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'"
                 id="file-label">
            <span class="material-icons" style="font-size:18px">add_photo_alternate</span>
            <span>Click to upload image</span>
          </label>
          <input type="file" id="image" name="image" accept="image/*"
                 style="display:none" onchange="previewImage(this)">
        </div>

      </div>

      <!-- image preview -->
      <div id="img-preview-wrap" style="display:none;margin-bottom:20px">
        <img id="img-preview"
             style="width:100%;max-height:280px;object-fit:cover;
                    border-radius:4px;border:1px solid var(--border)" alt="">
        <button type="button" onclick="removeImage()"
                style="margin-top:6px;background:none;border:none;
                       color:var(--text-muted);font-size:12px;cursor:pointer;
                       font-family:Inter,sans-serif;display:flex;align-items:center;gap:4px">
          <span class="material-icons" style="font-size:15px">close</span>
          Remove image
        </button>
      </div>

      <!-- content editor -->
      <div class="form-group">
        <label class="form-label">Story Content *</label>
        <textarea name="content" id="content" style="display:none"></textarea>
        <div id="editor"></div>
      </div>

      <!-- action buttons -->
      <div style="display:flex;gap:12px;align-items:center;
                  padding-top:20px;border-top:1px solid var(--border);margin-top:8px">
        <button type="button" onclick="submitForm('publish')"
                class="btn btn-primary">
          <span class="material-icons" style="font-size:17px">publish</span>
          Publish Story
        </button>
        <button type="button" onclick="submitForm('draft')"
                class="btn btn-secondary">
          <span class="material-icons" style="font-size:17px">save</span>
          Save Draft
        </button>
        <a href="profile.php" class="btn btn-secondary" style="margin-left:auto">
          Cancel
        </a>
      </div>

    </form>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.6/quill.min.js"></script>
  <script>
    var quill = new Quill('#editor', {
      theme:'snow',
      placeholder:'Write your story here...',
      modules:{
        toolbar:[
          [{'header':[1,2,3,false]}],
          ['bold','italic','underline'],
          [{'list':'ordered'},{'list':'bullet'}],
          ['blockquote','link'],
          ['clean']
        ]
      }
    });

    function submitForm(type) {
      var content = quill.root.innerHTML;
      if (content=='<p><br></p>' || content.trim()=='') {
        alert('Please write some content before submitting.');
        return;
      }
      document.getElementById('content').value = content;
      var input = document.createElement('input');
      input.type='hidden'; input.name='submit_type'; input.value=type;
      document.getElementById('blogForm').appendChild(input);
      document.getElementById('blogForm').submit();
    }

    function previewImage(input) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('img-preview').src = e.target.result;
          document.getElementById('img-preview-wrap').style.display = 'block';
          document.getElementById('file-label').querySelector('span:last-child').textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function removeImage() {
      document.getElementById('image').value = '';
      document.getElementById('img-preview-wrap').style.display = 'none';
      document.getElementById('file-label').querySelector('span:last-child').textContent = 'Click to upload image';
    }
  </script>

</body>
</html>