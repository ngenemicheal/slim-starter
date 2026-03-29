<?php

/**
 * TwigExtension — custom global functions and filters available in every template.
 *
 * Available functions:
 *   session('user')         → $_SESSION['user']
 *   flash('success')        → one-time session message (cleared after reading)
 *   current_path()          → e.g. '/admin/users'
 *
 * Available filters:
 *   {{ user.created_at | date_fmt('M d, Y') }}
 *   {{ 'hello world'   | ucwords }}
 */

declare(strict_types=1);

namespace App\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Global variables available in every template as {{ app_name }}, {{ app_env }}.
     */
    public function getGlobals(): array
    {
        return [
            'app_name' => $_ENV['APP_NAME'] ?? 'Slim Starter',
            'app_env'  => $_ENV['APP_ENV']  ?? 'development',
        ];
    }

    public function getFunctions(): array
    {
        return [
            // session('user') → $_SESSION['user'] ?? null
            // session()       → entire $_SESSION array
            new TwigFunction('session', function (string $key = null): mixed {
                if ($key === null) {
                    return $_SESSION ?? [];
                }
                return $_SESSION[$key] ?? null;
            }),

            // flash('success') → reads and clears $_SESSION['flash_success']
            new TwigFunction('flash', function (string $key): ?string {
                $value = $_SESSION["flash_{$key}"] ?? null;
                unset($_SESSION["flash_{$key}"]);
                return $value;
            }),

            // current_path() → '/admin/users'
            new TwigFunction('current_path', function (): string {
                return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
            }),
        ];
    }

    public function getFilters(): array
    {
        return [
            // {{ user.created_at | date_fmt }}
            // {{ user.created_at | date_fmt('Y-m-d') }}
            new TwigFilter('date_fmt', function (mixed $date, string $format = 'M d, Y'): string {
                if (!$date) {
                    return '—';
                }
                if ($date instanceof \DateTimeInterface) {
                    return $date->format($format);
                }
                return (new \DateTime((string) $date))->format($format);
            }),

            // {{ role | ucwords }}
            new TwigFilter('ucwords', fn (string $s): string => ucwords($s)),
        ];
    }
}
