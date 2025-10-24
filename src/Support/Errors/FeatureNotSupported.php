<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Errors;

final class FeatureNotSupported extends BuildError
{
    public static function feature(string $feature, string $profileId): self
    {
        return new self("Фича '{$feature}' не поддерживается активным профилем {$profileId}.");
    }
}
