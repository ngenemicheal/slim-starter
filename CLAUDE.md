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
│   │   ├── Controller.php              # Base: render(Twig), json(), redirect()
│   │   ├── HomeController.php          # Public landing page + user dashboard
│   │   ├── AuthController.php          # Register / login / logout
│   │   ├── VerificationController.php  # Email verification + resend
│   │   ├── PasswordResetController.php # Forgot / reset password
│   │   └── Admin/
│   │       ├── AuthController.php      # Admin-specific login (/admin/login)
│   │       ├── DashboardController.php
│   │       └── UserController.php      # User CRUD (list, show, edit, delete)
│   ├── Extensions/
│   │   └── TwigExtension.php           # session(), flash(), current_path() + filters
│   ├── Mail/
│   │   └── Mailer.php                  # PHPMailer + Twig wrapper: send(to, name, subject, template, data)
│   ├── Middleware/
│   │   ├── AuthMiddleware.php          # Redirects to /login if not authenticated
│   │   ├── VerifiedMiddleware.php      # Redirects to /verify-notice if email not verified
│   │   └── AdminMiddleware.php         # Redirects to /admin/login if not admin
│   └── Models/
│       ├── User.php                    # Eloquent model: role + status constants
│       ├── EmailVerification.php       # Verification tokens (user_id, token, created_at)
│       └── PasswordReset.php           # Reset tokens (email, token, created_at)
├── bootstrap/
│   └── app.php                         # Wires everything together; returns $app
├── config/
│   ├── app.php                         # PHP-DI: Twig, PHPMailer, Mailer, settings
│   └── database.php                    # Eloquent connection config
├── database/
│   ├── migrations/                     # SQL files — run in order by migrate.php
│   │   ├── 001_create_users_table.sql
│   │   ├── 003_create_email_verifications_table.sql
│   │   └── 004_create_password_resets_table.sql
│   └── seeds/
│       └── seed_admin.php              # Creates admin@example.com / admin123 (pre-verified)
├── docker/
│   ├── Dockerfile                      # PHP 8.3 + Apache
│   ├── apache.conf                     # VirtualHost pointing at public/
│   └── init.sql                        # Full schema (all 3 tables), auto-run on first compose up
├── public/                             # ← cPanel document root
│   ├── .htaccess
│   ├── index.php
│   └── css/
│       ├── app.css                     # Public stylesheet
│       └── admin.css                   # Admin panel stylesheet
├── routes/
│   ├── web.php                         # HTML + admin routes
│   └── api.php                         # JSON routes under /api
├── storage/
│   ├── sessions/                       # File-based PHP sessions
│   └── cache/
│       ├── twig/                       # Compiled Twig templates (production)
│       └── di/                         # Compiled PHP-DI container (production)
└── views/
    ├── base.twig                        # HTML skeleton
    ├── layout.twig                      # Public nav + footer
    ├── home.twig
    ├── dashboard.twig
    ├── emails/
    │   ├── base.twig                    # HTML email layout (table-based, inline CSS)
    │   ├── welcome-verify.twig          # Sent on registration with verification link
    │   ├── login-notification.twig      # Sent on each successful login
    │   └── reset-password.twig          # Sent when password reset is requested
    ├── auth/
    │   ├── login.twig
    │   ├── register.twig
    │   ├── verify-notice.twig           # "Check your email" page after registration
    │   ├── forgot-password.twig
    │   └── reset-password.twig
    └── admin/
        ├── layout.twig                  # Sidebar shell for all admin pages
        ├── login.twig
        ├── dashboard.twig
        └── users/
            ├── index.twig
            ├── show.twig
            └── edit.twig
```

---

## Quick start (Docker)

```bash
cp .env.example .env
docker compose up -d
docker compose exec slim_app composer install
docker compose exec slim_app php database/migrate.php
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
4. Copy `.env.example` to `.env` and fill in your DB credentials and SMTP settings.
5. Run migrations: `php database/migrate.php`
6. Run the admin seeder: `php database/seeds/seed_admin.php`
7. Make `storage/sessions/` and `storage/cache/` writable: `chmod -R 755 storage/`

---

## Email system

### Mailer wrapper

`App\Mail\Mailer` wraps PHPMailer with a single method:

```php
$this->mailer->send(
    'recipient@example.com',
    'Recipient Name',
    'Email Subject',
    'emails/template-name',   // relative to views/, no extension
    ['key' => 'value']        // Twig template variables
);
```

Inject it into any controller:

```php
public function __construct(Twig $twig, private Mailer $mailer)
{
    parent::__construct($twig);
}
```

### Email templates

