<?php
// ============================================================
// api/delete_expense.php — Delete a transaction owned by the user
// Accepts: POST
// Body param: id (expense ID)
// Returns: JSON {success} or {error}
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// --- Auth guard ---
if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method.'], 405);
}

$userId    = (int) $_SESSION['user_id'];
$expenseId = (int) ($_POST['id'] ?? 0);

if ($expenseId <= 0) {
    jsonResponse(['error' => 'Invalid expense ID.'], 422);
}

// --- Delete only if it belongs to the logged-in user ---
$stmt = $conn->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $expenseId, $userId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($affected > 0) {
    jsonResponse(['success' => true, 'message' => 'Transaction deleted.']);
} else {
    jsonResponse(['error' => 'Transaction not found or permission denied.'], 404);
}
