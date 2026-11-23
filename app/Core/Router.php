<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = [];

    /** $access: 'guest' (anyone), 'auth' (signed in), or a role name. */
    public function add(string $method, string $pattern, array $handler, string $access = 'guest'): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[0-9a-f\-]{36})', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler, $access];
    }
    public function get(string $p, array $h, string $a = 'guest'): void { $this->add('GET', $p, $h, $a); }
    public function post(string $p, array $h, string $a = 'guest'): void { $this->add('POST', $p, $h, $a); }

    /** @return array{0: array, 1: array<string,string>, 2: string}|null */
    public function match(string $method, string $path): ?array
    {
        $allowed = false;
        foreach ($this->routes as [$m, $regex, $handler, $access]) {
            if (preg_match($regex, $path, $mm)) {
                if ($m !== $method) { $allowed = true; continue; }
                return [$handler, array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY), $access];
            }
        }
        if ($allowed) throw new DomainError('invalid', 'method not allowed');
        return null;
    }
}
