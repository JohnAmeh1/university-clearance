<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['user_role'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['full_name'] = $user['full_name'];
        
        if ($user['username'] === 'admin') {
            redirect('../admin/dashboard.php');
        } else {
            redirect('../student/dashboard.php');
        }
    } else {
        $_SESSION['error'] = "Invalid username or password";
        redirect('../login.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $department = trim($_POST['department']);
    $user_role = 'student'; // Default role
    
    // Validation
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        redirect('../signup.php');
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, department, user_role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $full_name, $department, $user_role]);
        
        $_SESSION['success'] = "Registration successful. Please login.";
        redirect('../login.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        redirect('../signup.php');
    }
}
