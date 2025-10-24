<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Common;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * ExternalDocumentation (builder) — OAS 3.1 / 3.2
 *
 * Поля:
 *  - description?: string
 *  - url: string (required)
 *  - x-* extensions
 */
final class ExternalDocumentation implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $url = null;
    private ?string $description = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function url(string $url): self
    {
        $x = clone $this;
        $x->url = $url;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function toModel(): Core\ExternalDocumentation
    {
        return new Core\ExternalDocumentation(
            url: (string)$this->url,
            description: $this->description,
            extensions: $this->extensions(),
        );
    }
}
