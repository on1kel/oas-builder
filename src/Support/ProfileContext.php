<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support;

use On1kel\OAS\Core\Contract\Profile\FeatureSet;
use On1kel\OAS\Core\Contract\Profile\SpecProfile;

/**
 * Обёртка над профилем спецификации и его флагами.
 */
final class ProfileContext
{
    public function __construct(
        public readonly SpecProfile $profile,
        public readonly FeatureSet $features,
    ) {
    }

    public static function fromProfile(SpecProfile $profile): self
    {
        return new self($profile, $profile->features());
    }

    /**
     * Возвращает идентификатор профиля "MAJOR.MINOR" (например, "3.1", "3.2").
     */
    public function id(): string
    {
        return $this->profile->majorMinor();
    }

    /**
     * Быстрый доступ к произвольному флагу из FeatureSet::extra().
     */
    public function extra(string $flag, bool|int|string|null $default = null): bool|int|string|null
    {
        return $this->features->extraFlag($flag, $default);
    }
}
