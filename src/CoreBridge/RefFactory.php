<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\CoreBridge;

use On1kel\OAS\Core\Model\Reference;

final class RefFactory
{
    public static function fromString(string $ref, ?string $summary = null, ?string $description = null): Reference
    {
        return new Reference($ref, $summary, $description);
    }
}
