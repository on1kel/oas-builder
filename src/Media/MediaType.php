<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Media;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Schema\Schema as SchemaBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Errors\InvalidCombination;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\List\EncodingList;
use On1kel\OAS\Core\Model\Collections\Map\ExampleMap;

/**
 * Media Type (builder) — OAS 3.1 / 3.2
 *
 * Соответствует твоей core-модели:
 *  - schema: Schema|Reference|null
 *  - itemSchema: Schema|Reference|null          // 3.2
 *  - example: mixed
 *  - examples: ExampleMap|null                  // взаимоисключимо с example
 *  - prefixEncoding: EncodingList|null          // 3.2
 *  - itemEncoding: Encoding|null                // 3.2
 *
 * В билдере MediaType хранит свой MIME (ключ в content map).
 */
final class MediaType implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private string $mime;

    private Core\Schema|Core\Reference|null $schema = null;
    private Core\Schema|Core\Reference|null $itemSchema = null;       // 3.2

    private mixed $example = null;
    /** @var array<string, Core\Example|Core\Reference>|null */
    private ?array $examples = null;

    /** @var list<Core\Encoding>|null */
    private ?array $prefixEncoding = null;                             // 3.2
    private ?Core\Encoding $itemEncoding = null;                       // 3.2

    private FeatureGuard $guard;

    private function __construct(string $mime, ProfileContext $ctx)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->mime    = $mime;
    }

    public static function of(string $mime, ?ProfileContext $ctx = null): self
    {
        return new self($mime, $ctx ?? ProfileProvider::current());
    }

    public static function json(?ProfileContext $ctx = null): self
    {
        return new self('application/json', $ctx ?? ProfileProvider::current());
    }

    public function schema(SchemaBuilder|string $schemaRef): self
    {
        $this->guard->assertAllowedKey('MediaType', 'schema');
        $x = clone $this;
        $x->schema = \is_string($schemaRef)
            ? RefFactory::fromString($schemaRef)
            : $schemaRef->toModel();

        return $x;
    }

    // сахар
    public function schemaRef(string $ref): self
    {
        return $this->schema($ref);
    }

    /** Взаимоисключимо с examples. */
    public function example(mixed $value): self
    {
        $this->guard->assertAllowedKey('MediaType', 'example');
        if ($this->examples !== null) {
            throw InvalidCombination::because('MediaType: "example" и "examples" взаимоисключают друг друга.');
        }
        $x = clone $this;
        $x->example = $value;

        return $x;
    }

    /**
     * Примеры: map<string, Example|Reference>. Взаимоисключимо с example.
     * @param array<string, Core\Example|Core\Reference> $examples
     */
    public function examples(array $examples): self
    {
        $this->guard->assertAllowedKey('MediaType', 'examples');
        if ($this->example !== null) {
            throw InvalidCombination::because('MediaType: "example" и "examples" взаимоисключают друг друга.');
        }
        $x = clone $this;
        $x->examples = $examples ?: null;

        return $x;
    }

    /** 3.2: itemSchema для элементов массива/коллекции. */
    public function itemSchema(SchemaBuilder|null $schema): self
    {
        if ($schema !== null) {
            $this->guard->assertAllowedKey('MediaType', 'itemSchema');
        }
        $x = clone $this;
        $x->itemSchema = $schema?->toModel();

        return $x;
    }

    /** 3.2: список префиксных кодеков. */
    public function prefixEncoding(Encoding ...$encodings): self
    {
        if ($encodings !== []) {
            $this->guard->assertAllowedKey('MediaType', 'prefixEncoding');
        }
        $x = clone $this;
        $list = [];
        foreach ($encodings as $e) {
            $list[] = $e->toModel();
        }
        $x->prefixEncoding = $list ?: null;

        return $x;
    }

    /** 3.2: кодек для элемента. */
    public function itemEncoding(Encoding|null $encoding): self
    {
        if ($encoding !== null) {
            $this->guard->assertAllowedKey('MediaType', 'itemEncoding');
        }
        $x = clone $this;
        $x->itemEncoding = $encoding?->toModel();

        return $x;
    }

    /** MIME, под которым этот MediaType кладётся в content map. */
    public function mime(): string
    {
        return $this->mime;
    }

    public function toModel(): Core\MediaType
    {
        return new Core\MediaType(
            schema:         $this->schema,
            itemSchema:     $this->itemSchema, // 3.2
            example:        $this->example,
            examples:       Assembler::mapOrNull(ExampleMap::class, $this->examples),
            prefixEncoding: Assembler::listOrNull(EncodingList::class, $this->prefixEncoding),
            itemEncoding:   $this->itemEncoding, // 3.2
            extensions:     $this->extensions(),
        );
    }
}
