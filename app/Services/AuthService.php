<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Audit, Db, DomainError, Env, Mailer};

final class AuthService
{
    public function __construct(private Db $db) {}

    public function register(string $email, string $password, string $displayName, string $locale = 'th'): string
    {
        $email = Validate::email($email);
        $password = Validate::password($password);
        $name = Validate::text($displayName, 'display_name', 80, 1);
        if ($this->db->value('SELECT 1 FROM users WHERE email = ?', [$email])) {
            // Do not reveal whether the address is registered: behave as success, notify the owner instead.
            Mailer::send($email, 'MEMORii', "Someone tried to create a MEMORii account with this email. If it was you, use “Forgot password”.");
            return '';
        }
        $id = Db::uuid();
        $this->db->tx(function (Db $db) use ($id, $email, $password, $name, $locale) {
            $db->insert('users', ['id' => $id, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $name, 'locale' => $locale === 'en' ? 'en' : 'th']);
            $db->insert('user_roles', ['id' => Db::uuid(), 'user_id' => $id, 'role' => 'customer']);
            Audit::log($db, $id, 'user.registered', 'user', $id);
        });
        $this->sendVerification($id, $email);
        return $id;
    }

    public function sendVerification(string $userId, string $email): void
    {
        $token = $this->issueToken($userId, 'verify_email', 60 * 60 * 24);
        $link = rtrim(Env::get('APP_URL', 'http://localhost:8000'), '/') . '/verify-email?token=' . $token;
        Mailer::send($email, 'ยืนยันอีเมล MEMORii / Confirm your MEMORii email', "ยืนยันอีเมล / Confirm your email:\n{$link}\n");
    }

    public function verifyEmail(string $token): bool
    {
        $row = $this->consumeToken($token, 'verify_email');
        if (!$row) return false;
        $this->db->exec('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [$row['user_id']]);
        Audit::log($this->db, $row['user_id'], 'user.email_verified', 'user', $row['user_id']);
        return true;
    }

    /** @return string user id. Throws invalid('auth_invalid') or invalid('auth_unverified'). */
    public function attempt(string $email, string $password): string
    {
        $email = strtolower(trim($email));
        $u = $this->db->one('SELECT id, password_hash, email_verified_at, is_active FROM users WHERE email = ?', [$email]);
        // Hash even when the user is missing so response time does not reveal registered emails.
        $ok = password_verify($password, $u['password_hash'] ?? password_hash('timing-equaliser', PASSWORD_DEFAULT));
        if (!$u || !$ok || !(int) $u['is_active']) throw DomainError::invalid('auth_invalid');
        if (!$u['email_verified_at']) throw DomainError::invalid('auth_unverified');
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        Audit::log($this->db, $u['id'], 'user.login', 'user', $u['id']);
        return $u['id'];
    }

    public function requestReset(string $email): void
    {
        try { $email = Validate::email($email); } catch (DomainError) { return; }
        $u = $this->db->one('SELECT id FROM users WHERE email = ? AND is_active = 1', [$email]);
        if (!$u) return; // same response either way
        $token = $this->issueToken($u['id'], 'reset_password', 60 * 60);
        $link = rtrim(Env::get('APP_URL', 'http://localhost:8000'), '/') . '/reset-password?token=' . $token;
        Mailer::send($email, 'รีเซ็ตรหัสผ่าน MEMORii / Reset your MEMORii password', "{$link}\n(1 hour)");
    }

    public function tokenIsValid(string $token, string $purpose): bool
    {
        return (bool) $this->db->value('SELECT 1 FROM auth_tokens WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token), $purpose]);
    }

    public function resetPassword(string $token, string $password): void
    {
        $password = Validate::password($password);
        $this->db->tx(function (Db $db) use ($token, $password) {
            $row = $this->consumeToken($token, 'reset_password');
            if (!$row) throw DomainError::invalid('link_invalid');
            $db->exec('UPDATE users SET password_hash = ?, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
            $db->exec("UPDATE auth_tokens SET used_at = NOW() WHERE user_id = ? AND purpose = 'reset_password' AND used_at IS NULL", [$row['user_id']]);
            Audit::log($db, $row['user_id'], 'user.password_reset', 'user', $row['user_id']);
        });
    }

    private function issueToken(string $userId, string $purpose, int $ttl): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->insert('auth_tokens', ['id' => Db::uuid(), 'user_id' => $userId, 'purpose' => $purpose,
            'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', time() + $ttl)]);
        return $token;
    }
    private function consumeToken(string $token, string $purpose): ?array
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) return null;
        return $this->db->tx(function (Db $db) use ($token, $purpose) {
            $row = $db->one('SELECT id, user_id FROM auth_tokens WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE',
                [hash('sha256', $token), $purpose]);
            if ($row) $db->exec('UPDATE auth_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
            return $row;
        });
    }
}
