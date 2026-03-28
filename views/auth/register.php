<?php require APP_ROOT . '/views/layout/header.php'; ?>

<div class="page-narrow">
    <div class="card">

        <div class="card-title">Create account</div>
        <div class="card-subtitle">It only takes a few seconds.</div>

        <form method="POST" action="/register" novalidate>

            <div class="form-group">
                <label class="form-label" for="name">Full name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="form-input <?= !empty($errors['name']) ? 'error' : '' ?>"
                    value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                    autocomplete="name"
                    required
                >
                <?php if (!empty($errors['name'])): ?>
                    <div class="form-error"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </div>

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
                    autocomplete="new-password"
                    required
                    minlength="8"
                >
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Create account</button>

        </form>

        <div class="auth-footer">
            Already have an account? <a href="/login">Sign in</a>
        </div>

    </div>
</div>

<?php require APP_ROOT . '/views/layout/footer.php'; ?>
