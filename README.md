# slim-starter

A minimal but production-ready PHP starter framework built on **Slim 4**. Comes with Twig templates, an admin panel with full user CRUD, session-based authentication, Eloquent ORM, input validation, and PHPMailer. Designed for projects that will be deployed on shared hosting (cPanel) — no Docker on the server, no shell tricks, just Composer and a file upload.

Clone it, rename it, and build something.

---

## What comes out of the box

- **Routing** — Slim 4 with PHP-DI container, route groups, and middleware support
- **Twig templates** — full template inheritance, auto-escaping, custom helpers; plain PHP views still supported
- **Database** — Eloquent ORM (the same one from Laravel) with a `User` model ready to use
- **Authentication** — register, login, and logout with file-based PHP sessions
- **Admin panel** — user CRUD at `/admin` with stats, search, pagination, role and status management
- **Role system** — `role` column (`user` / `admin`) and `status` column (`active` / `inactive`) on users
- **Input validation** — Respect/Validation for clean, chainable validation rules
- **Email** — PHPMailer wired to `.env`; locally caught by Mailhog
- **Two route modes** — `APP_MODE=web` for HTML apps, `APP_MODE=api` for pure JSON APIs
- **Shared-hosting ready** — correct `.htaccess`, no shell calls, file-based sessions
- **Docker environment** — PHP 8.3 + Apache, MySQL 8, and Mailhog in one `compose up`

---

## Tech stack

| Layer | Package | Version |
|---|---|---|
| Router | `slim/slim` | ^4.14 |
| PSR-7 | `slim/psr7` | ^1.7 |
| Container | `php-di/php-di` | ^7.0 |
| Templates | `slim/twig-view` + `twig/twig` | ^3.4 / ^3.8 |
| ORM | `illuminate/database` | ^11.0 |
| Mailer | `phpmailer/phpmailer` | ^6.9 |
| Env vars | `vlucas/phpdotenv` | ^5.6 |
| Validation | `respect/validation` | ^2.3 |

---

## Prerequisites

| Tool | Minimum version |
|---|---|
| PHP | 8.1 |
| Composer | 2.x |
| Docker + Compose | any recent (local dev only) |

---

## Local development

```bash
# 1. Clone the template
git clone https://github.com/ngenemicheal/slim-starter.git my-project
cd my-project

# 2. Copy environment file (defaults work with Docker as-is)
cp .env.example .env

# 3. Start containers
docker compose up -d

# 4. Install dependencies
docker compose exec slim_app composer install

# 5. Run migrations (creates the database and all tables)
docker compose exec app php database/migrate.php

# 6. Seed the admin user
docker compose exec app php database/seeds/seed_admin.php
```

| Service | URL |
|---|---|
| App | http://wsl-local:8092 |
| Admin panel | http://wsl-local:8092/admin |
| Mailhog | http://wsl-local:8025 |

---

## Admin panel

Sign in at **`/admin/login`** with the default credentials seeded above:

```
Email:    admin@example.com
Password: admin123
```

**Change the password after first login.**

### Admin routes

| Route | Description |
|---|---|
| `GET /admin` | Dashboard — stats, recent registrations |
| `GET /admin/users` | Paginated user list with search |
| `GET /admin/users/{id}` | User detail |
| `GET /admin/users/{id}/edit` | Edit form |
| `POST /admin/users/{id}/edit` | Save changes (name, email, role, status) |
| `POST /admin/users/{id}/delete` | Delete user |

### Promote an existing user to admin

```sql
UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
```

Or re-run the seeder after editing `database/seeds/seed_admin.php`.

---

## Project structure

```
slim-starter/
├── app/
│   ├── Controllers/
│   │   ├── Controller.php          # Base: render(), json(), redirect()
│   │   ├── AuthController.php      # Register / login / logout
│   │   ├── HomeController.php
│   │   └── Admin/
│   │       ├── AuthController.php  # /admin/login
│   │       ├── DashboardController.php
│   │       └── UserController.php  # CRUD
│   ├── Extensions/
│   │   └── TwigExtension.php       # session(), flash(), current_path(), filters
│   ├── Middleware/
│   │   ├── AuthMiddleware.php      # Requires login
│   │   └── AdminMiddleware.php     # Requires role = admin
│   └── Models/
│       └── User.php
├── bootstrap/app.php               # Wires dotenv → session → Eloquent → DI → Slim
├── config/
│   ├── app.php                     # DI definitions: Twig, PHPMailer, settings
│   └── database.php
├── database/
│   ├── migrations/                 # 001 + 002 SQL files
│   └── seeds/seed_admin.php
├── docker/
├── public/                         # ← cPanel document root
│   └── css/app.css + admin.css
├── routes/web.php + api.php
├── storage/sessions/ + cache/
└── views/
    ├── base.twig + layout.twig     # Template hierarchy
    ├── home.twig, dashboard.twig
    ├── auth/login.twig + register.twig
    └── admin/                      # Sidebar layout + all admin views
```

