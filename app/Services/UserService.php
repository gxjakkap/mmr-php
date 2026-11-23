<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Actor, Audit, Db, DomainError, Policy};

final class UserService
{
    public function __construct(private Db $db) {}

    public function profile(Actor $a): array
    {
        return $this->db->one('SELECT id, email, display_name, phone, locale, email_verified_at FROM users WHERE id = ?', [$a->id]) ?? [];
    }
    public function updateProfile(Actor $a, array $in): void
    {
        $name = Validate::text($in['display_name'] ?? '', 'display_name', 80, 1);
        $phone = Validate::optText($in['phone'] ?? '', 'phone', 20);
        if ($phone !== null && !preg_match('/^[0-9+\- ]{6,20}$/', $phone)) throw DomainError::invalid('invalid phone');
        $locale = ($in['locale'] ?? 'th') === 'en' ? 'en' : 'th';
        $this->db->exec('UPDATE users SET display_name = ?, phone = ?, locale = ? WHERE id = ?', [$name, $phone, $locale, $a->id]);
    }

    public function listForAdmin(Actor $a): array
    {
        Policy::requireAdmin($a);
        $users = $this->db->all('SELECT id, email, display_name, email_verified_at, created_at FROM users ORDER BY created_at DESC LIMIT 200');
        $roles = [];
        foreach ($this->db->all('SELECT user_id, role FROM user_roles') as $r) $roles[$r['user_id']][] = $r['role'];
        foreach ($users as &$u) $u['roles'] = $roles[$u['id']] ?? [];
        return $users;
    }

    public function grantRole(Actor $a, string $userId, string $role, ?string $publicName = null): void
    {
        Policy::requireAdmin($a);
        $userId = Validate::uuid($userId, 'user');
        $role = Validate::enum($role, Validate::ROLES, 'role');
        $publicName = Validate::optText($publicName ?? '', 'public_name', 80);
        $this->db->tx(function (Db $db) use ($a, $userId, $role, $publicName) {
            $u = $db->one('SELECT id, display_name FROM users WHERE id = ?', [$userId]);
            if (!$u) throw DomainError::notFound('user not found');
            $db->exec('INSERT IGNORE INTO user_roles (id, user_id, role, granted_by) VALUES (?, ?, ?, ?)', [Db::uuid(), $userId, $role, $a->id]);
            if ($role === 'designer') {
                $name = $publicName ?? ($u['display_name'] !== '' ? $u['display_name'] : 'Designer');
                $db->exec('INSERT INTO designers (id, user_id, public_name, specialties) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE is_active = 1', [Db::uuid(), $userId, $name, '[]']);
            }
            Audit::log($db, $a->id, 'role.granted', 'user', $userId, ['role' => $role]);
        });
    }

    public function activeDesigners(): array
    {
        return $this->db->all('SELECT id, public_name FROM designers WHERE is_active = 1 ORDER BY public_name');
    }
    public function templates(): array
    {
        return $this->db->all('SELECT * FROM design_templates WHERE is_active = 1 ORDER BY sort_order');
    }
    public function audit(Actor $a): array
    {
        Policy::requireAdmin($a);
        return $this->db->all('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 200');
    }
}
