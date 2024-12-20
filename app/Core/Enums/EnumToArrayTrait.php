<?php

declare(strict_types=1);

namespace App\Core\Enums;

trait EnumToArrayTrait
{
    /** @return mixed[] */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /** @return mixed[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return mixed[] */
    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }
}
