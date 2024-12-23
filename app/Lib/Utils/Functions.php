<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? false;

    if ($value === false) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;

        case 'false':
        case '(false)':
            return false;

        case 'empty':
        case '(empty)':
            return '';

        case 'null':
        case '(null)':
            return null;
    }

    if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
        return substr($value, 1, -1);
    }

    return $value;
}

function isHtml(mixed $text): bool
{
    $processed = htmlentities((string) $text);
    if ($processed === $text) {
        return false;
    }
    return true;
}
