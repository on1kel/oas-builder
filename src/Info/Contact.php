<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Info;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * Contact (builder) — OAS 3.1 / 3.2
 *
 * Флюентный билдер для контактной информации владельца/поддержки API.
 *
 * Поля:
 *  - name?: string
 *  - url?: string
 *  - email?: string
 *  - x-* extensions
 */
final class Contact implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $name = null;
    private ?string $url = null;
    private ?string $email = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(?string $v): self
    {
        $x = clone $this;
        $x->name = $v;

        return $x;
    }

    public function url(?string $v): self
    {
        $x = clone $this;
        $x->url = $v;

        return $x;
    }

    public function email(?string $v): self
    {
        $x = clone $this;
        $x->email = $v;

        return $x;
    }

    public function toModel(): Core\Contact
    {
        return new Core\Contact(
            name:       $this->name,
            url:        $this->url,
            email:      $this->email,
            extensions: $this->extensions(),
        );
    }
}
