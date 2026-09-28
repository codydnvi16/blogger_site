<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id) {
    header("Location: profile.php");
    exit;
}

// make sure blog belongs to this user and is a draft
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND user_id=? AND status='draft'");
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: profile.php");
    exit;
}

// set status to pending for admin approval
$pdo->prepare("UPDATE posts SET status='pending' WHERE id=? AND user_id=?")
    ->execute([$id, $_SESSION['user_id']]);

header("Location: profile.php?msg=submitted");
exit;
?>