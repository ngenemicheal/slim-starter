<?php require APP_ROOT . '/views/layout/header.php'; ?>

<main class="page">

    <h2 class="section-title" style="margin-bottom:1.5rem">
        Welcome back, <?= htmlspecialchars($user['name']) ?>
    </h2>

    <!-- Stats row -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-label">Account</div>
            <div class="stat-value" style="font-size:1.25rem;word-break:break-all">
                <?= htmlspecialchars($user['email']) ?>
            </div>
            <div class="stat-meta">Signed in &mdash; session active</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">User ID</div>
            <div class="stat-value"><?= (int) $user['id'] ?></div>
            <div class="stat-meta">Primary key in users table</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">PHP Version</div>
            <div class="stat-value" style="font-size:1.5rem"><?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></div>
            <div class="stat-meta"><?= php_uname('s') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">App Mode</div>
            <div class="stat-value" style="font-size:1.5rem;text-transform:capitalize">
                <?= htmlspecialchars($_ENV['APP_MODE'] ?? 'web') ?>
            </div>
            <div class="stat-meta">Set via APP_MODE in .env</div>
        </div>

    </div>

    <!-- Quick info -->
    <div class="card">
        <div class="card-title">Environment</div>
        <div class="card-subtitle">Current runtime configuration</div>
        <ul class="info-list">
            <li>
                <span>APP_ENV</span>
                <span><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'development') ?></span>
            </li>
            <li>
                <span>APP_DEBUG</span>
                <span><?= ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? 'enabled' : 'disabled' ?></span>
            </li>
            <li>
                <span>DB_DATABASE</span>
                <span><?= htmlspecialchars($_ENV['DB_DATABASE'] ?? '—') ?></span>
            </li>
            <li>
                <span>Session save path</span>
                <span><?= htmlspecialchars(session_save_path() ?: 'default') ?></span>
            </li>
            <li>
                <span>API health check</span>
                <span><a href="/api/health" target="_blank">/api/health</a></span>
            </li>
        </ul>
    </div>

</main>

<?php require APP_ROOT . '/views/layout/footer.php'; ?>
