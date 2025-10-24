<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Contracts;

/**
 * Узел, поддерживающий x-* расширения.
 */
interface ExtensibleNode
{
    /**
     * @return array<string,mixed>
     */
    public function extensions(): array;
}
