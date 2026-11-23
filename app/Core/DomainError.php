<?php
declare(strict_types=1);
namespace App\Core;

/** Business-rule failure. kind: forbidden | invalid | not_found. Mapped to 403/422/404. */
final class DomainError extends \RuntimeException
{
    public function __construct(public readonly string $kind, string $message)
    {
        parent::__construct($message);
    }
    public static function forbidden(string $m = 'forbidden'): self { return new self('forbidden', $m); }
    public static function invalid(string $m): self { return new self('invalid', $m); }
    public static function notFound(string $m = 'not found'): self { return new self('not_found', $m); }
    public function httpStatus(): int
    {
        return match ($this->kind) { 'forbidden' => 403, 'not_found' => 404, default => 422 };
    }
}
