# slim-starter — Developer Guide

A minimal but complete PHP starter framework. Built on Slim 4 with Eloquent
ORM, PHPMailer, Twig templates, session authentication, and input validation.
Designed for shared hosting (cPanel) but works equally well in Docker locally.

---

## Project structure

```
slim-starter/
├── app/
│   ├── Controllers/
│   │   ├── Controller.php          # Base: render(Twig), json(), redirect()
│   │   ├── HomeController.php      # Public landing page + user dashboard
│   │   ├── AuthController.php      # Register / login / logout
│   │   └── Admin/
│   │       ├── AuthController.php  # Admin-specific login (/admin/login)
│   │       ├── DashboardController.php
│   │       └── UserController.php  # User CRUD (list, show, edit, delete)
│   ├── Extensions/
│   │   └── TwigExtension.php       # session(), flash(), current_path() + filters
│   ├── Middleware/
│   │   ├── AuthMiddleware.php      # Redirects to /login if not authenticated
│   │   └── AdminMiddleware.php     # Redirects to /admin/login if not admin
│   └── Models/
│       └── User.php                # Eloquent model: role + status constants
├── bootstrap/
│   └── app.php                     # Wires everything together; returns $app
├── config/
│   ├── app.php                     # PHP-DI: Twig, PHPMailer, settings
│   └── database.php                # Eloquent connection config
├── database/
│   ├── migrations/                 # SQL files — run in order manually
│   │   ├── 001_create_users_table.sql
│   │   └── 002_add_role_status_to_users.sql
│   └── seeds/
│       └── seed_admin.php          # Creates admin@example.com / admin123
├── docker/
│   ├── Dockerfile                  # PHP 8.3 + Apache
│   ├── apache.conf                 # VirtualHost pointing at public/
│   └── init.sql                    # Full schema, auto-run on first compose up
├── public/                         # ← cPanel document root
│   ├── .htaccess
│   ├── index.php
│   └── css/
│       ├── app.css                 # Public stylesheet
│       └── admin.css               # Admin panel stylesheet
├── routes/
│   ├── web.php                     # HTML + admin routes
│   └── api.php                     # JSON routes under /api
├── storage/
│   ├── sessions/                   # File-based PHP sessions
│   └── cache/
│       ├── twig/                   # Compiled Twig templates (production)
│       └── di/                     # Compiled PHP-DI container (production)
├── views/
│   ├── base.twig                   # HTML skeleton
│   ├── layout.twig                 # Public nav + footer
│   ├── home.twig
│   ├── dashboard.twig
│   ├── auth/
│   │   ├── login.twig
│   │   └── register.twig
│   └── admin/
│       ├── layout.twig             # Sidebar shell for all admin pages
│       ├── login.twig              # Standalone admin login (dark bg)
│       ├── dashboard.twig
│       └── users/
│           ├── index.twig          # Paginated list + search
│           ├── show.twig           # User detail
│           └── edit.twig           # Edit form (name, email, role, status)
├── .env.example
├── composer.json
└── docker-compose.yml
```

---

## Quick start (Docker)

```bash
cp .env.example .env
docker compose up -d
docker compose exec slim_app composer install
# Run the admin seeder
docker compose exec slim_app php database/seeds/seed_admin.php
# App     → http://wsl-local:8092
# Admin   → http://wsl-local:8092/admin
# Mailhog → http://wsl-local:8025
```

---

## Quick start (shared hosting / cPanel)

1. Upload all files (excluding `vendor/`) to the server.
2. Point the cPanel document root to the `public/` directory.
3. Run `composer install --no-dev --optimize-autoloader` (via SSH or cPanel Terminal).
4. Copy `.env.example` to `.env` and fill in your DB credentials.
5. Import `database/migrations/001_create_users_table.sql` via phpMyAdmin.
6. Import `database/migrations/002_add_role_status_to_users.sql` via phpMyAdmin.
7. Run the admin seeder: `php database/seeds/seed_admin.php`
8. Make `storage/sessions/` writable: `chmod 755 storage/sessions`.

---

## Admin panel

| URL | Description |
|---|---|
| `/admin/login` | Admin login (checks role = admin) |
| `/admin` | Dashboard with user stats |
| `/admin/users` | Paginated user list with search |
| `/admin/users/{id}` | User detail view |
| `/admin/users/{id}/edit` | Edit name, email, role, status |
| `/admin/users/{id}/delete` | POST — delete user |

**Default admin credentials** (created by the seeder):

