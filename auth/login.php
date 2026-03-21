<?php
// ============================================================
// auth/login.php — Handles user login (POST)
// ============================================================
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/db.php';

// --- 1. Collect inputs ---
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// --- 2. Validate ---
if (empty($email) || empty($password)) {
    $_SESSION['errors'] = ['Please enter your email and password.'];
    header('Location: ../login.php');
    exit;
}

// --- 3. Fetch user by email ---
$stmt = $conn->prepare('SELECT id, username, password FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($userId, $username, $hashedPassword);
$stmt->fetch();
$stmt->close();

// --- 4. Verify password ---
if (!$userId || !password_verify($password, $hashedPassword)) {
    $_SESSION['errors'] = ['Incorrect email or password. Please try again.'];
    $conn->close();
    header('Location: ../login.php');
    exit;
}

$conn->close();

// --- 5. Set session variables ---
session_regenerate_id(true);   // Prevent session fixation
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;

header('Location: ../dashboard.php');
exit;
