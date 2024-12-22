<?php

declare(strict_types=1);

namespace App\Lib\Psr15\Dispatcher\Matchers;

trait NegativeResultTrait
{
    private bool $result = true;

    private function getValue(string $value): string
    {
        if ($value[0] === '!') {
            $this->result = false;
            return substr($value, 1);
        }

        return $value;
    }
}
