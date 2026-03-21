<?php
// ============================================================
// dashboard.php — Protected dashboard for logged-in users
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── Auth guard ──────────────────────────────────────────────
requireLogin('login.php');

$userId   = (int) $_SESSION['user_id'];
$username = sanitize($_SESSION['username']);

// ── Safe DB helper: avoids fatal crash if table is missing ──
function safeQuery(mysqli $conn, string $sql, string $types, ...$params): float {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0.0;
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float) ($row['total'] ?? 0);
}

// ── Monthly income ──────────────────────────────────────────
$monthlyIncome = safeQuery(
    $conn,
    "SELECT COALESCE(SUM(amount),0) AS total FROM expenses
     WHERE user_id=? AND type='income'
       AND YEAR(date)=YEAR(CURDATE()) AND MONTH(date)=MONTH(CURDATE())",
    'i', $userId
);

// ── Monthly expenses ────────────────────────────────────────
$monthlyExpenses = safeQuery(
    $conn,
    "SELECT COALESCE(SUM(amount),0) AS total FROM expenses
     WHERE user_id=? AND type='expense'
       AND YEAR(date)=YEAR(CURDATE()) AND MONTH(date)=MONTH(CURDATE())",
    'i', $userId
);

// ── All-time balance ────────────────────────────────────────
$totalBalance = 0.0;
$bStmt = $conn->prepare(
    "SELECT
       COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END),0) AS ti,
       COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS te
     FROM expenses WHERE user_id=?"
);
if ($bStmt) {
    $bStmt->bind_param('i', $userId);
    $bStmt->execute();
    $bal = $bStmt->get_result()->fetch_assoc();
    $totalBalance = (float)$bal['ti'] - (float)$bal['te'];
    $bStmt->close();
}

// ── Recent 10 transactions ──────────────────────────────────
$recentTx = [];
$rStmt = $conn->prepare(
    "SELECT id, amount, category, description, type, date
     FROM expenses WHERE user_id=?
     ORDER BY date DESC, created_at DESC LIMIT 10"
);
if ($rStmt) {
    $rStmt->bind_param('i', $userId);
    $rStmt->execute();
    $res = $rStmt->get_result();
    while ($r = $res->fetch_assoc()) $recentTx[] = $r;
    $rStmt->close();
}

$conn->close();

