<?php
declare(strict_types=1);
namespace App\Core;

/** Session-based authentication state for the current HTTP request. */
final class Auth
{
    private static ?Actor $actor = null;
    private static bool $loaded = false;

    public static function actor(): ?Actor
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('uid');
            if (is_string($id)) self::$actor = Actor::load(Db::get(), $id);
            if ($id && !self::$actor) Session::destroy(); // deactivated or deleted user
        }
        return self::$actor;
    }
    public static function login(string $userId): void
    {
        Session::regenerate(); // prevent session fixation
        Session::set('uid', $userId);
        self::$loaded = false;
    }
    public static function logout(): void
    {
        Session::destroy();
        self::$actor = null;
        self::$loaded = true;
    }
    public static function homeFor(Actor $a): string
    {
        if ($a->isAdmin()) return '/admin';
        if ($a->has('designer')) return '/designer';
        return '/wardrobe';
    }
}
