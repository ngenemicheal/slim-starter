<?php require APP_ROOT . '/views/layout/header.php'; ?>

<main class="page">

    <div class="hero">
        <span class="hero-badge">PHP Starter Template</span>
        <h1>Ship faster.<br>Break less things.</h1>
        <p>
            A clean, minimal foundation built on Slim 4, Eloquent ORM, and
            PHPMailer. Session auth, input validation, and shared-hosting
            compatibility included — just clone and build.
        </p>
        <div class="hero-actions">
            <?php if (!empty($user)): ?>
                <a href="/dashboard" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="/register" class="btn btn-primary">Get started</a>
                <a href="/login"    class="btn btn-ghost">Sign in</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="features">

        <div class="feature-card">
            <div class="feature-icon">&#9889;</div>
            <div class="feature-title">Slim 4 Router</div>
            <div class="feature-desc">
                Fast PSR-7 / PSR-15 compliant routing with middleware support,
                route groups, and a PHP-DI container wired in.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">&#128200;</div>
            <div class="feature-title">Eloquent ORM</div>
            <div class="feature-desc">
                The same battle-tested ORM from Laravel — relationships,
                scopes, mass assignment, and fluent query builder.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">&#128274;</div>
            <div class="feature-title">Session Auth</div>
            <div class="feature-desc">
                Register, login, and logout out of the box. File-based
                sessions — no Redis needed. Passwords hashed with bcrypt.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">&#9989;</div>
            <div class="feature-title">Input Validation</div>
            <div class="feature-desc">
                Respect/Validation provides a fluent, chainable API
                for validating and sanitising user input.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">&#128231;</div>
            <div class="feature-title">PHPMailer</div>
            <div class="feature-desc">
                SMTP email ready to go. Swap in any provider — Mailgun,
                SendGrid, SES — by editing <code>.env</code>.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">&#127758;</div>
            <div class="feature-title">Shared Hosting Ready</div>
            <div class="feature-desc">
                Correct <code>.htaccess</code>, PHP 8.1+, no shell commands,
                file-based sessions. Deploy by uploading files and running
                Composer.
            </div>
        </div>

    </div>

</main>

<?php require APP_ROOT . '/views/layout/footer.php'; ?>
