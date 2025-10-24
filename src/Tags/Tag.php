<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Tags;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Enum\TagKind;

/**
 * Tag (builder) — OAS 3.1 / 3.2
 *
 * Публичный API без core-типов:
 *  - externalDocs(url, description)
 *  - kind(string|null) — в toModel() маппится в TagKind::from(...)
 */
final class Tag implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private string $name;

    private ?string $description = null;

    /** externalDocs без core */
    private ?string $externalDocsUrl = null;
    private ?string $externalDocsDescription = null;

    // 3.2 only
    private ?string $summary = null;
    private ?string $parent = null;

    /** Храним строку; в toModel() → TagKind::from(...) */
    private ?string $kindStr = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx, string $name)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->name    = $name;
    }

    public static function of(string $name, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name);
    }

    // ---- 3.1-поля ----

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('Tag', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    /** externalDocs без core: url + description */
    public function externalDocs(?string $url, ?string $description = null): self
    {
        $this->guard->assertAllowedKey('Tag', 'externalDocs');
        if ($url === '') {
            throw new \InvalidArgumentException('Tag: externalDocs url не может быть пустым.');
        }
        $x = clone $this;
        $x->externalDocsUrl = $url;
        $x->externalDocsDescription = $description;

        return $x;
    }

    // ---- 3.2-поля ----

    public function summary(?string $text): self
    {
        if ($text !== null) {
            $this->guard->assertAllowedKey('Tag', 'summary');
        }
        $x = clone $this;
        $x->summary = $text;

        return $x;
    }

    /** Родительский тег (имя) */
    public function parent(?string $name): self
    {
        if ($name !== null) {
            $this->guard->assertAllowedKey('Tag', 'parent');
            if ($name === '') {
                throw new \InvalidArgumentException('Tag: parent не может быть пустой строкой.');
            }
        }
        $x = clone $this;
        $x->parent = $name;

        return $x;
    }

    /**
     * Тип тега (3.2).
     * Принимаем строку, например: "tag" | "group" (в зависимости от того, что поддерживает ядро).
     * В toModel() попробуем конвертировать через TagKind::from($kind).
     */
    public function kind(?string $kind): self
    {
        if ($kind !== null) {
            $this->guard->assertAllowedKey('Tag', 'kind');
            if ($kind === '') {
                throw new \InvalidArgumentException('Tag: kind не может быть пустым.');
            }
        }
        $x = clone $this;
        $x->kindStr = $kind;

        return $x;
    }

    // ---- Build ----

    public function toModel(): Core\Tag
    {
        $extDocs = null;
        if ($this->externalDocsUrl !== null) {
            $extDocs = new Core\ExternalDocumentation(
                url: $this->externalDocsUrl,
                description: $this->externalDocsDescription,
                extensions: []
            );
        }

        $kindEnum = null;
        if ($this->kindStr !== null) {
            $kindEnum = TagKind::from($this->kindStr);
        }

        return new Core\Tag(
            name: $this->name,
            summary: $this->summary,
            description: $this->description,
            // 3.2
            externalDocs: $extDocs,
            parent: $this->parent,
            kind: $kindEnum,
            extensions: $this->extensions(),
        );
    }
}
