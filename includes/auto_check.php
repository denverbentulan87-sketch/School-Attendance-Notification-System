<?php
/**
 * auth_check.php
 * ─────────────────────────────────────────────
 * Include this at the VERY TOP of every protected
 * page (admin dashboard, student dashboard, etc.)
 * BEFORE any HTML output.
 *
 * Usage:
 *   <?php require_once 'includes/auth_check.php'; ?>
 */

// 1. Kill browser/proxy caching so the page is NEVER
//    served from cache after the user logs out.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past

// 2. Start (or resume) the session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. If there is no active session, bounce the user
//    back to the login page — no exceptions.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please+sign+in+to+access+that+page.");
    exit();
}

// 4. Optional: role guard — pass the required role when including.
//    Example at top of admin page:
//      $required_role = 'admin';
//      require_once 'includes/auth_check.php';
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header("Location: index.php?error=Access+denied.");
    exit();
}