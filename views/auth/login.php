<?php require APP_ROOT . '/views/layout/header.php'; ?>

<div class="page-narrow">
    <div class="card">

        <div class="card-title">Sign in</div>
        <div class="card-subtitle">Enter your email and password to continue.</div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login" novalidate>

            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-input <?= !empty($errors['email']) ? 'error' : '' ?>"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    autocomplete="email"
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="form-input <?= !empty($errors['password']) ? 'error' : '' ?>"
                    autocomplete="current-password"
                    required
                >
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Sign in</button>

        </form>

        <div class="auth-footer">
            Don't have an account? <a href="/register">Create one</a>
        </div>

    </div>
</div>

<?php require APP_ROOT . '/views/layout/footer.php'; ?>
