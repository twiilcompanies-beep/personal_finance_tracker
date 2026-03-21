<?php
// ============================================================
// auth/register.php — Handles new user registration (POST)
// ============================================================
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

require_once '../includes/db.php';

// --- 1. Collect & sanitize inputs ---
$username        = trim($_POST['name']            ?? '');
$email           = trim($_POST['email']           ?? '');
$password        = $_POST['password']             ?? '';
$confirmPassword = $_POST['confirmPassword']      ?? '';

// --- 2. Validate ---
$errors = [];

if (empty($username)) {
    $errors[] = 'Full name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: ../register.php');
    exit;
}

// --- 3. Check if email already exists ---
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['errors'] = ['An account with this email address already exists.'];
    $stmt->close();
    $conn->close();
    header('Location: ../register.php');
    exit;
}
$stmt->close();

// --- 4. Hash password & insert user ---
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $username, $email, $hashedPassword);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Your account has been created! Please log in.';
    $stmt->close();
    $conn->close();
    header('Location: ../login.php');
    exit;
} else {
    $_SESSION['errors'] = ['Registration failed. Please try again.'];
    $stmt->close();
    $conn->close();
    header('Location: ../register.php');
    exit;
}
