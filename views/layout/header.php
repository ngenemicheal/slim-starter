<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Slim Starter') ?> — <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Slim Starter') ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<nav class="nav">
    <a href="/" class="nav-brand"><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Slim Starter') ?></a>
    <ul class="nav-links">
        <?php if (!empty($user)): ?>
            <li><a href="/dashboard">Dashboard</a></li>
            <li><a href="/logout">Logout</a></li>
        <?php else: ?>
            <li><a href="/login">Sign in</a></li>
            <li><a href="/register" class="btn-nav">Get started</a></li>
        <?php endif; ?>
    </ul>
</nav>
