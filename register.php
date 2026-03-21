<?php
// register.php — PHP version of register.html
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Grab any session messages then clear them
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FinPulse</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="auth-wrapper">
        <!-- Sidebar -->
        <div class="auth-sidebar d-none d-lg-flex">
            <div class="text-center">
                <i class="bi bi-wallet2 text-white mb-4" style="font-size: 4rem;"></i>
                <h1>FinPulse</h1>
                <p>Join us today to take control of your money, track your expenses, and reach your financial goals.</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="auth-main w-100 position-relative">
            <!-- Theme Toggle -->
            <button id="theme-toggle" class="theme-toggle position-absolute top-0 end-0 m-4" aria-label="Toggle Dark Mode">
                <i id="theme-icon" class="bi bi-moon"></i>
            </button>

            <div class="auth-card">
                <div class="text-center mb-4 d-lg-none">
                    <i class="bi bi-wallet2 text-primary-custom mb-2" style="font-size: 2.5rem;"></i>
                    <h2 class="fw-bold">FinPulse</h2>
                </div>

                <h3 class="fw-bold mb-1">Create an Account</h3>
                <p class="text-muted mb-4">Start your financial journey with us.</p>

                <!-- Session error messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php foreach ($errors as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form class="needs-validation" novalidate action="auth/register.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="name" name="name" placeholder="John Doe" required>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" placeholder="name@example.com" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" minlength="6" required>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label fw-medium">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="confirmPassword" name="confirmPassword" placeholder="••••••••" required>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Sign Up</button>
                    </div>

                    <div class="text-center">
                        <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-decoration-none text-primary-custom fw-semibold">Log in</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <script>
        // Bootstrap form validation + password-match check
        (() => {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', event => {
                    const pw  = document.getElementById('password');
                    const cpw = document.getElementById('confirmPassword');
                    if (pw.value !== cpw.value) {
                        cpw.setCustomValidity('Passwords do not match.');
                    } else {
                        cpw.setCustomValidity('');
                    }
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
