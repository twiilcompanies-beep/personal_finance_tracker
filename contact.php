<?php
// ============================================================
// contact.php (root) — Delegates to auth/contact.php
// This file exists so that forms pointing to "contact.php"
// at the project root also work. The real handler lives in
// auth/contact.php which uses relative paths to includes/.
// ============================================================
session_start();

// Only accept POST; otherwise send back to homepage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

// --- Collect & sanitize inputs ---
$name    = trim($_POST['contact_name']    ?? '');
$email   = trim($_POST['contact_email']   ?? '');
$message = trim($_POST['contact_message'] ?? '');

// --- Validate ---
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
    header('Location: index.php#contact');
    exit;
}

// --- Insert into messages table ---
$stmt = $conn->prepare('INSERT INTO messages (name, email, message) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $message);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: index.php?success=1#contact');
    exit;
} else {
    $_SESSION['contact_errors'] = ['Could not send your message. Please try again.'];
    $stmt->close();
    $conn->close();
    header('Location: index.php#contact');
    exit;
}
