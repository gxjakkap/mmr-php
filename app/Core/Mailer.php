<?php
declare(strict_types=1);
namespace App\Core;

/**
 * Mail adapter.
 *  MAIL_DRIVER=log  (development) writes to storage/logs/mail.log — nothing is sent.
 *  MAIL_DRIVER=mail uses PHP mail() (the host's sendmail).
 *  MAIL_DRIVER=smtp sends through an SMTP server (Google Workspace, Microsoft 365, Brevo, SendGrid, the host's mail server…):
 *    SMTP_HOST, SMTP_PORT (587 = STARTTLS, 465 = TLS), SMTP_USER, SMTP_PASS.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): void
    {
        $driver = Env::get('MAIL_DRIVER', 'log');
        $from = Env::get('MAIL_FROM', 'no-reply@memorii.local');
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        if ($driver === 'smtp') {
            try { self::smtp($from, $to, $encSubject, $body); }
            catch (\Throwable $e) { error_log('smtp failed for ' . $to . ': ' . $e->getMessage()); }
            return;
        }
        if ($driver === 'mail') {
            $headers = "From: MEMORii <{$from}>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
            if (!mail($to, $encSubject, chunk_split(base64_encode($body)), $headers)) error_log('mail() failed for ' . $to);
            return;
        }
        file_put_contents(BASE_PATH . '/storage/logs/mail.log',
            sprintf("[%s] TO: %s\nSUBJECT: %s\n%s\n----\n", date('c'), $to, $subject, $body), FILE_APPEND | LOCK_EX);
    }

    private static function smtp(string $from, string $to, string $subject, string $body): void
    {
        if (preg_match('/[\r\n]/', $to . $from)) throw new \RuntimeException('bad address');
        $host = Env::get('SMTP_HOST', ''); $port = (int) Env::get('SMTP_PORT', '587');
        if ($host === '') throw new \RuntimeException('SMTP_HOST not set');
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $s = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $err, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$s) throw new \RuntimeException("connect: {$err}");
        stream_set_timeout($s, 15);
        $read = function () use ($s): string {
            $out = '';
            while (($line = fgets($s, 515)) !== false) { $out .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
            return $out;
        };
        $cmd = function (string $c, array $ok) use ($s, $read): string {
            if ($c !== '') fwrite($s, $c . "\r\n");
            $r = $read();
            if (!in_array((int) substr($r, 0, 3), $ok, true)) throw new \RuntimeException('SMTP ' . trim(explode("\r\n", $r)[0]));
            return $r;
        };
        $me = parse_url(Env::get('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        $cmd('', [220]);
        $ehlo = $cmd("EHLO {$me}", [250]);
        if ($port !== 465 && str_contains($ehlo, 'STARTTLS')) {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) throw new \RuntimeException('TLS failed');
            $cmd("EHLO {$me}", [250]);
        }
        if (Env::get('SMTP_USER', '') !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode(Env::get('SMTP_USER', '')), [334]);
            $cmd(base64_encode(Env::get('SMTP_PASS', '')), [235]);
        }
        $cmd("MAIL FROM:<{$from}>", [250]);
        $cmd("RCPT TO:<{$to}>", [250, 251]);
        $cmd('DATA', [354]);
        $msg = "From: MEMORii <{$from}>\r\nTo: <{$to}>\r\nSubject: {$subject}\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(12)) . "@{$me}>\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body));
        $cmd($msg . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($s);
    }
}
