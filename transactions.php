<?php
// ============================================================
// transactions.php — Full transaction history for logged-in users
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin('login.php');

$userId   = (int) $_SESSION['user_id'];
$username = sanitize($_SESSION['username']);

// ── Filters from GET params ─────────────────────────────────
$filterCategory = trim($_GET['category'] ?? '');
$filterType     = trim($_GET['type']     ?? '');
$filterSearch   = trim($_GET['search']   ?? '');
$page           = max(1, (int) ($_GET['page'] ?? 1));
$perPage        = 15;
$offset         = ($page - 1) * $perPage;

// ── Build WHERE clause ──────────────────────────────────────
$whereClauses = ['user_id = ?'];
$bindTypes    = 'i';
$bindValues   = [$userId];

if (!empty($filterCategory)) {
    $whereClauses[] = 'category = ?';
    $bindTypes .= 's';
    $bindValues[] = $filterCategory;
}
if (!empty($filterType) && in_array($filterType, ['income','expense'])) {
    $whereClauses[] = 'type = ?';
    $bindTypes .= 's';
    $bindValues[] = $filterType;
}
if (!empty($filterSearch)) {
    $whereClauses[] = '(description LIKE ? OR category LIKE ?)';
    $bindTypes .= 'ss';
    $like = '%' . $filterSearch . '%';
    $bindValues[] = $like;
    $bindValues[] = $like;
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

// ── Total count (for pagination) ────────────────────────────
$countSQL  = "SELECT COUNT(*) AS total FROM expenses $whereSQL";
$cStmt     = $conn->prepare($countSQL);
if ($cStmt) {
    $cStmt->bind_param($bindTypes, ...$bindValues);
    $cStmt->execute();
    $totalCount = (int) $cStmt->get_result()->fetch_assoc()['total'];
    $cStmt->close();
} else {
    $totalCount = 0;
}
$totalPages = max(1, (int) ceil($totalCount / $perPage));

// ── Fetch transactions ──────────────────────────────────────
$transactions = [];
$fetchSQL = "SELECT id, amount, category, description, type, date
             FROM expenses $whereSQL
             ORDER BY date DESC, created_at DESC
             LIMIT ? OFFSET ?";
$fStmt = $conn->prepare($fetchSQL);
if ($fStmt) {
    $allTypes  = $bindTypes . 'ii';
    $allValues = array_merge($bindValues, [$perPage, $offset]);
    $fStmt->bind_param($allTypes, ...$allValues);
    $fStmt->execute();
    $res = $fStmt->get_result();
    while ($r = $res->fetch_assoc()) $transactions[] = $r;
    $fStmt->close();
}

// ── Distinct categories for filter dropdown ─────────────────
$categories = [];
$catStmt = $conn->prepare("SELECT DISTINCT category FROM expenses WHERE user_id=? ORDER BY category ASC");
if ($catStmt) {
    $catStmt->bind_param('i', $userId);
    $catStmt->execute();
    $catRes = $catStmt->get_result();
    while ($c = $catRes->fetch_assoc()) $categories[] = $c['category'];
    $catStmt->close();
}

// ── Monthly totals for summary strip ───────────────────────
$monthIncome = 0.0; $monthExpense = 0.0;
$mStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END),0) AS mi,
        COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS me
     FROM expenses WHERE user_id=?
       AND YEAR(date)=YEAR(CURDATE()) AND MONTH(date)=MONTH(CURDATE())"
);
if ($mStmt) {
    $mStmt->bind_param('i', $userId);
    $mStmt->execute();
    $mr = $mStmt->get_result()->fetch_assoc();
    $monthIncome  = (float)$mr['mi'];
    $monthExpense = (float)$mr['me'];
    $mStmt->close();
}
$conn->close();

$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=6366f1&color=fff&rounded=true';

