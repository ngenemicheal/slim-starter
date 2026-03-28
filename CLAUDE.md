# slim-starter — Developer Guide

A minimal but complete PHP starter framework. Built on Slim 4 with Eloquent
ORM, PHPMailer, session authentication, and input validation. Designed for
shared hosting (cPanel) but works equally well in Docker locally.

---

## Project structure

```
slim-starter/
├── app/
│   ├── Controllers/        # Request handlers
│   │   ├── Controller.php  # Base class (render, json, redirect helpers)
│   │   ├── HomeController.php
│   │   └── AuthController.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php   # Redirects unauthenticated requests to /login
│   └── Models/
│       └── User.php             # Eloquent model backed by `users` table
├── bootstrap/
│   └── app.php             # Wires everything together; returns $app
├── config/
│   ├── app.php             # PHP-DI container definitions (services, settings)
│   └── database.php        # Eloquent connection config
├── database/
│   └── migrations/         # Plain SQL migration files — run manually
├── docker/
│   ├── Dockerfile          # PHP 8.3 + Apache image
│   ├── apache.conf         # VirtualHost pointing at public/
│   └── init.sql            # Auto-run on first `docker compose up`
├── public/                 # ← cPanel document root points here
│   ├── .htaccess           # URL rewriting for Slim
│   ├── index.php           # Front controller
│   └── css/app.css         # Application stylesheet
├── routes/
│   ├── web.php             # HTML routes (loaded in web mode)
│   └── api.php             # JSON routes (always loaded under /api)
├── storage/
│   └── sessions/           # File-based PHP sessions (gitignored)
├── views/
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── home.php
│   └── dashboard.php
├── .env.example            # Copy to .env and fill in values
├── composer.json
└── docker-compose.yml
```

---

## Quick start (Docker)

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
# App: http://wsl-local:8092
# Mailhog: http://wsl-local:8025
```

The database is initialised automatically from `docker/init.sql` on first run.

---

## Quick start (shared hosting / cPanel)

1. Upload all files (excluding `vendor/`) to the server.
2. Point the cPanel document root to the `public/` directory.
3. SSH in (or use the cPanel Terminal) and run:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   If you have no terminal, run Composer via a cPanel-provided tool or
   pre-build the `vendor/` directory locally and upload it.
4. Copy `.env.example` to `.env` and fill in your DB credentials.
5. Import `database/migrations/001_create_users_table.sql` via phpMyAdmin.
6. Make sure `storage/sessions/` is writable by the web server (`chmod 755`).

---

## How to add a route

### Web route (HTML response)

Edit `routes/web.php`:

```php
// Public
$app->get('/about', [AboutController::class, 'show']);

// Protected (requires login)
$app->group('', function ($group) {
    $group->get('/profile', [ProfileController::class, 'show']);
    $group->post('/profile', [ProfileController::class, 'update']);
})->add(AuthMiddleware::class);
```

### API route (JSON response)

Edit `routes/api.php` inside the existing `/api` group:

```php
$group->get('/posts',       [PostController::class, 'index']);
$group->post('/posts',      [PostController::class, 'store']);
$group->get('/posts/{id}',  [PostController::class, 'show']);
```

---

## How to add a controller

Create `app/Controllers/PostController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $posts = Post::latest()->get();
        return $this->render($response, 'posts/index', compact('posts'));
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $post = Post::create(['title' => $body['title'], 'body' => $body['body']]);
        return $this->json($response, $post, 201);
    }
}
```

PHP-DI autowires controllers automatically — no registration required.

---

## How to add a model

1. Create `app/Models/Post.php`:

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

2. Add `database/migrations/002_create_posts_table.sql`:

```sql
CREATE TABLE IF NOT EXISTS `posts` (
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id`    bigint unsigned NOT NULL,
    `title`      varchar(255)    NOT NULL,
    `body`       text            NOT NULL,
    `created_at` timestamp       NULL DEFAULT NULL,
    `updated_at` timestamp       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `posts_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

3. Run the SQL via phpMyAdmin or `docker compose exec db mysql`.

---

## How to inject a service

Register it in `config/app.php`:

```php
use App\Services\PostService;

return [
    // ...existing entries...

    PostService::class => \DI\autowire(),

    // Or with manual wiring:
    PostService::class => function (ContainerInterface $c): PostService {
        return new PostService($c->get(PHPMailer::class));
    },
];
```

Then type-hint it in a controller constructor:

```php
class PostController extends Controller
{
    public function __construct(private PostService $posts) {}
}
```

---

## How to switch APP_MODE

Edit `.env`:

```env
# Full web app (HTML views + /api/* routes)
APP_MODE=web

# Pure JSON API (only /api/* routes, JSON error responses)
APP_MODE=api
```

In `api` mode the error middleware returns JSON instead of HTML, which is
useful when building headless backends.

---

## How to send email

Inject `PHPMailer` and use it:

```php
use PHPMailer\PHPMailer\PHPMailer;

class NotificationController extends Controller
{
    public function __construct(private PHPMailer $mail) {}

    public function send(...): Response
    {
        $this->mail->addAddress('user@example.com');
        $this->mail->Subject = 'Hello!';
        $this->mail->Body    = '<p>Welcome!</p>';
        $this->mail->isHTML(true);
        $this->mail->send();
        // ...
    }
}
```

Locally, all mail is caught by Mailhog at `http://wsl-local:8025`.

---

## Validation cheatsheet

```php
use Respect\Validation\Validator as v;

v::stringType()->length(2, 100)->validate($name);   // string, 2–100 chars
v::email()->validate($email);                        // valid email
v::stringType()->length(8, null)->validate($pass);  // min 8 chars
v::intType()->between(1, 100)->validate($qty);       // integer 1–100
v::url()->validate($url);                            // valid URL
v::date('Y-m-d')->validate($date);                   // date format
v::notEmpty()->validate($value);                     // not empty
```

---

## Security notes

- **CSRF protection** — Not included. Add `slim/csrf` for production web apps.
- **Rate limiting** — Not included. Consider middleware or a CDN rule.
- **SQL injection** — Eloquent parameterises all queries; you are safe as long
  as you use the ORM or the query builder (never raw string interpolation).
- **XSS** — All view output is wrapped in `htmlspecialchars()`. Keep it that way.
- **Session fixation** — Mitigated: `session_regenerate_id(true)` is called on login.
- **Password hashing** — bcrypt with cost 12 via `password_hash()`.

---

## Adding a `.env` variable

1. Add the key/default to `.env.example` (commit this).
2. Add the key to `.env` on the server (do not commit the real `.env`).
3. Read it anywhere with `$_ENV['MY_KEY'] ?? 'default'`.

---

## Deployment checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] `APP_SECRET` is a random 32+ character string
- [ ] `storage/sessions/` is writable and outside `public/`
- [ ] Database credentials are correct
- [ ] `composer install --no-dev --optimize-autoloader` has been run
- [ ] `database/migrations/` SQL files have been imported
- [ ] HTTPS is enabled and `cookie_secure` will be set automatically