$netSavings  = $monthlyIncome - $monthlyExpenses;
$savingsPct  = $monthlyIncome > 0 ? round(($netSavings / $monthlyIncome) * 100) : 0;
$savingsPct  = max(0, min(100, $savingsPct));
$avatarUrl   = 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=6366f1&color=fff&rounded=true';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #toast-container {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            z-index: 9999; display: flex; flex-direction: column; gap: .5rem;
        }
        .fp-toast {
            min-width: 260px; padding: .75rem 1.1rem; border-radius: .75rem;
            font-size: .9rem; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            animation: slideIn .3s ease;
        }
        .fp-toast.success { background: #10b981; color: #fff; }
        .fp-toast.error   { background: #ef4444; color: #fff; }
        @keyframes slideIn {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .stat-card { transition: box-shadow .2s; }
        .stat-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,.08); }
    </style>
</head>
<body>

<div id="toast-container"></div>

<div class="app-wrapper">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-wallet2"></i> FinPulse
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="transactions.html" class="menu-item">
                <i class="bi bi-list-columns-reverse"></i> Transactions
            </a>
            <a href="#" class="menu-item">
                <i class="bi bi-pie-chart"></i> Reports
            </a>
            <a href="#" class="menu-item">
                <i class="bi bi-tags"></i> Categories
            </a>
        </nav>
        <div class="mt-auto p-3 border-top" style="border-top-color:var(--border-light)!important">
            <a href="auth/logout.php" class="menu-item text-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>

    <!-- ═══ MAIN ═══ -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggler me-3" id="sidebar-toggler">
                    <i class="bi bi-list"></i>
                </button>
                <h4 class="mb-0 fw-bold d-none d-sm-block">
                    Welcome back, <?= $username ?>! 👋
                </h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle Dark Mode">
                    <i id="theme-icon" class="bi bi-moon"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none dropdown-toggle text-body d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="dropdown">
                        <img src="<?= $avatarUrl ?>" alt="<?= $username ?>" width="36" height="36" class="rounded-circle">
                        <span class="fw-medium d-none d-md-block"><?= $username ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><span class="dropdown-item-text text-muted small">Signed in as <strong><?= $username ?></strong></span></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-wrapper">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-semibold mb-0">Financial Overview</h5>
                    <small class="text-muted">
                        <?= date('F Y') ?> — All stats update in real time
                    </small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Transaction
                </button>
            </div>

            <!-- ─── Stat Cards ─── -->
            <div class="row g-4 mb-4">

                <!-- Total Balance -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="stat-label">Total Balance</h6>
                                <h3 class="stat-value <?= $totalBalance < 0 ? 'text-danger' : '' ?>" id="stat-balance">
                                    <?= ($totalBalance < 0 ? '-' : '') ?>$<?= number_format(abs($totalBalance), 2) ?>
                                </h3>
                                <span class="badge <?= $totalBalance >= 0 ? 'bg-success-light' : 'bg-danger-light' ?>">
                                    <i class="bi bi-wallet2 me-1"></i> All-time net
                                </span>
                            </div>
                            <div class="stat-icon bg-primary-light"><i class="bi bi-wallet2"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Income -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="stat-label">Income (This Month)</h6>
                                <h3 class="stat-value text-success" id="stat-income">
                                    $<?= number_format($monthlyIncome, 2) ?>
                                </h3>
                                <span class="badge bg-success-light">
                                    <i class="bi bi-arrow-down-left me-1"></i> <?= date('M Y') ?>
                                </span>
                            </div>
                            <div class="stat-icon bg-success-light"><i class="bi bi-arrow-down-left"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Expenses -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="stat-label">Expenses (This Month)</h6>
                                <h3 class="stat-value text-danger" id="stat-expenses">
                                    $<?= number_format($monthlyExpenses, 2) ?>
                                </h3>
                                <span class="badge bg-danger-light">
                                    <i class="bi bi-arrow-up-right me-1"></i> <?= date('M Y') ?>
                                </span>
                            </div>
                            <div class="stat-icon bg-danger-light"><i class="bi bi-arrow-up-right"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Net Savings -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="w-100">
                                <h6 class="stat-label">Net Savings (This Month)</h6>
                                <h3 class="stat-value <?= $netSavings < 0 ? 'text-danger' : '' ?>" id="stat-savings">
                                    <?= ($netSavings < 0 ? '-' : '') ?>$<?= number_format(abs($netSavings), 2) ?>
                                </h3>
                                <div class="progress mt-2" style="height:6px;">
                                    <div class="progress-bar bg-primary" id="savings-bar"
                                         style="width:<?= $savingsPct ?>%"></div>
                                </div>
                                <small class="text-muted mt-1 d-block" id="savings-pct">
                                    <?= $savingsPct ?>% of income saved
                                </small>
                            </div>
                            <div class="stat-icon bg-warning-light"><i class="bi bi-piggy-bank"></i></div>
                        </div>
                    </div>
                </div>

            </div><!-- /row -->

            <!-- ─── Charts ─── -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="stat-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-semibold mb-0">Income &amp; Expense Trend</h5>
                            <span class="badge bg-primary-light text-primary-custom">Last 6 months</span>
                        </div>
                        <div style="height:280px; position:relative;">
                            <canvas id="expenseTrendChart"></canvas>
                            <div id="trend-empty" class="position-absolute top-50 start-50 translate-middle text-center text-muted d-none">
                                <i class="bi bi-bar-chart fs-1 opacity-25 d-block mb-2"></i>
                                Add transactions to see your trend
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="stat-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-semibold mb-0">By Category</h5>
                            <span class="badge bg-primary-light text-primary-custom"><?= date('M Y') ?></span>
                        </div>
                        <div style="height:280px; position:relative; display:flex; align-items:center; justify-content:center;">
                            <canvas id="categoryChart"></canvas>
                            <div id="cat-empty" class="text-center text-muted d-none">
                                <i class="bi bi-pie-chart fs-1 opacity-25 d-block mb-2"></i>
                                No expense data yet
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Recent Transactions ─── -->
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-semibold mb-0">Recent Transactions</h5>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tx-tbody">
                        <?php if (empty($recentTx)): ?>
                            <tr id="empty-row">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    No transactions yet — click <strong>Add Transaction</strong> to get started!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTx as $tx): ?>
                            <tr id="tx-row-<?= (int)$tx['id'] ?>">
                                <td class="text-nowrap"><?= date('M d, Y', strtotime($tx['date'])) ?></td>
                                <td><span class="fw-medium"><?= sanitize($tx['description'] ?: '—') ?></span></td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-body fw-normal">
                                        <?= sanitize($tx['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill badge-<?= $tx['type'] === 'income' ? 'income' : 'expense' ?>">
                                        <?= ucfirst($tx['type']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold <?= $tx['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx['type'] === 'income' ? '+' : '−' ?>$<?= number_format((float)$tx['amount'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                            onclick="deleteTransaction(<?= (int)$tx['id'] ?>, this)"
                                            title="Delete transaction">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /content-wrapper -->
    </main>
</div><!-- /app-wrapper -->


<!-- ═══ ADD TRANSACTION MODAL ═══ -->
<div class="modal fade" id="addTransactionModal" tabindex="-1"
     aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:var(--card-light); border-color:var(--border-light);">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="addTransactionModalLabel">New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTxForm" novalidate>

                    <!-- Type toggle -->
                    <div class="d-flex gap-3 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="type" id="typeExpense" value="expense" checked>
                            <label class="form-check-label fw-semibold text-danger" for="typeExpense">
                                <i class="bi bi-arrow-up-right me-1"></i>Expense
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="type" id="typeIncome" value="income">
                            <label class="form-check-label fw-semibold text-success" for="typeIncome">
                                <i class="bi bi-arrow-down-left me-1"></i>Income
                            </label>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="tx-amount"
                                   name="amount" placeholder="0.00" step="0.01" min="0.01" required>
                            <div class="invalid-feedback">Please enter a valid amount greater than $0.</div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <input type="text" class="form-control" id="tx-description"
                               name="description" placeholder="e.g. Grocery store, Salary" maxlength="255">
                    </div>

                    <!-- Category + Date -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="tx-category" name="category" required>
                                <option value="">Select...</option>
                                <optgroup label="── Expenses ──">
                                    <option value="Food">🍔 Food</option>
                                    <option value="Transport">🚗 Transport</option>
                                    <option value="Bills">🧾 Bills</option>
                                    <option value="Shopping">🛍️ Shopping</option>
                                    <option value="Health">🏥 Health</option>
                                    <option value="Entertainment">🎬 Entertainment</option>
                                    <option value="Education">📚 Education</option>
                                    <option value="Other">📌 Other</option>
                                </optgroup>
                                <optgroup label="── Income ──">
                                    <option value="Salary">💼 Salary</option>
                                    <option value="Freelance">💻 Freelance</option>
                                    <option value="Investment">📈 Investment</option>
                                    <option value="Gift">🎁 Gift</option>
                                    <option value="Other Income">💰 Other Income</option>
                                </optgroup>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tx-date" name="date" required>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-link text-muted text-decoration-none"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="saveTxBtn">
                    <span id="saveTxSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    Save Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
// ══════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════
function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `fp-toast ${type}`;
    el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}`;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function fmtMoney(n) {
    const v = parseFloat(n);
    return (v < 0 ? '-$' : '$') + Math.abs(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function fmtDate(s) {
    const d = new Date(s + 'T00:00:00');
    return d.toLocaleDateString('en-US', {year:'numeric', month:'short', day:'2-digit'});
}

function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ══════════════════════════════════════════════════════════
//  STAT CARD UPDATER
// ══════════════════════════════════════════════════════════
function updateStatCards(data) {
    document.getElementById('stat-balance').textContent  = fmtMoney(data.total_balance);
    document.getElementById('stat-income').textContent   = fmtMoney(data.monthly_income);
    document.getElementById('stat-expenses').textContent = fmtMoney(data.monthly_expenses);
    const net = data.monthly_income - data.monthly_expenses;
    document.getElementById('stat-savings').textContent  = fmtMoney(net);
    const pct = data.monthly_income > 0
        ? Math.max(0, Math.min(100, Math.round((net / data.monthly_income) * 100)))
        : 0;
    document.getElementById('savings-bar').style.width = pct + '%';
    document.getElementById('savings-pct').textContent  = pct + '% of income saved';
}

// ══════════════════════════════════════════════════════════
//  CHARTS
// ══════════════════════════════════════════════════════════
let trendChart = null, catChart = null;
const PALETTE  = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316'];

function buildCharts(data) {
    // ── Trend chart ──
    const trendCtx = document.getElementById('expenseTrendChart').getContext('2d');
    const labels   = data.trend.map(t => t.month);
    const incomes  = data.trend.map(t => t.income);
    const expenses = data.trend.map(t => t.expenses);
    const hasData  = incomes.some(v => v > 0) || expenses.some(v => v > 0);

    document.getElementById('trend-empty').classList.toggle('d-none', hasData);
    document.getElementById('expenseTrendChart').style.display = hasData ? 'block' : 'none';

    if (hasData) {
        if (trendChart) {
            trendChart.data.labels = labels;
            trendChart.data.datasets[0].data = incomes;
            trendChart.data.datasets[1].data = expenses;
            trendChart.update();
        } else {
            trendChart = new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label:'Income',   data: incomes,  backgroundColor:'rgba(16,185,129,.75)', borderRadius:6 },
                        { label:'Expenses', data: expenses, backgroundColor:'rgba(239,68,68,.75)',  borderRadius:6 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend:{ position:'top' } },
                    scales: { y:{ beginAtZero:true, ticks:{ callback: v => '$'+v } } }
                }
            });
        }
    }

    // ── Category chart ──
    const catCtx    = document.getElementById('categoryChart').getContext('2d');
    const catLabels = data.category_breakdown.map(c => c.category);
    const catData   = data.category_breakdown.map(c => c.total);
    const hasCat    = catData.length > 0;

    document.getElementById('cat-empty').classList.toggle('d-none', hasCat);
    document.getElementById('categoryChart').style.display = hasCat ? 'block' : 'none';

    if (hasCat) {
        if (catChart) {
            catChart.data.labels = catLabels;
            catChart.data.datasets[0].data = catData;
            catChart.data.datasets[0].backgroundColor = PALETTE.slice(0, catLabels.length);
            catChart.update();
        } else {
            catChart = new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{ data: catData, backgroundColor: PALETTE, borderWidth:2 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend:{ position:'bottom' } },
                    cutout: '65%'
                }
            });
        }
    }
}

// ══════════════════════════════════════════════════════════
//  REFRESH (stats + charts together)
// ══════════════════════════════════════════════════════════
async function refreshDashboard() {
    try {
        const res  = await fetch('api/get_summary.php');
        const data = await res.json();
        if (data.error) { console.warn('Summary error:', data.error); return; }
        updateStatCards(data);
        buildCharts(data);
    } catch (e) {
        console.error('Dashboard refresh error:', e);
    }
}

// ══════════════════════════════════════════════════════════
//  ADD TRANSACTION
// ══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Default date = today
    document.getElementById('tx-date').valueAsDate = new Date();

    // Load charts on page load
    refreshDashboard();
});

document.getElementById('saveTxBtn').addEventListener('click', async () => {
    const form    = document.getElementById('addTxForm');
    const spinner = document.getElementById('saveTxSpinner');
    const btn     = document.getElementById('saveTxBtn');

    form.classList.add('was-validated');
    if (!form.checkValidity()) return;

    spinner.classList.remove('d-none');
    btn.disabled = true;

    const formData = new FormData(form);

    try {
        const res  = await fetch('api/add_expense.php', { method:'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            // Close & reset modal
            bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('tx-date').valueAsDate = new Date();

            // Pull fresh values back from form
            const amount   = parseFloat(formData.get('amount'));
            const type     = formData.get('type');
            const category = formData.get('category');
            const desc     = formData.get('description') || '—';
            const date     = formData.get('date');
            const id       = json.id;

            // Remove empty placeholder
            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            // Prepend row to table
            const tbody = document.getElementById('tx-tbody');
            const tr    = document.createElement('tr');
            tr.id = `tx-row-${id}`;
            tr.innerHTML = `
                <td class="text-nowrap">${fmtDate(date)}</td>
                <td><span class="fw-medium">${escHtml(desc)}</span></td>
                <td><span class="badge bg-secondary bg-opacity-10 text-body fw-normal">${escHtml(category)}</span></td>
                <td><span class="badge rounded-pill badge-${type}">${type.charAt(0).toUpperCase()+type.slice(1)}</span></td>
                <td class="text-end fw-semibold ${type==='income'?'text-success':'text-danger'}">
                    ${type==='income'?'+':'−'}${fmtMoney(amount)}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="deleteTransaction(${id}, this)" title="Delete">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>`;
            tbody.insertBefore(tr, tbody.firstChild);

            // Refresh stats & charts
            refreshDashboard();
            showToast('Transaction saved successfully!');
        } else {
            showToast(json.error || 'Failed to save. Please try again.', 'error');
        }
    } catch {
        showToast('Network error. Is the server running?', 'error');
    } finally {
        spinner.classList.add('d-none');
        btn.disabled = false;
    }
});

// ══════════════════════════════════════════════════════════
//  DELETE TRANSACTION
// ══════════════════════════════════════════════════════════
async function deleteTransaction(id, btn) {
    if (!confirm('Delete this transaction? This cannot be undone.')) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const body = new FormData();
    body.append('id', id);

    try {
        const res  = await fetch('api/delete_expense.php', { method:'POST', body });
        const json = await res.json();

        if (json.success) {
            const row = document.getElementById(`tx-row-${id}`);
            if (row) {
                row.style.opacity = '0';
                row.style.transition = 'opacity .3s';
                setTimeout(() => {
                    row.remove();
                    if (!document.querySelector('#tx-tbody tr')) {
                        document.getElementById('tx-tbody').innerHTML = `
                            <tr id="empty-row">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    No transactions yet — click <strong>Add Transaction</strong> to get started!
                                </td>
                            </tr>`;
                    }
                }, 300);
            }
            refreshDashboard();
            showToast('Transaction deleted.');
        } else {
            showToast(json.error || 'Could not delete.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3"></i>';
        }
    } catch {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3"></i>';
    }
}
</script>
</body>
</html>
