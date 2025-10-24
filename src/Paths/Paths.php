<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Paths;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\PathItemMap;

final class Paths implements BuildsCoreModel
{
    use HasExtensions;

    /** @var array<string, PathItem|string> */
    private array $routes = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /** Добавить/заменить PathItem (флюент) по пути. */
    public function put(string $path, PathItem $item): self
    {
        $this->assertPath($path);
        $x = clone $this;
        $x->routes[$path] = $item;

        return $x;
    }

    /** Добавить/заменить ссылку $ref (строкой) по пути. */
    public function ref(string $path, string $ref): self
    {
        $this->assertPath($path);
        if ($ref === '') {
            throw new \InvalidArgumentException('Paths: $ref не может быть пустым.');
        }
        $x = clone $this;
        $x->routes[$path] = $ref; // конверсия в core — в toModel()

        return $x;
    }

    /**
     * Массовая загрузка: ['/{id}' => PathItem|string($ref), ...]
     * @param array<string, PathItem|string> $map
     */
    public function map(array $map): self
    {
        $x = clone $this;
        foreach ($map as $path => $node) {
            $this->assertPath($path);
            if (!is_string($node) && !($node instanceof PathItem)) {
                $got = is_object($node) ? $node::class : gettype($node);
                throw new \InvalidArgumentException("Paths: значение для '{$path}' должно быть PathItem или \$ref-строкой, получено {$got}.");
            }
            $x->routes[$path] = $node;
        }

        return $x;
    }

    /** Шорткаты (по желанию оставляем самые частые) */
    public function get(string $path, Operation $op): self
    {
        return $this->put($path, PathItem::create()->get($op));
    }
    public function post(string $path, Operation $op): self
    {
        return $this->put($path, PathItem::create()->post($op));
    }
    public function putMethod(string $path, Operation $op): self
    {
        return $this->put($path, PathItem::create()->put($op));
    }
    public function delete(string $path, Operation $op): self
    {
        return $this->put($path, PathItem::create()->delete($op));
    }

    public function remove(string $path): self
    {
        $x = clone $this;
        unset($x->routes[$path]);

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Paths
    {
        $map = [];
        foreach ($this->routes as $route => $node) {
            $map[(string)$route] = is_string($node)
                ? RefFactory::fromString($node)
                : $node->toModel();
        }

        /** @var PathItemMap $pathMap */
        $pathMap = Assembler::map(PathItemMap::class, $map);

        return new Core\Paths(
            items:      $pathMap,
            extensions: $this->extensions(),
        );
    }

    // ── Внутреннее ─────────────────────────────────────────────────────────

    private function assertPath(string $path): void
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Paths: path не может быть пустым.');
        }
        // опционально: требовать начальный слэш
        if ($path[0] !== '/') {
            throw new \InvalidArgumentException("Paths: path '{$path}' должен начинаться со '/'.");
        }
    }
}
