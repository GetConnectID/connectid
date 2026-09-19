<?php

require_once __DIR__ . '/backend/session.php';
require_once __DIR__ . '/backend/csrf.php';

$csrfToken = csrfToken();

$sent = isset($_GET['sent']);
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Forgot Password | ConnectID</title>

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
            margin-bottom: 10px;
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

    <h1>Forgot your password?</h1>

    <div class="subtitle">
        Enter your ConnectID username and we'll send instructions
        to the recovery method connected to your account.
    </div>

    <?php if ($sent): ?>

        <div class="message">
            If an account exists for that username, recovery
            instructions have been requested.
        </div>

    <?php endif; ?>

    <?php if ($error === 'invalid'): ?>

        <div class="message">
            Please enter a valid username.
        </div>

    <?php endif; ?>

    <form
        method="post"
        action="backend/request_password_reset.php"
    >

        <input
            type="text"
            name="username"
            placeholder="@username"
            maxlength="30"
            autocomplete="username"
            required
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken) ?>"
        >

        <button type="submit">
            Request password reset
        </button>

    </form>

    <a
        class="back"
        href="login.html"
    >
        Back to login
    </a>

</div>

</body>
</html>
