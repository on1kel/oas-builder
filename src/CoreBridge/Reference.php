<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\CoreBridge;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Core\Model as Core;

/**
 * Reference (builder) — OAS 3.1 / 3.2
 *
 * Простая обёртка для удобного создания Reference-объектов.
 * Используется везде, где допускается $ref вместо вложенного объекта.
 *
 * Пример:
 *   ReferenceBuilder::to('#/components/schemas/User')
 *       ->summary('User schema ref')
 *       ->description('Link to shared User definition')
 *       ->toModel();
 */
final class Reference implements BuildsCoreModel
{
    private string $ref;
    private ?string $summary = null;
    private ?string $description = null;

    private function __construct(string $ref)
    {
        if ($ref === '') {
            throw new \InvalidArgumentException('ReferenceBuilder: "$ref" не может быть пустым.');
        }
        $this->ref = $ref;
    }

    public static function to(string $ref): self
    {
        return new self($ref);
    }

    public function summary(?string $text): self
    {
        $x = clone $this;
        $x->summary = $text;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function toModel(): Core\Reference
    {
        return new Core\Reference(
            ref: $this->ref,
            summary: $this->summary,
            description: $this->description,
        );
    }
}
