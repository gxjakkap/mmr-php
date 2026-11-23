<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    /** Page title set by templates (they render before the layout). */
    public static ?string $title = null;
    /** Full-bleed page (no padded container), e.g. the home hero. */
    public static bool $full = false;
    /** Extra class on <body> (e.g. 'on-home' lets the mobile header float over the hero). */
    public static string $bodyClass = '';

    /** Renders app/Views/{$template}.php inside a layout. Templates must escape with e(). */
    public static function render(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        $content = self::partial($template, $data);
        return $layout ? self::partial($layout, $data + ['content' => $content]) : $content;
    }
    public static function partial(string $template, array $data = []): string
    {
        if (!preg_match('#^[a-z0-9_/\-]+$#', $template)) throw new \InvalidArgumentException('bad template');
        return self::isolate(BASE_PATH . '/app/Views/' . $template . '.php', $data);
    }
    /** Renders in a scope that contains only the template's own data (no local-variable collisions). */
    private static function isolate(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        unset($__data);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    }
}
