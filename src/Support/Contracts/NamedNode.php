<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Contracts;

interface NamedNode
{
    public function name(): ?string;
}
