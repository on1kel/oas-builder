<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Schema;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Errors\InvalidCombination;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;

/**
 * Discriminator (builder) — OAS 3.1 / 3.2
 *
 * Поля:
 *  - propertyName (required)
 *  - mapping: map<string,string>
 *  - defaultMapping (3.2+)
 */
final class Discriminator implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private string $propertyName;
    /** @var array<string,string>|null */
    private ?array $mapping = null;
    private ?string $defaultMapping = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx, string $propertyName)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->propertyName = $propertyName;
    }

    /**
     * Статическая фабрика.
     */
    public static function of(string $propertyName, ?ProfileContext $ctx = null): self
    {
        $ctx = $ctx ?? ProfileProvider::current();

        return new self($ctx, $propertyName);
    }

    /**
     * Переустановить имя свойства (если нужно).
     */
    public function property(string $propertyName): self
    {
        $x = clone $this;
        $x->propertyName = $propertyName;

        return $x;
    }

    /**
     * Полная замена mapping.
     * @param array<string,string> $map
     */
    public function map(array $map): self
    {
        $x = clone $this;
        $x->mapping = $map ?: null;

        return $x;
    }

    /**
     * Добавить/перезаписать одно соответствие.
     */
    public function put(string $discriminatorValue, string $refOrName): self
    {
        $x = clone $this;
        $m = $x->mapping ?? [];
        $m[$discriminatorValue] = $refOrName;
        $x->mapping = $m;

        return $x;
    }

    /**
     * Удалить одно соответствие.
     */
    public function remove(string $discriminatorValue): self
    {
        $x = clone $this;
        if ($x->mapping !== null) {
            unset($x->mapping[$discriminatorValue]);
            if ($x->mapping === []) {
                $x->mapping = null;
            }
        }

        return $x;
    }

    /**
     * 3.2+: defaultMapping. В 3.1 — FeatureNotSupported.
     */
    public function defaultMapping(?string $refOrName): self
    {
        if ($refOrName !== null) {
            $this->guard->assertAllowedKey('Discriminator', 'defaultMapping');
        }
        $x = clone $this;
        $x->defaultMapping = $refOrName;

        return $x;
    }

    public function toModel(): Core\Discriminator
    {
        // DX-страховка: если defaultMapping задан в профиле, где он запрещён, кинем понятную ошибку
        if ($this->defaultMapping !== null) {
            try {
                $this->guard->assertAllowedKey('Discriminator', 'defaultMapping');
            } catch (\Throwable $e) {
                throw InvalidCombination::because('Discriminator: "defaultMapping" доступен только в OAS 3.2+.');
            }
        }

        return new Core\Discriminator(
            propertyName:   $this->propertyName,
            mapping:        $this->mapping,
            defaultMapping: $this->defaultMapping,
            extensions:     $this->extensions(),
        );
    }
}
