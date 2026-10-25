
<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash
             FROM admins
             WHERE username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify(
            $password,
            $admin['password_hash']
        )) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | FreshFold Laundry</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <main class="admin-login">
        <h1>FreshFold Laundry</h1>
        <h2>Administrator Login</h2>
        <p>Sign in to manage your laundry shop.</p>

        <?php if ($error !== ''): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="post" action="">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Log In</button>
        </form>

        <p><a href="../index.php">Back to Home</a></p>
    </main>
</body>
</html>