```
Email:    admin@example.com
Password: admin123
```

Change the password after first login.

To promote an existing user to admin directly in MySQL:

```sql
UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
```

---

## Templates (Twig)

All views are Twig templates in `views/`. The template engine is set by
`APP_TEMPLATE_ENGINE` in `.env` — defaults to `twig`.

### Twig template hierarchy

```
base.twig          ← HTML skeleton (head, body, title block)
└── layout.twig    ← Public pages: nav + footer
│   └── home.twig, dashboard.twig, auth/login.twig, ...
└── admin/layout.twig  ← Admin pages: sidebar + topbar
    └── admin/dashboard.twig, admin/users/index.twig, ...
```

### Available Twig functions (from TwigExtension)

```twig
{{ session('user') }}        {# → $_SESSION['user'] #}
{{ session('user').role }}   {# → 'admin' or 'user' #}
{{ flash('success') }}       {# reads and clears $_SESSION['flash_success'] #}
{{ current_path() }}         {# → '/admin/users' #}
```

### Available Twig filters

```twig
{{ user.created_at | date_fmt }}          {# → 'Jan 01, 2025' #}
{{ user.created_at | date_fmt('Y-m-d') }} {# custom format #}
{{ 'hello' | ucwords }}                   {# → 'Hello' #}
```

### Set a flash message from a controller

```php
$_SESSION['flash_success'] = 'Your changes were saved.';
$_SESSION['flash_error']   = 'Something went wrong.';
return $this->redirect($response, '/somewhere');
```

### Switch back to plain PHP templates

```env
APP_TEMPLATE_ENGINE=php
```

The base `Controller::render()` will look for `views/<name>.php` instead.
The original `.php` view files are preserved alongside the `.twig` files.

---

## How to add a route

**Web route** — `routes/web.php`:

```php
$app->get('/about', [AboutController::class, 'show']);

// Protected
$app->group('', function ($group) {
    $group->get('/settings', [SettingsController::class, 'show']);
})->add(AuthMiddleware::class);

// Admin
$app->group('/admin', function ($group) {
    $group->get('/reports', [ReportController::class, 'index']);
})->add(AdminMiddleware::class);
```

**API route** — `routes/api.php` inside the existing `/api` group:

```php
$group->get('/posts', [PostController::class, 'index']);
```

---

## How to add a controller

```php
// app/Controllers/PostController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'posts/index', ['posts' => Post::all()]);
    }
}
```

PHP-DI autowires controllers — no registration needed.

---

## How to add a model

1. Create `app/Models/Post.php` extending `Illuminate\Database\Eloquent\Model`
2. Add `database/migrations/003_create_posts_table.sql`
3. Run the SQL via phpMyAdmin or `docker compose exec slim_db mysql -uroot -psecret slim_starter < database/migrations/003_create_posts_table.sql`

---

## How to inject a service

Add to `config/app.php`, then type-hint in the controller constructor:

```php
// config/app.php
App\Services\PostService::class => \DI\autowire(),

// PostController.php
public function __construct(
    \Slim\Views\Twig $twig,
    private \App\Services\PostService $posts
) {
    parent::__construct($twig);
}
```

---

## How to switch APP_MODE

```env
APP_MODE=web   # HTML views + /api/* routes
APP_MODE=api   # JSON only, JSON error responses
```

---

## Validation cheatsheet

```php
v::stringType()->length(2, 100)->validate($name);
v::email()->validate($email);
v::stringType()->length(8, null)->validate($password);
v::inArray(['user', 'admin'], true)->validate($role);
v::url()->validate($url);
```

---

## Security notes

- **CSRF** — not included. Add `slim/csrf` for production forms.
- **SQL injection** — Eloquent parameterises all queries. Never interpolate into raw SQL.
- **XSS** — Twig auto-escapes all `{{ }}` output. Use `{{ value|raw }}` only for trusted HTML.
- **Session fixation** — `session_regenerate_id(true)` called on every login.
- **Password hashing** — bcrypt cost 12 via `password_hash()`.
- **Admin access** — `AdminMiddleware` checks `role = 'admin'` on every request to `/admin/*`.

---

## Deployment checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_SECRET` is a random 32+ character string
- [ ] `APP_TEMPLATE_ENGINE=twig`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] Both SQL migrations imported
- [ ] Admin seeder run, default password changed
- [ ] `storage/sessions/` and `storage/cache/` are writable
- [ ] HTTPS enabled on the domain
