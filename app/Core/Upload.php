<?php
declare(strict_types=1);
namespace App\Core;

/**
 * Image upload handling. Files are stored OUTSIDE the web root (storage/uploads) with random names
 * and are only served by MediaController after a Policy check.
 */
final class Upload
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const MIME_EXT = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Normalises $_FILES['x'] (multiple) into a list. */
    public static function files(string $field): array
    {
        $f = $_FILES[$field] ?? null;
        if (!$f || !isset($f['name'])) return [];
        if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        $out = [];
        foreach ($f['name'] as $i => $_) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $out[] = ['name' => $f['name'][$i], 'tmp_name' => $f['tmp_name'][$i], 'size' => $f['size'][$i], 'error' => $f['error'][$i]];
        }
        return $out;
    }

    /**
     * Validates by content (finfo + getimagesize), never by the client-supplied name or type.
     * @return array{mime:string, ext:string, size:int}
     */
    public static function inspect(string $tmpPath, int $size, int $error = UPLOAD_ERR_OK): array
    {
        if ($error !== UPLOAD_ERR_OK) throw DomainError::invalid('upload_failed');
        if ($size <= 0 || $size > self::MAX_BYTES) throw DomainError::invalid('photo_too_big');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: '';
        if (!isset(self::MIME_EXT[$mime])) throw DomainError::invalid('photo_bad_type');
        $info = @getimagesize($tmpPath);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 12000 || $info[1] > 12000) throw DomainError::invalid('photo_bad_type');
        return ['mime' => $mime, 'ext' => self::MIME_EXT[$mime], 'size' => $size];
    }

    public static function root(): string
    {
        return rtrim(Env::get('UPLOAD_DIR', BASE_PATH . '/storage/uploads'), '/');
    }

    /** Moves a validated file to {root}/{relative}. Uses rename() for CLI tests, move_uploaded_file() for HTTP. */
    public static function store(string $tmpPath, string $relative): void
    {
        if (!preg_match('#^[0-9a-f\-]{36}/[0-9a-f\-]{36}/[0-9a-f\-]{36}\.(jpg|png|webp)$#', $relative)) throw DomainError::invalid('bad path');
        $dest = self::root() . '/' . $relative;
        if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0750, true) && !is_dir(dirname($dest))) throw new \RuntimeException('mkdir failed');
        $ok = is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $dest) : (PHP_SAPI === 'cli' && copy($tmpPath, $dest));
        if (!$ok) throw new \RuntimeException('store failed');
        chmod($dest, 0640);
    }
    public static function delete(string $relative): void
    {
        $p = self::root() . '/' . $relative;
        if (is_file($p) && str_starts_with(realpath($p) ?: '', realpath(self::root()) ?: '//')) unlink($p);
    }
    public static function path(string $relative): ?string
    {
        $p = realpath(self::root() . '/' . $relative);
        return $p && str_starts_with($p, (string) realpath(self::root())) ? $p : null;
    }
}
