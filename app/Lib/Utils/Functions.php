<?php

declare(strict_types=1);

function isHtml(mixed $text): bool
{
    $processed = htmlentities((string) $text);
    if ($processed === $text) {
        return false;
    }
    return true;
}