---

## Web mode vs API mode

```env
APP_MODE=web   # HTML views + /api/* routes
APP_MODE=api   # JSON only, JSON error responses
```

---

## Template engine

```env
APP_TEMPLATE_ENGINE=twig   # default — uses views/*.twig
APP_TEMPLATE_ENGINE=php    # fallback — uses views/*.php
```

Twig auto-escapes all output, so `{{ variable }}` is always XSS-safe.
Use `{{ variable|raw }}` only for trusted HTML.

### Twig helpers (TwigExtension)

```twig
{{ session('user').name }}     {# current session user #}
{{ flash('success') }}         {# one-time flash message, cleared after read #}
{{ current_path() }}           {# → '/admin/users' #}
{{ user.created_at|date_fmt }} {# → 'Jan 01, 2025' #}
```

---

## Adding a route

**HTML route** — `routes/web.php`:

```php
$app->get('/about', [AboutController::class, 'show']);

// Protected by login
$app->group('', function ($group) {
    $group->get('/settings', [SettingsController::class, 'show']);
})->add(AuthMiddleware::class);

// Admin only
$app->group('/admin', function ($group) {
    $group->get('/reports', [ReportController::class, 'index']);
})->add(AdminMiddleware::class);
```

**API endpoint** — `routes/api.php` inside the existing `/api` group:

```php
$group->get('/posts',       [PostController::class, 'index']);
$group->post('/posts',      [PostController::class, 'store']);
$group->get('/posts/{id}',  [PostController::class, 'show']);
```

---

## Adding a model

**1.** Create `app/Models/Post.php`:

```php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'body', 'user_id'];

    public function user() { return $this->belongsTo(User::class); }
}
```

**2.** Add `database/migrations/003_create_posts_table.sql` and run:

```bash
docker compose exec app php database/migrate.php
```

---

## Deploying to shared hosting (cPanel)

1. **Create the database** in cPanel → MySQL Databases.
2. **Upload files** (excluding `vendor/`) keeping the directory structure intact.
3. **Set document root** to the `public/` subdirectory.
4. **Install dependencies** via SSH/Terminal: `composer install --no-dev --optimize-autoloader`
   *(No terminal? Run locally and upload the generated `vendor/` directory.)*
5. **Configure environment** — copy `.env.example` to `.env` and fill in your values.
6. **Run migrations**: `php database/migrate.php` (creates the DB and all tables from `.env` credentials).
7. **Seed admin**: `php database/seeds/seed_admin.php`
8. **Set permissions**: `chmod 755 storage/sessions storage/cache`

> Set `APP_DEBUG=false` and `APP_ENV=production` before going live.

---

## Environment variables reference

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `Slim Starter` | Shown in the UI and emails |
| `APP_ENV` | `development` | `development` or `production` |
| `APP_DEBUG` | `true` | Detailed error pages (`false` in production) |
| `APP_URL` | `http://localhost:8092` | Full base URL |
| `APP_SECRET` | *(change this)* | Random 32+ char secret |
| `APP_MODE` | `web` | `web` or `api` |
| `APP_TEMPLATE_ENGINE` | `twig` | `twig` or `php` |
| `DB_DRIVER` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `slim_starter` | Database name |
| `DB_USERNAME` | `root` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `DB_CHARSET` | `utf8mb4` | Character set |
| `DB_COLLATION` | `utf8mb4_unicode_ci` | Collation |
| `MAIL_HOST` | `localhost` | SMTP host |
| `MAIL_PORT` | `1025` | SMTP port (1025 = Mailhog) |
| `MAIL_USERNAME` | *(empty)* | SMTP username |
| `MAIL_PASSWORD` | *(empty)* | SMTP password |
| `MAIL_ENCRYPTION` | *(empty)* | `tls` or `ssl` |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default from address |
| `MAIL_FROM_NAME` | `Slim Starter` | Default from name |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `SESSION_PATH` | `../storage/sessions` | Session file directory |

---

## Contributing

Pull requests are welcome. For significant changes, open an issue first to discuss what you'd like to change. Keep PRs focused — one feature or fix per PR.

---

## License

[MIT](https://opensource.org/licenses/MIT)
