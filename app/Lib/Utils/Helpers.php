<?php

declare(strict_types=1);

namespace App\Lib\Utils;

class Helpers
{
    public static function isHtml(mixed $text): bool
    {
        $processed = htmlentities($text);
        if ($processed === $text) {
            return false;
        }
        return true;
    }
}
