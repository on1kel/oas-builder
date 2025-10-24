<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Traits;

use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;

trait UsesProfile
{
    protected ProfileContext $profile;

    /**
     * Взять контекст: либо явно переданный, либо активный из ProfileProvider.
     */
    final protected static function ctx(?ProfileContext $ctx = null): ProfileContext
    {
        return $ctx ?? ProfileProvider::current();
    }
}
