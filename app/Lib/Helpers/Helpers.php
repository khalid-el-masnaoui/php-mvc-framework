<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Response;
use App\Lib\Application\Application;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Lib\Application\Support\Redirect;

if (!function_exists('app')) {
    /**
     * Get Application instance.
     */
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('config')) {
    /**
     * Get config data.
     * @return string|array<string,int|string>
     */
    function config(string $file, string $key): string | array
    {
        $data = require APP_ROOT . "/config/{$file}.php";

        if (isset($data[$key])) {
            return $data[$key];
        } else {
            throw new \Exception("Key:($key) Not Found");
        }
    }
}

if (!function_exists('env')) {
    /**
     * Get env data.
     */
    function env(string $key, ?string $default = null): mixed
    {
        $dotenv = Dotenv::createImmutable(APP_ROOT);
        $dotenv->safeLoad();

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
}

if (!function_exists('request')) {
    /**
     * Get request instance.
     */
    function request(): RequestInterface
    {
        return new Request();
    }
}

if (!function_exists('response')) {
    /**
     * Get request instance.
     */
    function response(): ResponseInterface
    {
        return new Response();
    }
}

if (!function_exists('response')) {
    /**
     * Get request instance.
     */
    function response(): ResponseInterface
    {
        return new Response();
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect link.
     */
    function redirect(string $redirectLink): Redirect
    {
        return (new Redirect())->redirect($redirectLink);
    }
}

if (!function_exists('back')) {
    /**
     * Create a new redirect response to the previous location.
     */
    function back(): Redirect
    {
        return (new Redirect())->back();
    }
}
