# slim-starter

A minimal but production-ready PHP starter framework built on **Slim 4**. Comes with session-based authentication, Eloquent ORM, input validation, PHPMailer, and a Docker environment wired up and ready to go. Designed for projects that will be deployed on shared hosting (cPanel) — no Docker on the server, no shell tricks, just Composer and a file upload.

Clone it, rename it, and build something.

---

## What comes out of the box

- **Routing** — Slim 4 with PHP-DI container, route groups, and middleware support
- **Database** — Eloquent ORM (the same one from Laravel) with a `User` model ready to use
- **Authentication** — Register, login, and logout with file-based PHP sessions
- **Input validation** — Respect/Validation for clean, chainable validation rules
- **Email** — PHPMailer wired to `.env`; locally caught by Mailhog
- **Two route modes** — `APP_MODE=web` for HTML apps, `APP_MODE=api` for pure JSON APIs
- **Shared-hosting ready** — correct `.htaccess`, no shell calls, file-based sessions
- **Docker environment** — PHP 8.3 + Apache, MySQL 8, and Mailhog in one `compose up`
- **Clean views** — PHP templates with a self-contained stylesheet; no build tools

---

## Tech stack

| Layer | Package | Version |
|---|---|---|
| Router | `slim/slim` | ^4.14 |
| PSR-7 | `slim/psr7` | ^1.7 |
| Container | `php-di/php-di` | ^7.0 |
| ORM | `illuminate/database` | ^11.0 |
| Mailer | `phpmailer/phpmailer` | ^6.9 |
| Env vars | `vlucas/phpdotenv` | ^5.6 |
| Validation | `respect/validation` | ^2.3 |

---

## Prerequisites

| Tool | Minimum version | Notes |
|---|---|---|
| PHP | 8.1 | 8.2 / 8.3 recommended |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Docker + Compose | any recent | Local dev only |

---

## Local development

```bash
# 1. Clone the template
git clone https://github.com/ngenemicheal/slim-starter.git my-project
cd my-project

# 2. Copy and configure environment
cp .env.example .env
#    Edit .env if needed — defaults work with the Docker setup as-is

# 3. Start the containers
docker compose up -d

# 4. Install PHP dependencies inside the container
docker compose exec app composer install

# 5. Open in your browser
#    App     → http://wsl-local:8092
#    Mailhog → http://wsl-local:8025
```

> The database and `users` table are created automatically on first run via `docker/init.sql`.

---

## Project structure

```
slim-starter/
├── app/
│   ├── Controllers/
│   │   ├── Controller.php        # Base: render(), json(), redirect()
│   │   ├── AuthController.php    # Register / login / logout
│   │   └── HomeController.php    # Landing page + dashboard
│   ├── Middleware/
│   │   └── AuthMiddleware.php    # Redirects unauthenticated requests to /login
│   └── Models/
│       └── User.php              # Eloquent model — users table
├── bootstrap/
│   └── app.php                   # Wires dotenv → session → Eloquent → DI → Slim
├── config/
│   ├── app.php                   # PHP-DI container definitions
│   └── database.php              # Eloquent connection config
├── database/
│   └── migrations/               # Plain SQL files — import via phpMyAdmin
├── docker/
│   ├── Dockerfile                # PHP 8.3 + Apache
│   ├── apache.conf               # VirtualHost pointing at public/
│   └── init.sql                  # Runs once on first `docker compose up`
├── public/                       # ← cPanel document root
│   ├── .htaccess                 # URL rewriting for Slim
│   ├── index.php                 # Front controller
│   └── css/app.css               # Application stylesheet
├── routes/
│   ├── web.php                   # HTML routes (web mode)
│   └── api.php                   # JSON routes under /api (always loaded)
├── storage/
│   └── sessions/                 # File-based sessions (gitignored)
├── views/
│   ├── layout/                   # header.php + footer.php partials
│   ├── auth/                     # login.php, register.php
│   ├── home.php
│   └── dashboard.php
├── .env.example
├── composer.json
└── docker-compose.yml
```

---

## Web mode vs API mode

Set `APP_MODE` in `.env`:

```env
# Full web app — loads HTML routes (routes/web.php)
# and JSON API routes under /api (routes/api.php)
APP_MODE=web

# Pure JSON API — loads only routes/api.php
# Error responses are JSON, not HTML
APP_MODE=api
```

