<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Schema;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Enum\XmlNodeType;

/**
 * XML Object (builder) — OAS 3.1 / 3.2.
 *
 * 3.2: поддерживает nodeType; профиль 3.1 не пропустит этот ключ через FeatureGuard.
 * Конфликты nodeType с attribute/wrapped валидируются в core-модели.
 */
final class Xml implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private ?XmlNodeType $nodeType = null; // 3.2
    private ?string $name = null;
    private ?string $namespace = null;
    private ?string $prefix = null;
    private ?bool $attribute = null; // 3.1 (deprecated в 3.2)
    private ?bool $wrapped = null;   // 3.1 (deprecated в 3.2)

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
    }

    public static function create(?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current());
    }

    public function name(?string $v): self
    {
        $x = clone $this;
        $x->name = $v;

        return $x;
    }

    public function namespace(?string $v): self
    {
        $x = clone $this;
        $x->namespace = $v;

        return $x;
    }

    public function prefix(?string $v): self
    {
        $x = clone $this;
        $x->prefix = $v;

        return $x;
    }

    public function attribute(?bool $v): self
    {
        $x = clone $this;
        $x->attribute = $v;

        return $x;
    }

    public function wrapped(?bool $v): self
    {
        $x = clone $this;
        $x->wrapped = $v;

        return $x;
    }

    /** 3.2-only */
    public function nodeType(?XmlNodeType $type): self
    {
        if ($type !== null) {
            $this->guard->assertAllowedKey('Xml', 'nodeType');
        }
        $x = clone $this;
        $x->nodeType = $type;

        return $x;
    }

    public function toModel(): Core\Xml
    {
        return new Core\Xml(
            nodeType:   $this->nodeType,
            name:       $this->name,
            namespace:  $this->namespace,
            prefix:     $this->prefix,
            attribute:  $this->attribute,
            wrapped:    $this->wrapped,
            extensions: $this->extensions(),
        );
    }
}
