<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Bodies;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\Media\MediaType;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\MediaTypeMap;

/**
 * Request Body (builder) — OAS 3.1 / 3.2
 *
 * Core\RequestBody:
 *  - description?: string|null
 *  - content: MediaTypeMap (обязателен, не пустой)
 *  - required?: bool
 *  - extensions?: array
 */
final class RequestBody implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private ?string $description = null;
    private bool $required = false;

    /** @var array<string, Core\MediaType> */
    private array $content = [];

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

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('RequestBody', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function required(bool $flag = true): self
    {
        $this->guard->assertAllowedKey('RequestBody', 'required');
        $x = clone $this;
        $x->required = $flag;

        return $x;
    }

    /**
     * Добавить media-типы. Каждый билдер знает свой MIME (->mime()).
     */
    public function content(MediaType ...$media): self
    {
        $this->guard->assertAllowedKey('RequestBody', 'content');
        $x = clone $this;
        foreach ($media as $m) {
            $x->content[$m->mime()] = $m->toModel();
        }

        return $x;
    }

    public function toModel(): Core\RequestBody
    {
        return new Core\RequestBody(
            description: $this->description,
            content:     Assembler::map(MediaTypeMap::class, $this->content),
            required:    $this->required,
            extensions:  $this->extensions(),
        );
    }
}
