<?php

declare(strict_types=1);

namespace App\Core\Services\Views;

use App\Core\Exceptions\Views\ViewNotFoundException;

class View
{
    /** @param mixed[] $params */
    public function __construct(
        protected string $view,
        protected array $params = []
    ) {
    }

    /** @param mixed[] $params */
    public static function make(string $view, array $params = []): self
    {
        return new self($view, $params);
    }

    public function render(): string
    {
        $viewPath =  __DIR__ . '/../../../../resources/views/' . $this->view . '.php';

        if (!file_exists($viewPath)) {
            throw new ViewNotFoundException('ViewNotFoundException', 404);
        }

        foreach ($this->params as $key => $value) {
            $$key = $value;
        }

        ob_start();

        include $viewPath;

        return (string) ob_get_clean();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __get(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }
}
