<?php

declare(strict_types=1);

namespace App;

/**
 * @property-read ?array $db
 * @property-read ?array $environment
 */
class Config
{
    protected array $config = [];

    public function __construct(array $env)
    {
        $this->config = [
            'db'      => [
                'host'      => $env['DB_HOST'],
                'username'  => $env['DB_USER'],
                'password'  => $env['DB_PASS'],
                'database'  => $env['DB_DATABASE'],
                'driver'    => $env['DB_DRIVER'] ?? 'pdo_mysql',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ],
            'environment' => [
                "debug" => $env["APP_DEBUG"] == "true" ? true : false,
                "maintenance" => $env["APP_MAINTENANCE"] == "true" ? true : false,
                "environment" => $env["APP_ENV"] ?? "prod",
            ],
            'paths' => [
                "storage" => __DIR__."/../storage/",
                "views" => __DIR__."/../resources/views/",
            ],
        ];
    }

    public function __get(string $name)
    {
        return $this->config[$name] ?? null;
    }
}
