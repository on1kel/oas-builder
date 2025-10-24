<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Headers;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Examples\Example as ExampleBuilder;
use On1kel\OAS\Builder\Media\MediaType as MediaTypeBuilder;
use On1kel\OAS\Builder\Schema\Schema as SchemaBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\ExampleMap;
use On1kel\OAS\Core\Model\Collections\Map\MediaTypeMap;
use On1kel\OAS\Core\Model\Enum\Style;

/**
 * Header (builder) — строго по Core\Model\Header, без приёма core-объектов.
 *
 * Поля:
 *  - description?: string
 *  - required?: bool (по умолчанию false)
 *  - deprecated?: bool (по умолчанию false)
 *  - style?: Style (если задан — только Style::Simple)
 *  - explode?: bool|null (для headers по умолчанию false)
 *  - schema?: SchemaBuilder|string ($ref)
 *  - example?: mixed  (XOR examples)
 *  - examples?: array<string, ExampleBuilder|string> (XOR example)
 *  - content?: array<string, MediaTypeBuilder> (XOR schema/example(s); строго 1 элемент)
 *  - x-* extensions
 */
final class Header implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $description = null;
    private ?bool $required = null;
    private ?bool $deprecated = null;

    private ?Style $style = null;   // если задан — только Style::Simple
    private ?bool $explode = null;  // по умолчанию false

    /** @var SchemaBuilder|string|null */
    private SchemaBuilder|string|null $schema = null;

    private mixed $example = null;

    /** @var array<string, ExampleBuilder|string>|null */
    private ?array $examples = null;

    /** @var array<string, MediaTypeBuilder>|null */
    private ?array $content = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // ── Метаданные ──────────────────────────────────────────────────────────

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function required(bool $flag = true): self
    {
        $x = clone $this;
        $x->required = $flag;

        return $x;
    }

    public function deprecated(bool $flag = true): self
    {
        $x = clone $this;
        $x->deprecated = $flag;

        return $x;
    }

    /**
     * Только Style::Simple допустим для Header.
     */
    public function style(?Style $style): self
    {
        if ($style !== null && $style !== Style::Simple) {
            throw new \InvalidArgumentException('Header: допустим только style=Style::Simple.');
        }
        $x = clone $this;
        $x->style = $style;

        return $x;
    }

    public function explode(?bool $flag): self
    {
        $x = clone $this;
        $x->explode = $flag;

        return $x;
    }

    // ── Содержимое ─────────────────────────────────────────────────────────

    /**
     * Schema как билдер или $ref-строка.
     * Взаимоисключимо с content.
     *
     * @param SchemaBuilder|string $schema
     */
    public function schema(SchemaBuilder|string $schema): self
    {
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Header: schema/example(s) взаимоисключают content.');
        }
        $x = clone $this;
        $x->schema = $schema;

        return $x;
    }

    /** Взаимоисключимо с examples и content. */
    public function example(mixed $value): self
    {
        if ($this->examples !== null) {
            throw new \InvalidArgumentException('Header: "example" и "examples" взаимоисключаемы.');
        }
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Header: schema/example(s) взаимоисключают content.');
        }
        $x = clone $this;
        $x->example = $value;

        return $x;
    }

    /**
     * Map примеров: name => ExampleBuilder|$ref-строка.
     * Взаимоисключимо с example и content.
     *
     * @param array<string, ExampleBuilder|string> $examples
     */
    public function examples(array $examples): self
    {
        if ($this->example !== null) {
            throw new \InvalidArgumentException('Header: "example" и "examples" взаимоисключаемы.');
        }
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Header: schema/example(s) взаимоисключают content.');
        }
        foreach ($examples as $name => $ex) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException('Header: examples: ключи должны быть непустыми строками.');
            }
            if (!is_string($ex) && !($ex instanceof ExampleBuilder)) {
                $got = is_object($ex) ? $ex::class : gettype($ex);
                throw new \InvalidArgumentException("Header: examples['{$name}'] должен быть ExampleBuilder или \$ref-строкой, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->examples = $examples ?: null;

        return $x;
    }

    /**
     * Установить content из билдера — будет ровно один MIME (внутри берётся из $media->mime()).
     * Взаимоисключимо со schema/example(s).
     */
    public function content(MediaTypeBuilder $media): self
    {
        if ($this->schema !== null || $this->example !== null || $this->examples !== null) {
            throw new \InvalidArgumentException('Header: content взаимоисключает schema/example(s).');
        }
        $x = clone $this;
        $x->content = [$media->mime() => $media];

        return $x;
    }

    /**
     * Прямая установка content: MIME => MediaTypeBuilder (ровно один элемент).
     * Взаимоисключимо со schema/example(s).
     *
     * @param array<string, MediaTypeBuilder> $byMime
     */
    public function contentMap(array $byMime): self
    {
        if ($this->schema !== null || $this->example !== null || $this->examples !== null) {
            throw new \InvalidArgumentException('Header: content взаимоисключает schema/example(s).');
        }
        if (\count($byMime) !== 1) {
            throw new \InvalidArgumentException('Header: content должен содержать ровно один media type.');
        }
        foreach ($byMime as $mime => $mt) {
            if (!is_string($mime) || $mime === '' || !($mt instanceof MediaTypeBuilder)) {
                $got = is_object($mt) ? $mt::class : gettype($mt);
                throw new \InvalidArgumentException("Header: content['{$mime}'] должен быть MediaTypeBuilder, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->content = $byMime;

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Header
    {
        // schema: SchemaBuilder|string($ref) → Core\Schema|Core\Reference|null
        $schemaCore = null;
        if ($this->schema !== null) {
            $schemaCore = is_string($this->schema)
                ? RefFactory::fromString($this->schema)
                : $this->schema->toModel();
        }

        // examples: convert to array<string, Core\Example|Core\Reference>
        $examplesCore = null;
        if ($this->examples !== null) {
            $examplesCore = [];
            foreach ($this->examples as $name => $ex) {
                $examplesCore[$name] = is_string($ex)
                    ? RefFactory::fromString($ex)
                    : $ex->toModel();
            }
        }

        // content: MIME => MediaTypeBuilder → MIME => Core\MediaType (ровно 1)
        $contentCore = null;
        if ($this->content !== null) {
            $contentCore = [];
            foreach ($this->content as $mime => $mtBuilder) {
                $contentCore[$mime] = $mtBuilder->toModel();
            }
        }

        return new Core\Header(
            description: $this->description,
            required:    $this->required ?? false,
            deprecated:  $this->deprecated ?? false,
            style:       $this->style,
            explode:     $this->explode,
            schema:      $schemaCore,
            example:     $this->example,
            examples:    Assembler::mapOrNull(ExampleMap::class, $examplesCore),
            content:     Assembler::mapOrNull(MediaTypeMap::class, $contentCore),
            extensions:  $this->extensions(),
        );
    }
}
