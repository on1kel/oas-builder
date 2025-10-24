<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Errors;

final class InvalidCombination extends BuildError
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
