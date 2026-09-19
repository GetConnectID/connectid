<?php

require_once __DIR__ . '/backend/session.php';
require_once __DIR__ . '/backend/csrf.php';

$token = trim($_GET['token'] ?? '');
$csrfToken = csrfToken();

$error = $_GET['error'] ?? '';
$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reset Password | ConnectID</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            background: #0b0b0b;
            color: #ffffff;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #151515;
            border: 1px solid #292929;
            border-radius: 14px;
            padding: 30px;
        }

        h1 {
            margin-top: 0;
        }

        .subtitle {
            color: #999999;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #333333;
            border-radius: 8px;
            background: #0d0d0d;
            color: #ffffff;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #ff7a00;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #e86d00;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            background: #1c1c1c;
            color: #cccccc;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .back {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #999999;
            text-decoration: none;
        }

        .back:hover {
            color: #ffffff;
        }
    </style>
</head>

<body>

<div class="card">

    <h1>Reset password</h1>

    <?php if ($success): ?>

        <div class="message">
            Your password has been changed successfully.
        </div>

        <a
            class="back"
            href="login.html"
        >
            Continue to login
        </a>

    <?php elseif ($error === 'invalid_token'): ?>

        <div class="message">
            This password reset link is invalid or has expired.
        </div>

        <a
            class="back"
            href="forgot_password.php"
        >
            Request a new reset
        </a>

    <?php elseif ($error === 'password'): ?>

        <div class="message">
            Passwords must match and contain at least 8 characters.
        </div>

        <form method="post" action="backend/perform_password_reset.php">

            <input
                type="password"
                name="password"
                placeholder="New password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <input
                type="password"
                name="password_confirm"
                placeholder="Confirm new password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token) ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <button type="submit">
                Change password
            </button>

        </form>

    <?php elseif ($token === ''): ?>

        <div class="message">
            A valid password reset token is required.
        </div>

        <a
            class="back"
            href="forgot_password.php"
        >
            Request a new reset
        </a>

    <?php else: ?>

        <div class="subtitle">
            Choose a new password for your ConnectID account.
        </div>

        <form method="post" action="backend/perform_password_reset.php">

            <input
                type="password"
                name="password"
                placeholder="New password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <input
                type="password"
                name="password_confirm"
                placeholder="Confirm new password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token) ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <button type="submit">
                Change password
            </button>

        </form>

    <?php endif; ?>

</div>

</body>
</html>
