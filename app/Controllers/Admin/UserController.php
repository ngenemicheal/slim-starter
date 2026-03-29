<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

class UserController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * GET /admin/users
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $search = trim($params['search'] ?? '');
        $page   = max(1, (int) ($params['page'] ?? 1));

        $query = User::query()->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $total      = (clone $query)->count();
        $users      = $query->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE)->get();
        $totalPages = (int) ceil($total / self::PER_PAGE);

        return $this->render($response, 'admin/users/index', [
            'title'       => 'Users',
            'users'       => $users,
            'search'      => $search,
            'page'        => $page,
            'total_pages' => $totalPages,
            'total'       => $total,
            'per_page'    => self::PER_PAGE,
        ]);
    }

    /**
     * GET /admin/users/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $subject = User::findOrFail((int) $args['id']);

        return $this->render($response, 'admin/users/show', [
            'title'   => 'User — ' . $subject->name,
            'subject' => $subject,
        ]);
    }

    /**
     * GET /admin/users/{id}/edit
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $subject = User::findOrFail((int) $args['id']);

        return $this->render($response, 'admin/users/edit', [
            'title'   => 'Edit User',
            'subject' => $subject,
        ]);
    }

    /**
     * POST /admin/users/{id}/edit
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $subject = User::findOrFail((int) $args['id']);
        $body    = (array) $request->getParsedBody();

        $name   = trim($body['name']   ?? '');
        $email  = trim($body['email']  ?? '');
        $role   = $body['role']        ?? User::ROLE_USER;
        $status = $body['status']      ?? User::STATUS_ACTIVE;
        $errors = [];

        if (!v::stringType()->length(2, 100)->validate($name)) {
            $errors['name'] = 'Name must be between 2 and 100 characters.';
        }
        if (!v::email()->validate($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (User::where('email', $email)->where('id', '!=', $subject->id)->exists()) {
            $errors['email'] = 'That email is already used by another account.';
        }
        if (!in_array($role, [User::ROLE_USER, User::ROLE_ADMIN], true)) {
            $errors['role'] = 'Invalid role.';
        }
        if (!in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            $errors['status'] = 'Invalid status.';
        }

        if (!empty($errors)) {
            return $this->render($response, 'admin/users/edit', [
                'title'   => 'Edit User',
                'subject' => $subject,
                'errors'  => $errors,
                'old'     => compact('name', 'email', 'role', 'status'),
            ]);
        }

        $subject->update(compact('name', 'email', 'role', 'status'));

        // Keep the current admin's own session in sync if they edited themselves
        if ($subject->id === ($_SESSION['user']['id'] ?? null)) {
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['role']  = $role;
        }

        $_SESSION['flash_success'] = "User \"{$subject->name}\" updated successfully.";
        return $this->redirect($response, '/admin/users');
    }

    /**
     * POST /admin/users/{id}/delete
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $subject = User::findOrFail((int) $args['id']);

        if ($subject->id === ($_SESSION['user']['id'] ?? null)) {
            $_SESSION['flash_error'] = 'You cannot delete your own account.';
            return $this->redirect($response, '/admin/users');
        }

        $name = $subject->name;
        $subject->delete();

        $_SESSION['flash_success'] = "User \"{$name}\" was deleted.";
        return $this->redirect($response, '/admin/users');
    }
}
