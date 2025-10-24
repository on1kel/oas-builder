<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Security;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Core\Model as Core;

/**
 * SecurityRequirement (builder) — карта { schemeName: list<scope> }.
 * В конструкторе core-модель валидирует форматы.
 */
final class SecurityRequirement implements BuildsCoreModel
{
    /** @var array<string, string[]> */
    private array $map = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /** Добавить схему с набором scope'ов. */
    public function add(string $schemeName, string ...$scopes): self
    {
        $x = clone $this;
        $x->map[$schemeName] = \array_values($scopes);

        return $x;
    }

    /** Массовая установка. @param array<string, string[]> $map */
    public function set(array $map): self
    {
        $x = clone $this;
        $x->map = [];
        foreach ($map as $scheme => $scopes) {
            $x->map[$scheme] = \array_values($scopes);
        }

        return $x;
    }

    public function toModel(): Core\SecurityRequirement
    {
        return new Core\SecurityRequirement($this->map);
    }
}
