<div class="admin-topbar">
  <div class="topbar-left">
    <a href="dashboard.php"
       style="color:var(--text-muted);text-decoration:none;
              display:flex;align-items:center;gap:4px;font-size:12px">
      <span class="material-icons" style="font-size:15px">home</span>
      Dashboard
    </a>
    <span class="material-icons" style="font-size:14px">chevron_right</span>
    <span class="page-title"><?= $pageTitle ?? 'Admin' ?></span>
  </div>
  <div class="topbar-right">
    <a href="../user/home.php" target="_blank"
       style="display:inline-flex;align-items:center;gap:5px;
              padding:6px 12px;background:var(--primary);color:white;
              border-radius:6px;font-size:12px;font-weight:600;
              text-decoration:none">
      <span class="material-icons" style="font-size:15px">open_in_new</span>
      View Site
    </a>
    <div class="topbar-divider"></div>
    <button class="topbar-btn" id="themeToggle" onclick="toggleAdminTheme()">
      <span class="material-icons">dark_mode</span>
    </button>
    <a href="logout.php" class="topbar-btn" style="color:var(--text-sub)">
      <span class="material-icons">logout</span>
    </a>
  </div>
</div>