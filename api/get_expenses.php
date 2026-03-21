<?php
// ============================================================
// api/get_expenses.php — Fetch expense/income records for a user
// Accepts: GET
// Query params:
//   ?period=all|daily|weekly|monthly   (default: all)
//   ?limit=N                           (default: 50)
// Returns: JSON array of transaction objects
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// --- Auth guard ---
if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized.'], 401);
}

$userId = (int) $_SESSION['user_id'];
$period = $_GET['period'] ?? 'all';
$limit  = max(1, min(200, (int) ($_GET['limit'] ?? 50)));

// --- Build date filter ---
$dateFilter = '';
switch ($period) {
    case 'daily':
        $dateFilter = "AND DATE(e.date) = CURDATE()";
        break;
    case 'weekly':
        $dateFilter = "AND e.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'monthly':
        $dateFilter = "AND YEAR(e.date) = YEAR(CURDATE()) AND MONTH(e.date) = MONTH(CURDATE())";
        break;
    default:
        $dateFilter = '';
}

// --- Query ---
$sql = "SELECT id, amount, category, description, type, date, created_at
        FROM expenses
        WHERE user_id = ? $dateFilter
        ORDER BY date DESC, created_at DESC
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $userId, $limit);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $row['amount'] = (float) $row['amount'];
    $expenses[]    = $row;
}

$stmt->close();
$conn->close();

jsonResponse($expenses);
