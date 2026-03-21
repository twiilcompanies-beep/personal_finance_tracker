<?php
// ============================================================
// auth/contact.php — Handles contact-form submission (POST)
// ============================================================
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/db.php';

// --- 1. Collect & sanitize inputs ---
$name    = trim($_POST['contact_name']    ?? '');
$email   = trim($_POST['contact_email']   ?? '');
$message = trim($_POST['contact_message'] ?? '');

// --- 2. Validate ---
$errors = [];

if (empty($name)) {
    $errors[] = 'Your name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($message)) {
    $errors[] = 'Message cannot be empty.';
}

if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    header('Location: ../index.php#contact');
    exit;
}

// --- 3. Insert into messages table ---
$stmt = $conn->prepare('INSERT INTO messages (name, email, message) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $message);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: ../index.php?success=1#contact');
    exit;
} else {
    $_SESSION['contact_errors'] = ['Could not send your message. Please try again.'];
    $stmt->close();
    $conn->close();
    header('Location: ../index.php#contact');
    exit;
}
