<?php

declare(strict_types=1);

namespace App\Core\Enums;

trait EnumToArrayTrait
{
    /** @return array<int|string> */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /** @return array<int|string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<int|string> */
    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }
}
