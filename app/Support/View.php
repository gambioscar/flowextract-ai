<?php

declare(strict_types=1);

namespace FlowExtract\Support;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): void
    {
        $root = dirname(__DIR__, 2) . '/resources/views';
        $file = $root . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vista non trovata: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();
        require $root . '/layout.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