In `api` mode the error middleware automatically returns JSON instead of HTML error pages, making it suitable for headless backends and mobile app APIs.

---

## Adding a route

**HTML route** — edit `routes/web.php`:

```php
// Public page
$app->get('/about', [AboutController::class, 'show']);

// Protected page (requires login)
$app->group('', function ($group) {
    $group->get('/settings', [SettingsController::class, 'show']);
    $group->post('/settings', [SettingsController::class, 'update']);
})->add(AuthMiddleware::class);
```

**API endpoint** — edit `routes/api.php` inside the existing `/api` group:

```php
$group->get('/posts',          [PostController::class, 'index']);
$group->post('/posts',         [PostController::class, 'store']);
$group->get('/posts/{id}',     [PostController::class, 'show']);
$group->put('/posts/{id}',     [PostController::class, 'update']);
$group->delete('/posts/{id}',  [PostController::class, 'destroy']);
```

---

## Adding a model

**1. Create the model** in `app/Models/Post.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

**2. Add the migration** in `database/migrations/002_create_posts_table.sql`:

```sql
CREATE TABLE IF NOT EXISTS `posts` (
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id`    bigint unsigned NOT NULL,
    `title`      varchar(255)    NOT NULL,
    `body`       text            NOT NULL,
    `created_at` timestamp       NULL DEFAULT NULL,
    `updated_at` timestamp       NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**3. Run the migration:**
- Docker: `docker compose exec db mysql -uroot -psecret slim_starter < database/migrations/002_create_posts_table.sql`
- Shared hosting: import via phpMyAdmin

---

## Deploying to shared hosting (cPanel)

1. **Create the database** — use cPanel's MySQL Databases wizard. Note the host, database name, username, and password.

2. **Upload files** — upload everything *except* `vendor/` to your hosting account. Keep the directory structure intact.

3. **Set the document root** — in cPanel, point your domain's document root to the `public/` subdirectory.

4. **Install dependencies** — use the cPanel Terminal or a Composer-enabled hosting tool:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   If no terminal is available, run `composer install` locally and upload the generated `vendor/` directory.

5. **Configure the environment** — copy `.env.example` to `.env` and fill in your values. You can edit it directly in the cPanel File Manager.

6. **Import the database schema** — in phpMyAdmin, select your database and import `database/migrations/001_create_users_table.sql`.

7. **Set permissions** — make sure `storage/sessions/` is writable:
   ```bash
   chmod 755 storage/sessions
   ```

8. **Verify** — visit your domain. You should see the home page.

> **Tip:** Set `APP_DEBUG=false` and `APP_ENV=production` before going live.

---

## Environment variables reference

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `Slim Starter` | Displayed in the UI and emails |
| `APP_ENV` | `development` | `development` or `production` |
| `APP_DEBUG` | `true` | Show detailed error pages (`false` in production) |
| `APP_URL` | `http://localhost:8092` | Full base URL of the app |
| `APP_SECRET` | *(change this)* | Random secret for signing tokens / CSRF |
| `APP_MODE` | `web` | `web` or `api` — controls which routes are loaded |
| `DB_DRIVER` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `slim_starter` | Database name |
| `DB_USERNAME` | `root` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `DB_CHARSET` | `utf8mb4` | Character set |
| `DB_COLLATION` | `utf8mb4_unicode_ci` | Collation |
| `MAIL_HOST` | `localhost` | SMTP host (`localhost` → Mailhog in Docker) |
| `MAIL_PORT` | `1025` | SMTP port |
| `MAIL_USERNAME` | *(empty)* | SMTP username |
| `MAIL_PASSWORD` | *(empty)* | SMTP password |
| `MAIL_ENCRYPTION` | *(empty)* | `tls` or `ssl` (leave empty for Mailhog) |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default from address |
| `MAIL_FROM_NAME` | `Slim Starter` | Default from name |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `SESSION_PATH` | `../storage/sessions` | Where PHP writes session files |

---

## Contributing

Pull requests are welcome. For significant changes, open an issue first to discuss what you'd like to change. Please keep PRs focused — one feature or fix per PR.

---

## License

[MIT](https://opensource.org/licenses/MIT)
