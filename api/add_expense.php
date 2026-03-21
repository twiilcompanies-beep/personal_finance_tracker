<?php
// ============================================================
// api/add_expense.php — Add a new transaction (expense or income)
// Accepts: POST (AJAX or form)
// Returns: JSON {success, message} or {error}
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// --- Auth guard: must be logged in ---
if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized. Please log in.'], 401);
}

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method.'], 405);
}

$userId      = (int) $_SESSION['user_id'];
$amount      = trim($_POST['amount']      ?? '');
$category    = trim($_POST['category']    ?? '');
$description = trim($_POST['description'] ?? '');
$type        = trim($_POST['type']        ?? 'expense');
$date        = trim($_POST['date']        ?? '');

// --- Validation ---
$errors = [];

if (!is_numeric($amount) || (float)$amount <= 0) {
    $errors[] = 'Amount must be a positive number.';
}

if (empty($category)) {
    $errors[] = 'Category is required.';
}

if (!in_array($type, ['expense', 'income'], true)) {
    $errors[] = 'Type must be either "expense" or "income".';
}

if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = 'A valid date (YYYY-MM-DD) is required.';
}

if (!empty($errors)) {
    jsonResponse(['error' => implode(' ', $errors)], 422);
}

// --- Insert into database ---
$stmt = $conn->prepare(
    'INSERT INTO expenses (user_id, amount, category, description, type, date)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('idssss', $userId, $amount, $category, $description, $type, $date);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    jsonResponse([
        'success' => true,
        'message' => 'Transaction saved successfully.',
        'id'      => $newId,
    ]);
} else {
    $stmt->close();
    $conn->close();
    jsonResponse(['error' => 'Failed to save transaction. Please try again.'], 500);
}
