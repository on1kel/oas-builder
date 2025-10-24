<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Errors;

final class RequiredMissing extends BuildError
{
    public static function field(string $node, string $field): self
    {
        return new self("Отсутствует обязательное поле '{$field}' для узла {$node}.");
    }
}