// ── Build pagination URL helper ─────────────────────────────
function pageUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return 'transactions.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - FinPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        #toast-container {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            z-index: 9999; display: flex; flex-direction: column; gap: .5rem;
        }
        .fp-toast {
            min-width: 240px; padding: .7rem 1rem; border-radius: .75rem;
            font-size: .875rem; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            animation: slideIn .3s ease;
        }
        .fp-toast.success { background:#10b981; color:#fff; }
        .fp-toast.error   { background:#ef4444; color:#fff; }
        @keyframes slideIn {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .summary-strip .card {
            border: none;
            border-radius: 1rem;
        }
        tr.deleting { opacity: .4; transition: opacity .3s; }
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
            <a href="dashboard.php" class="menu-item">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="transactions.php" class="menu-item active">
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
                <h4 class="mb-0 fw-bold d-none d-sm-block">Transactions</h4>
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

        <!-- Content -->
        <div class="content-wrapper">

            <!-- ── Summary strip ── -->
            <div class="row g-3 mb-4 summary-strip">
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center py-3">
                        <div class="stat-label mb-1">This Month Income</div>
                        <div class="fw-bold text-success fs-5">$<?= number_format($monthIncome, 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center py-3">
                        <div class="stat-label mb-1">This Month Expenses</div>
                        <div class="fw-bold text-danger fs-5">$<?= number_format($monthExpense, 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center py-3">
                        <div class="stat-label mb-1">Net This Month</div>
                        <?php $net = $monthIncome - $monthExpense; ?>
                        <div class="fw-bold fs-5 <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($net < 0 ? '-' : '') ?>$<?= number_format(abs($net), 2) ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center py-3">
                        <div class="stat-label mb-1">Total Records</div>
                        <div class="fw-bold fs-5"><?= number_format($totalCount) ?></div>
                    </div>
                </div>
            </div>

            <!-- ── Main Card ── -->
            <div class="stat-card">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h5 class="fw-semibold mb-0">All Transactions</h5>
                        <small class="text-muted">
                            <?= $totalCount ?> record<?= $totalCount !== 1 ? 's' : '' ?> found
                            <?php if (!empty($filterSearch) || !empty($filterCategory) || !empty($filterType)): ?>
                                — <a href="transactions.php" class="text-decoration-none">Clear filters</a>
                            <?php endif; ?>
                        </small>
                    </div>

                    <!-- Filters (GET form — no JS needed) -->
                    <form method="GET" action="transactions.php" class="d-flex flex-wrap gap-2 align-items-center">
                        <!-- Search -->
                        <div class="input-group" style="max-width:210px;">
                            <span class="input-group-text bg-transparent text-muted border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0"
                                   placeholder="Search..." value="<?= sanitize($filterSearch) ?>">
                        </div>

                        <!-- Category -->
                        <select name="category" class="form-select" style="width:auto;" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= sanitize($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>>
                                    <?= sanitize($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Type -->
                        <select name="type" class="form-select" style="width:auto;" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="income"  <?= $filterType === 'income'  ? 'selected' : '' ?>>Income</option>
                            <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>Expense</option>
                        </select>

                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>

                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Add New
                        </a>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-custom mb-0 table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tx-tbody">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    <?php if (!empty($filterSearch) || !empty($filterCategory) || !empty($filterType)): ?>
                                        No transactions match your filters.
                                        <a href="transactions.php">Clear filters</a>
                                    <?php else: ?>
                                        No transactions yet.
                                        <a href="dashboard.php">Add your first one!</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
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
                                            onclick="deleteTx(<?= (int)$tx['id'] ?>, this)"
                                            title="Delete">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4" aria-label="Transactions pagination">
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= pageUrl($page - 1) ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= pageUrl($p) ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= pageUrl($page + 1) ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div><!-- /stat-card -->
        </div><!-- /content-wrapper -->
    </main>
</div><!-- /app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `fp-toast ${type}`;
    el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}`;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

async function deleteTx(id, btn) {
    if (!confirm('Delete this transaction? This cannot be undone.')) return;

    const row = document.getElementById(`tx-row-${id}`);
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    if (row) row.classList.add('deleting');

    const body = new FormData();
    body.append('id', id);

    try {
        const res  = await fetch('api/delete_expense.php', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            if (row) {
                row.style.transition = 'opacity .3s';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Show empty state if no rows left
                    const tbody = document.getElementById('tx-tbody');
                    if (!tbody.querySelector('tr')) {
                        tbody.innerHTML = `
                            <tr><td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                No transactions found.
                            </td></tr>`;
                    }
                }, 300);
            }
            showToast('Transaction deleted successfully.');
        } else {
            showToast(json.error || 'Could not delete.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3"></i>';
            if (row) row.classList.remove('deleting');
        }
    } catch {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3"></i>';
        if (row) row.classList.remove('deleting');
    }
}
</script>
</body>
</html>
