<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, DomainError, Env, Http, I18n, RateLimiter, Session};
use App\Services\AuthService;

final class AuthController extends Controller
{
    private function svc(): AuthService { return new AuthService($this->db); }

    public function loginForm(): string
    {
        if ($a = Auth::actor()) Http::redirect(Auth::homeFor($a));
        return $this->view('auth/login', ['next' => Http::safePath($this->query('next'), '')]);
    }
    public function login(): never
    {
        $email = (string) $this->input('email');
        $next = Http::safePath($this->input('next'), '');
        $back = '/login' . ($next ? '?next=' . rawurlencode($next) : '');
        if (!RateLimiter::hit('login:ip:' . RateLimiter::clientIp(), 20, 600) || !RateLimiter::hit('login:email:' . strtolower($email), 8, 600)) {
            $this->fail(t('auth.tooMany'), $back, ['email' => $email]);
        }
        try {
            $uid = $this->svc()->attempt($email, (string) $this->input('password'));
        } catch (DomainError $e) {
            if ($e->getMessage() === 'auth_unverified') {
                $u = $this->db->one('SELECT id, email FROM users WHERE email = ?', [strtolower(trim($email))]);
                if ($u && RateLimiter::hit('verify-resend:' . $u['id'], 3, 3600)) $this->svc()->sendVerification($u['id'], $u['email']);
                $this->fail(t('auth.unverified'), $back, ['email' => $email]);
            }
            $this->fail($e, $back, ['email' => $email]);
        }
        Auth::login($uid);
        $locale = $this->db->value('SELECT locale FROM users WHERE id = ?', [$uid]);
        if (!isset($_COOKIE['memorii_locale']) && $locale) setcookie('memorii_locale', $locale, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        Http::redirect($next ?: Auth::homeFor(Auth::actor()));
    }

    public function registerForm(): string { return $this->view('auth/register'); }
    public function register(): never
    {
        $old = ['email' => $this->input('email'), 'display_name' => $this->input('display_name')];
        if (!RateLimiter::hit('register:' . RateLimiter::clientIp(), 5, 3600)) $this->fail(t('auth.tooMany'), '/register', $old);
        if (empty($_POST['accept_terms'])) $this->fail(L('กรุณายอมรับข้อกำหนดการใช้งานก่อนสมัคร', 'Please accept the terms of use to register'), '/register', $old);
        try {
            $this->svc()->register((string) $this->input('email'), (string) $this->input('password'), (string) $this->input('display_name'), I18n::locale());
        } catch (DomainError $e) {
            $this->fail($e, '/register', $old);
        }
        $msg = t('auth.checkEmail') . (Env::get('MAIL_DRIVER', 'log') === 'log' ? ' ' . t('auth.devMail') : '');
        $this->ok($msg, '/login');
    }

    public function verify(): never
    {
        $ok = $this->svc()->verifyEmail((string) $this->query('token'));
        $ok ? $this->ok(t('auth.verified'), '/login') : $this->fail(t('auth.linkInvalid'), '/login');
    }

    public function forgotForm(): string { return $this->view('auth/forgot'); }
    public function forgot(): never
    {
        if (RateLimiter::hit('reset:' . RateLimiter::clientIp(), 5, 3600)) $this->svc()->requestReset((string) $this->input('email'));
        $this->ok(t('auth.resetSent'), '/forgot-password'); // identical response either way
    }

    public function resetForm(): string
    {
        $token = (string) $this->query('token');
        if (!$this->svc()->tokenIsValid($token, 'reset_password')) $this->fail(t('auth.linkInvalid'), '/forgot-password');
        return $this->view('auth/reset', ['token' => $token]);
    }
    public function reset(): never
    {
        $token = (string) $this->input('token');
        try {
            $this->svc()->resetPassword($token, (string) $this->input('password'));
        } catch (DomainError $e) {
            $this->fail($e, '/reset-password?token=' . rawurlencode($token));
        }
        Auth::logout();
        Session::start();
        $this->ok(t('auth.passwordUpdated'), '/login');
    }

    public function logout(): never
    {
        Auth::logout();
        Http::redirect('/');
    }
}
