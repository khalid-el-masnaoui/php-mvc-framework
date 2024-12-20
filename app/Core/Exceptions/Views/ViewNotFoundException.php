<?php

declare(strict_types=1);

namespace App\Core\Exceptions\Views;

class ViewNotFoundException extends \Exception
{
    protected $message = 'View not found';
}
