<?php
// index.php — PHP landing page with session awareness and contact form
session_start();
require_once 'includes/db.php';

$loggedIn       = isset($_SESSION['user_id']);
$username       = $loggedIn ? htmlspecialchars($_SESSION['username']) : '';
$contactSuccess = isset($_GET['success']) && $_GET['success'] === '1';
$contactErrors  = $_SESSION['contact_errors'] ?? [];
unset($_SESSION['contact_errors']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinPulse - Your Personal Finance Tracker</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Landing Page Specific Styles */
        .navbar-landing {
            background-color: var(--card-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] .navbar-landing {
            background-color: var(--card-dark);
            border-bottom: 1px solid var(--border-dark);
            box-shadow: none;
        }

        .hero-section {
            padding: 100px 0 80px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            min-height: 85vh;
            display: flex;
            align-items: center;
        }

        [data-bs-theme="dark"] .hero-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(15, 23, 42, 1) 100%);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-light);
            margin-bottom: 2rem;
            max-width: 600px;
            line-height: 1.6;
        }

        [data-bs-theme="dark"] .hero-subtitle {
            color: var(--text-dark);
            opacity: 0.8;
        }

        .feature-card {
            background-color: var(--card-light);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        [data-bs-theme="dark"] .feature-card {
            background-color: var(--card-dark);
            border-color: var(--border-dark);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .feature-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(99, 102, 241, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        /* Contact Section */
        .contact-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            padding: 5rem 0;
        }

        [data-bs-theme="dark"] .contact-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(15,23,42,0.8) 100%);
        }

        .contact-card {
            background: var(--card-light);
            border: 1px solid var(--border-light);
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }

        [data-bs-theme="dark"] .contact-card {
            background: var(--card-dark);
            border-color: var(--border-dark);
        }

        .footer {
            background-color: var(--bg-dark);
            color: rgba(255, 255, 255, 0.7);
            padding: 4rem 0 2rem;
        }

        .footer-logo {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-link:hover { color: white; }

        .mockup-img {
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-light);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }

        [data-bs-theme="dark"] .mockup-img {
            border-color: var(--border-dark);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .mockup-img:hover { transform: perspective(1000px) rotateY(0deg); }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-landing fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary-custom" href="index.php">
                <i class="bi bi-wallet2 fs-3"></i>
                <span class="fs-4">FinPulse</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto fw-medium">
                    <li class="nav-item"><a class="nav-link px-3 active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#about">About</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                    <button id="theme-toggle" class="theme-toggle" aria-label="Toggle Dark Mode">
                        <i id="theme-icon" class="bi bi-moon"></i>
                    </button>
                    <?php if ($loggedIn): ?>
                        <span class="fw-medium text-primary-custom">
                            <i class="bi bi-person-circle me-1"></i><?= $username ?>
                        </span>
                        <a href="auth/logout.php" class="btn btn-outline-danger px-4 fw-medium">Logout</a>
                    <?php else: ?>
                        <a href="login.php"    class="btn btn-outline-primary px-4 fw-medium">Log In</a>
                        <a href="register.php" class="btn btn-primary px-4 fw-medium">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-6 mb-5 mb-lg-0 z-1">
                    <span class="badge bg-primary-light px-3 py-2 rounded-pill mb-3 fw-semibold">Take Control of Your Future</span>
                    <h1 class="hero-title">Smart Personal Finance Management</h1>
                    <p class="hero-subtitle">FinPulse helps you track your income, monitor expenses, and visualize your financial health in one beautiful, easy-to-use platform.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if ($loggedIn): ?>
                            <a href="dashboard.php" class="btn btn-primary btn-lg px-4 py-3 fw-medium d-flex align-items-center gap-2">
                                Go to Dashboard <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary btn-lg px-4 py-3 fw-medium d-flex align-items-center gap-2">
                                Start Tracking for Free <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-outline-secondary btn-lg px-4 py-3 fw-medium">
                            Explore Features
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 position-relative">
                    <div class="position-absolute top-0 end-0 bg-primary opacity-10 rounded-circle" style="width: 300px; height: 300px; filter: blur(50px); z-index: 0;"></div>
                    <div class="position-absolute bottom-0 start-0 bg-success opacity-10 rounded-circle" style="width: 250px; height: 250px; filter: blur(40px); z-index: 0;"></div>
                    <div class="position-relative z-1 p-3 bg-white rounded-4 shadow-sm" style="border: 1px solid var(--border-light);">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop" alt="Dashboard Preview" class="img-fluid rounded-3">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 my-5">
        <div class="container py-4">
            <div class="text-center mb-5 pb-3">
                <span class="text-primary-custom fw-bold text-uppercase tracking-wider">Features</span>
                <h2 class="fw-bold mt-2 mb-3 display-6">Everything you need to manage money</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Our comprehensive toolkit allows you to break free from spreadsheets and truly understand your financial habits.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="bi bi-graph-up-arrow feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Track Expenses</h4>
                        <p class="text-muted mb-0">Record all your transactions instantly. Categorize them to see exactly where your money goes every month.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="bi bi-pie-chart feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Beautiful Visuals</h4>
                        <p class="text-muted mb-0">Interactive charts and graphs provide you with clear insights into your income versus expense trends.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="bi bi-shield-check feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Secure &amp; Private</h4>
                        <p class="text-muted mb-0">Your financial data is yours alone. We ensure robust security protocols to keep your information safe.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary-light">
        <div class="container py-5 text-center">
            <h2 class="fw-bold mb-4">Ready to achieve your financial goals?</h2>
            <p class="text-muted mx-auto mb-5" style="max-width: 600px;">Join thousands of users who have transformed their financial lives with FinPulse today.</p>
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill fw-medium">
                    <i class="bi bi-speedometer2 me-2"></i>Open Dashboard
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill fw-medium">Create Your Free Account</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-primary-custom fw-bold text-uppercase">Contact Us</span>
                <h2 class="fw-bold mt-2 mb-3 display-6">Get in Touch</h2>
                <p class="text-muted mx-auto" style="max-width: 550px;">Have a question or feedback? We'd love to hear from you. Send us a message and we'll get back to you shortly.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="contact-card">

                        <!-- Success Banner -->
                        <?php if ($contactSuccess): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <span>Your message has been sent! We'll get back to you soon.</span>
                        </div>
                        <?php endif; ?>

                        <!-- Error Messages -->
                        <?php if (!empty($contactErrors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php foreach ($contactErrors as $err): ?>
                                <div><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form action="auth/contact.php" method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="contact_name" class="form-label fw-medium">Your Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" id="contact_name" name="contact_name" placeholder="Jane Doe" required>
                                        <div class="invalid-feedback">Please enter your name.</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="contact_email" class="form-label fw-medium">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control border-start-0 ps-0" id="contact_email" name="contact_email" placeholder="jane@example.com" required>
                                        <div class="invalid-feedback">Please enter a valid email.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="contact_message" class="form-label fw-medium">Message</label>
                                    <textarea class="form-control" id="contact_message" name="contact_message" rows="5" placeholder="Write your message here..." required></textarea>
                                    <div class="invalid-feedback">Please enter a message.</div>
                                </div>
                                <div class="col-12 pt-1">
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-medium">
                                        <i class="bi bi-send me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="about" class="footer">
        <div class="container">
            <div class="row gy-4 mb-5">
                <div class="col-lg-4 pe-lg-5">
                    <a href="index.php" class="footer-logo d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-wallet2 text-primary-custom"></i>
                        <span>FinPulse</span>
                    </a>
                    <p class="mb-4 text-opacity-75">Your modern solution for personal finance management. Stop guessing, start tracking, and reach your goals faster.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="footer-link fs-5"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="footer-link fs-5"><i class="bi bi-github"></i></a>
                        <a href="#" class="footer-link fs-5"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white mb-3">Product</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="#features" class="footer-link">Features</a></li>
                        <li><a href="#" class="footer-link">Pricing</a></li>
                        <li><a href="#" class="footer-link">Security</a></li>
                        <li><a href="#" class="footer-link">Updates</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white mb-3">Company</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="#about"   class="footer-link">About Us</a></li>
                        <li><a href="#"        class="footer-link">Careers</a></li>
                        <li><a href="#"        class="footer-link">Blog</a></li>
                        <li><a href="#contact" class="footer-link">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white mb-3">Subscribe to Newsletter</h5>
                    <p class="text-opacity-75 mb-3">Get the latest financial tips and feature updates directly to your inbox.</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Email address" aria-label="Email address">
                        <button class="btn btn-primary" type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="border-top border-secondary pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <p class="mb-0 text-opacity-75">&copy; 2026 FinPulse Tracker. All rights reserved.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="footer-link small">Privacy Policy</a>
                    <a href="#" class="footer-link small">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <script>
        // Shrink navbar on scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar-landing');
            if (window.scrollY > 50) {
                navbar.style.padding = '0.5rem 0';
                navbar.classList.add('shadow-sm');
            } else {
                navbar.style.padding = '1rem 0';
                navbar.classList.remove('shadow-sm');
            }
        });

        // Bootstrap form validation for contact form
        (() => {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', event => {
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
