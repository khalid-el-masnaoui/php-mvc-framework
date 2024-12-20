<?php

declare(strict_types=1);

namespace App\Core\Exceptions\Views;

use Psr\Container\NotFoundExceptionInterface;

class ViewNotFoundException extends \Exception implements NotFoundExceptionInterface
{
    protected $message = 'View not found';
}
