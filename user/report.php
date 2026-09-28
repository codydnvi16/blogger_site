<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'] ?? '';
    $reason  = $_POST['reason']  ?? '';

    if ($post_id && $reason) {
        try {
            $pdo->prepare("INSERT INTO reports (post_id, user_id, reason) VALUES (?,?,?)")
                ->execute([$post_id, $_SESSION['user_id'], $reason]);
        } catch (Exception $e) {
            // already reported — ignore duplicate
        }
    }

    header("Location: post.php?id=$post_id");
    exit;
}

header("Location: home.php");
exit;
?>