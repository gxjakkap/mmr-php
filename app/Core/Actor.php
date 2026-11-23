<?php
declare(strict_types=1);
namespace App\Core;

/** The authenticated caller. Every service method receives one explicitly. */
final class Actor
{
    /** @param string[] $roles */
    public function __construct(public readonly string $id, public readonly array $roles, public readonly string $displayName = '') {}

    public function has(string $role): bool { return in_array($role, $this->roles, true); }
    public function isAdmin(): bool { return $this->has('admin'); }
    public function isStaff(): bool { return (bool) array_intersect($this->roles, ['admin', 'designer', 'tailor', 'qc', 'support']); }

    public static function load(Db $db, string $userId): ?self
    {
        $u = $db->one('SELECT id, display_name FROM users WHERE id = ? AND is_active = 1', [$userId]);
        if (!$u) return null;
        $roles = array_column($db->all('SELECT role FROM user_roles WHERE user_id = ?', [$userId]), 'role');
        return new self($u['id'], $roles, $u['display_name']);
    }
}