All templates extend `views/emails/base.twig` and override `{% block body %}`.
The base layout provides: header with app name, body area, footer with year.

Available base template variables: `app_name`, `app_url`.

### Email flows

| Trigger | Template | Notes |
|---|---|---|
| Registration | `emails/welcome-verify` | Contains verification link; expires 24 h |
| Login | `emails/login-notification` | Includes time + IP; wrapped in try/catch |
| Forgot password | `emails/reset-password` | Token expires in 60 minutes |

### Verification flow

1. User registers → `email_verified_at` is `null` → session `email_verified_at` is `null`
2. Redirected to `/verify-notice` (shown for both unverified and just-registered)
3. Clicks link in email → `GET /verify/{token}` → `email_verified_at` set → redirected to `/dashboard`
4. `/verify/resend` is rate-limited to one resend per 5 minutes per user
5. `/dashboard` is behind `VerifiedMiddleware` — unverified users are bounced to `/verify-notice`

### Password reset flow

1. User submits `/forgot-password` → token stored in `password_resets`, email sent
2. Token is valid for **60 minutes**; rate-limited to one request per 5 minutes
3. `/reset-password/{token}` form validates min 8 chars + confirmation match
4. After reset, token is deleted and user is redirected to `/login`

### Admin accounts and email verification

The seeder sets `email_verified_at = NOW()` on the admin user so it bypasses the
verification gate. Admin routes (`/admin/*`) do **not** go through `VerifiedMiddleware`.

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

---

## Auth routes

| Route | Controller method | Auth required |
|---|---|---|
| `GET /login` | `AuthController::loginForm` | — |
| `POST /login` | `AuthController::login` | — |
| `GET /register` | `AuthController::registerForm` | — |
| `POST /register` | `AuthController::register` | — |
| `GET /logout` | `AuthController::logout` | — |
| `GET /verify-notice` | `VerificationController::notice` | session only |
| `GET /verify/{token}` | `VerificationController::verify` | — |
| `POST /verify/resend` | `VerificationController::resend` | session only |
| `GET /forgot-password` | `PasswordResetController::forgotForm` | — |
| `POST /forgot-password` | `PasswordResetController::forgot` | — |
| `GET /reset-password/{token}` | `PasswordResetController::resetForm` | — |
| `POST /reset-password/{token}` | `PasswordResetController::reset` | — |

---

## Middleware

| Middleware | Protects | Redirects to |
|---|---|---|
| `AuthMiddleware` | Requires a valid session | `/login` |
| `VerifiedMiddleware` | Requires `email_verified_at` in session | `/verify-notice` |
| `AdminMiddleware` | Requires `role = admin` | `/admin/login` |

Middleware is applied LIFO in Slim 4. For the dashboard group:
```php
->add(VerifiedMiddleware::class)->add(AuthMiddleware::class)
// AuthMiddleware runs first (outermost), then VerifiedMiddleware
```

---

## Templates (Twig)

All views are Twig templates in `views/`. The template engine is set by
`APP_TEMPLATE_ENGINE` in `.env` — defaults to `twig`.

### Twig template hierarchy

```
base.twig              ← HTML skeleton
└── layout.twig        ← Public pages: nav + footer
│   └── home.twig, dashboard.twig, auth/*.twig
└── admin/layout.twig  ← Admin pages: sidebar + topbar
    └── admin/dashboard.twig, admin/users/*.twig
emails/base.twig       ← HTML email skeleton (standalone, table-based)
└── emails/welcome-verify.twig
└── emails/login-notification.twig
└── emails/reset-password.twig
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

---

## How to add a route

**Web route** — `routes/web.php`:

```php
$app->get('/about', [AboutController::class, 'show']);

// Protected (auth + verified)
$app->group('', function ($group) {
    $group->get('/settings', [SettingsController::class, 'show']);
})->add(VerifiedMiddleware::class)->add(AuthMiddleware::class);

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
2. Add `database/migrations/005_create_posts_table.sql`
3. Run: `php database/migrate.php` (or `docker compose exec slim_app php database/migrate.php`)

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
- **Email enumeration** — `forgot-password` always shows the same confirmation message.
- **Token security** — `bin2hex(random_bytes(32))` produces 64-char hex tokens.

---

## Deployment checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_SECRET` is a random 32+ character string
- [ ] `APP_URL` set to the live domain (used in email links)
- [ ] `APP_TEMPLATE_ENGINE=twig`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php database/migrate.php` run (creates all tables)
- [ ] Admin seeder run, default password changed
- [ ] SMTP credentials configured in `.env`
- [ ] `storage/sessions/` and `storage/cache/` are writable
- [ ] HTTPS enabled on the domain
