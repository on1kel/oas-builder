<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Contracts;

use On1kel\OAS\Builder\Support\ProfileContext;

interface ProfileAware
{
    public function profile(): ProfileContext;
}
