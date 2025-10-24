<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Info;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * License (builder) — OAS 3.1 / 3.2
 *
 * Флюентный билдер для объекта лицензии API.
 *
 * Поддерживает:
 *  - name (обязательно)
 *  - identifier? (3.1+)
 *  - url? (URI строки)
 *  - x-* extensions
 */
final class License implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $name = null;
    private ?string $identifier = null;
    private ?string $url = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(string $name): self
    {
        $x = clone $this;
        $x->name = $name;

        return $x;
    }

    public function identifier(?string $id): self
    {
        $x = clone $this;
        $x->identifier = $id;

        return $x;
    }

    public function url(?string $url): self
    {
        $x = clone $this;
        $x->url = $url;

        return $x;
    }

    public function toModel(): Core\License
    {
        return new Core\License(
            name:       $this->name,
            identifier: $this->identifier,
            url:        $this->url,
            extensions: $this->extensions(),
        );
    }
}
