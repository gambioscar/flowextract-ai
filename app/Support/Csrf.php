<?php

declare(strict_types=1);

namespace FlowExtract\Support;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function verify(?string $token): void
    {
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            throw new RuntimeException('Sessione scaduta o richiesta non valida. Ricarica la pagina.');
        }
    }
}
