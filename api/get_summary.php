<?php
// ============================================================
// api/get_summary.php — Dashboard summary statistics
// Accepts: GET
// Returns: JSON with monthly totals, category breakdown, and 6-month trend
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// --- Auth guard ---
if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized.'], 401);
}

$userId = (int) $_SESSION['user_id'];

// --- This month's income total ---
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
       AND type = 'income'
       AND YEAR(date) = YEAR(CURDATE())
       AND MONTH(date) = MONTH(CURDATE())"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$monthlyIncome = (float) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// --- This month's expense total ---
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
       AND type = 'expense'
       AND YEAR(date) = YEAR(CURDATE())
       AND MONTH(date) = MONTH(CURDATE())"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$monthlyExpenses = (float) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// --- All-time balance (income − expenses) ---
$stmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS total_expenses
     FROM expenses
     WHERE user_id = ?"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$totalBalance = (float) $row['total_income'] - (float) $row['total_expenses'];
$stmt->close();

// --- Category breakdown (this month, expenses only) ---
$stmt = $conn->prepare(
    "SELECT category, COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
       AND type = 'expense'
       AND YEAR(date) = YEAR(CURDATE())
       AND MONTH(date) = MONTH(CURDATE())
     GROUP BY category
     ORDER BY total DESC"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$categoryBreakdown = [];
while ($row = $result->fetch_assoc()) {
    $categoryBreakdown[] = [
        'category' => $row['category'],
        'total'    => (float) $row['total'],
    ];
}
$stmt->close();

// --- Last 6 months trend (income & expenses per month) ---
$stmt = $conn->prepare(
    "SELECT
        DATE_FORMAT(date, '%Y-%m') AS month_key,
        DATE_FORMAT(date, '%b %Y') AS month_label,
        COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS expenses
     FROM expenses
     WHERE user_id = ?
       AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month_key, month_label
     ORDER BY month_key ASC"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$trend = [];
while ($row = $result->fetch_assoc()) {
    $trend[] = [
        'month'    => $row['month_label'],
        'income'   => (float) $row['income'],
        'expenses' => (float) $row['expenses'],
    ];
}
$stmt->close();
$conn->close();

jsonResponse([
    'monthly_income'    => $monthlyIncome,
    'monthly_expenses'  => $monthlyExpenses,
    'total_balance'     => $totalBalance,
    'category_breakdown'=> $categoryBreakdown,
    'trend'             => $trend,
]);
