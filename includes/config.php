<?php
session_start();

$host = 'localhost';
$dbname = 'university_clearance';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Check if the constant exists before using it
    if (defined('PDO::ATTR_ERRMODE')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        // Fallback error handling
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_student() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}