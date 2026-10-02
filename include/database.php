<?php

// database name
$host   = 'localhost';
$dbname = 'blog';
$user   = 'root';
$pass   = '';        // XAMPP has no password by default, leave empty

try {

    // connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    // show errors clearly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    // if connection fails, show why
    die("Database connection failed: " . $e->getMessage());

}
?>