<?php
// ============================================================
// includes/functions.php — Shared helper functions
// ============================================================

/**
 * Redirect to a URL and stop execution.
 *
 * @param string $url  Relative or absolute URL to redirect to.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Require the user to be logged in.
 * Redirects to login.php (relative to project root) if not authenticated.
 *
 * @param string $loginPage  Path to the login page, relative to the calling file.
 */
function requireLogin(string $loginPage = '../login.php'): void {
    if (empty($_SESSION['user_id'])) {
        redirect($loginPage);
    }
}

/**
 * Safely output a string in HTML context.
 *
 * @param  mixed  $value
 * @return string
 */
function sanitize($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Send a JSON response and terminate.
 *
 * @param mixed $data       Data to encode as JSON.
 * @param int   $httpCode   HTTP status code (default 200).
 */
function jsonResponse($data, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Return the current logged-in user's ID from the session.
 *
 * @return int|null
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
