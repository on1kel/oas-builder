<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Media;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\HeaderMap;

/**
 * Encoding (builder) — OAS 3.1 / 3.2
 *
 * Core\Encoding ожидает:
 *  - contentType?: string
 *  - headers?: HeaderMap
 *  - style?: string
 *  - explode?: bool
 *  - allowReserved?: bool
 */
final class Encoding implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private ?string $contentType = null;
    /** @var array<string, Core\Header|Core\Reference>|null */
    private ?array $headers = null;
    private ?string $style = null;
    private ?bool $explode = null;
    private ?bool $allowReserved = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
    }

    public static function of(?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current());
    }

    public function contentType(?string $mime): self
    {
        $this->guard->assertAllowedKey('Encoding', 'contentType');
        $x = clone $this;
        $x->contentType = $mime;

        return $x;
    }

    /**
     * @param array<string, Core\Header|Core\Reference> $headers
     */
    public function headers(array $headers): self
    {
        $this->guard->assertAllowedKey('Encoding', 'headers');
        $x = clone $this;
        $x->headers = $headers ?: null;

        return $x;
    }

    public function style(?string $style): self
    {
        $this->guard->assertAllowedKey('Encoding', 'style');
        $x = clone $this;
        $x->style = $style;

        return $x;
    }

    public function explode(?bool $flag): self
    {
        $this->guard->assertAllowedKey('Encoding', 'explode');
        $x = clone $this;
        $x->explode = $flag;

        return $x;
    }

    public function allowReserved(?bool $flag): self
    {
        $this->guard->assertAllowedKey('Encoding', 'allowReserved');
        $x = clone $this;
        $x->allowReserved = $flag;

        return $x;
    }

    public function toModel(): Core\Encoding
    {
        return new Core\Encoding(
            contentType: $this->contentType,
            headers:     Assembler::mapOrNull(HeaderMap::class, $this->headers),
            style:       $this->style,
            explode:     $this->explode ?? false,
            allowReserved: $this->allowReserved ?? false,
            extensions:  $this->extensions(),
        );
    }
}
