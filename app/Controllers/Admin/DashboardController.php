<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * GET /admin
     */
    public function index(Request $request, Response $response): Response
    {
        $weekAgo = (new \DateTime('-7 days'))->format('Y-m-d H:i:s');

        return $this->render($response, 'admin/dashboard', [
            'title'         => 'Dashboard',
            'total_users'   => User::count(),
            'active_users'  => User::where('status', User::STATUS_ACTIVE)->count(),
            'admin_users'   => User::where('role', User::ROLE_ADMIN)->count(),
            'new_this_week' => User::where('created_at', '>=', $weekAgo)->count(),
            'recent_users'  => User::orderBy('created_at', 'desc')->take(8)->get(),
        ]);
    }
}
