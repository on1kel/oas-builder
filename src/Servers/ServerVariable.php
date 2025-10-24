<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Servers;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * ServerVariable (builder) — OAS 3.1 / 3.2
 *
 * Поля:
 *  - enum?: string[]
 *  - default: string (required)
 *  - description?: string
 *  - x-* extensions
 */
final class ServerVariable implements BuildsCoreModel
{
    use HasExtensions;

    /** @var string[]|null */
    private ?array $enum = null;

    private ?string $default = null;
    private ?string $description = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param string ...$values
     * @return ServerVariable
     */
    public function enum(string ...$values): self
    {
        $x = clone $this;
        $x->enum = $values ?: null;

        return $x;
    }

    public function default(string $value): self
    {
        $x = clone $this;
        $x->default = $value;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function toModel(): Core\ServerVariable
    {
        return new Core\ServerVariable(
            default: (string)$this->default,
            description: $this->description,
            enum: $this->enum ?? [],
            extensions: $this->extensions(),
        );
    }
}
