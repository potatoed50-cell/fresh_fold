
<?php
require __DIR__ . '/../includes/auth.php';

// Clear all session data.
$_SESSION = [];

// Remove the session cookie from the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the current session.
session_destroy();

// Return to the admin login page.
header('Location: login.php');
exit;
