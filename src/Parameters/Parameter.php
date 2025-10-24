<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Parameters;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Examples\Example as ExampleBuilder;
use On1kel\OAS\Builder\Media\MediaType as MediaTypeBuilder;
use On1kel\OAS\Builder\Schema\Schema as SchemaBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\ExampleMap;
use On1kel\OAS\Core\Model\Collections\Map\MediaTypeMap;
use On1kel\OAS\Core\Model\Enum\ParameterIn;

/**
 * Parameter (builder) — OAS 3.1 / 3.2
 *
 * Без приёма core-моделей: только билдеры и $ref-строки.
 * Инварианты:
 *  - schema/example(s) взаимоисключают content
 *  - example XOR examples
 */
final class Parameter implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private string $name;
    private ParameterIn $in; // path|query|header|cookie

    private ?string $description = null;
    private ?bool $required = null;
    private bool $deprecated = false;
    private ?bool $allowEmptyValue = null;

    private ?string $style = null;
    private ?bool $explode = null;
    private ?bool $allowReserved = null;

    /** @var SchemaBuilder|string|null */
    private SchemaBuilder|string|null $schema = null;

    private mixed $example = null;

    /** @var array<string, ExampleBuilder|string>|null */
    private ?array $examples = null;

    /** @var array<string, MediaTypeBuilder>|null */
    private ?array $content = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx, string $name, ParameterIn $in)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->name    = $name;
        $this->in      = $in;
    }

    // --------- Фабрики ---------

    public static function of(string $name, ParameterIn $in, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name, $in);
    }

    public static function query(string $name, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name, ParameterIn::Query);
    }

    public static function path(string $name, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name, ParameterIn::Path);
    }

    public static function header(string $name, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name, ParameterIn::Header);
    }

    public static function cookie(string $name, ?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current(), $name, ParameterIn::Cookie);
    }

    // --------- Атрибуты ---------

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('Parameter', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function required(bool $flag = true): self
    {
        $this->guard->assertAllowedKey('Parameter', 'required');
        $x = clone $this;
        $x->required = $flag;

        return $x;
    }

    public function deprecated(bool $flag = true): self
    {
        $this->guard->assertAllowedKey('Parameter', 'deprecated');
        $x = clone $this;
        $x->deprecated = $flag;

        return $x;
    }

    public function allowEmptyValue(?bool $flag): self
    {
        $this->guard->assertAllowedKey('Parameter', 'allowEmptyValue');
        $x = clone $this;
        $x->allowEmptyValue = $flag;

        return $x;
    }

    public function style(?string $style): self
    {
        $this->guard->assertAllowedKey('Parameter', 'style');
        $x = clone $this;
        $x->style = $style;

        return $x;
    }

    public function explode(?bool $flag): self
    {
        $this->guard->assertAllowedKey('Parameter', 'explode');
        $x = clone $this;
        $x->explode = $flag;

        return $x;
    }

    public function allowReserved(?bool $flag): self
    {
        $this->guard->assertAllowedKey('Parameter', 'allowReserved');
        $x = clone $this;
        $x->allowReserved = $flag;

        return $x;
    }

    // --------- Содержание ---------

    /**
     * Schema как билдер или $ref-строка.
     * Взаимоисключимо с content.
     */
    public function schema(SchemaBuilder|string $schema): self
    {
        $this->guard->assertAllowedKey('Parameter', 'schema');
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Parameter: schema/example(s) взаимоисключают content.');
        }
        $x = clone $this;
        $x->schema = $schema;

        return $x;
    }

    /** Взаимоисключимо с examples и content. */
    public function example(mixed $value): self
    {
        $this->guard->assertAllowedKey('Parameter', 'example');
        if ($this->examples !== null) {
            throw new \InvalidArgumentException('Parameter: "example" и "examples" взаимоисключаемы.');
        }
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Parameter: schema/example(s) взаимоисключают content.');
        }
        $x = clone $this;
        $x->example = $value;

        return $x;
    }

    /**
     * Примеры: name => ExampleBuilder|$ref-строка.
     * Взаимоисключимо с example и content.
     *
     * @param array<string, ExampleBuilder|string> $examples
     */
    public function examples(array $examples): self
    {
        $this->guard->assertAllowedKey('Parameter', 'examples');
        if ($this->example !== null) {
            throw new \InvalidArgumentException('Parameter: "example" и "examples" взаимоисключаемы.');
        }
        if ($this->content !== null) {
            throw new \InvalidArgumentException('Parameter: schema/example(s) взаимоисключают content.');
        }
        foreach ($examples as $name => $ex) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException('Parameter: examples — ключи должны быть непустыми строками.');
            }
            if (!is_string($ex) && !($ex instanceof ExampleBuilder)) {
                $got = is_object($ex) ? $ex::class : gettype($ex);
                throw new \InvalidArgumentException("Parameter: examples['{$name}'] — нужен ExampleBuilder или \$ref-строка, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->examples = $examples ?: null;

        return $x;
    }

    /**
     * content: добавление одного или нескольких MediaTypeBuilder.
     * Взаимоисключимо со schema/example(s).
     */
    public function content(MediaTypeBuilder ...$media): self
    {
        $this->guard->assertAllowedKey('Parameter', 'content');
        if ($this->schema !== null || $this->example !== null || $this->examples !== null) {
            throw new \InvalidArgumentException('Parameter: content взаимоисключает schema/example(s).');
        }
        $x = clone $this;
        $map = $x->content ?? [];
        foreach ($media as $m) {
            $map[$m->mime()] = $m;
        }
        $x->content = $map ?: null;

        return $x;
    }

    /**
     * Прямая установка content картой MIME => MediaTypeBuilder.
     * Взаимоисключимо со schema/example(s).
     *
     * @param array<string, MediaTypeBuilder> $byMime
     */
    public function contentMap(array $byMime): self
    {
        $this->guard->assertAllowedKey('Parameter', 'content');
        if ($this->schema !== null || $this->example !== null || $this->examples !== null) {
            throw new \InvalidArgumentException('Parameter: content взаимоисключает schema/example(s).');
        }
        foreach ($byMime as $mime => $mt) {
            if (!is_string($mime) || $mime === '' || !($mt instanceof MediaTypeBuilder)) {
                $got = is_object($mt) ? $mt::class : gettype($mt);
                throw new \InvalidArgumentException("Parameter: content['{$mime}'] — нужен MediaTypeBuilder, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->content = $byMime ?: null;

        return $x;
    }

    // --------- Build ---------

    public function toModel(): Core\Parameter
    {
        // schema → Core\Schema|Core\Reference|null
        $schemaCore = null;
        if ($this->schema !== null) {
            $schemaCore = is_string($this->schema)
                ? RefFactory::fromString($this->schema)
                : $this->schema->toModel();
        }

        // examples → array<string, Core\Example|Core\Reference>|null
        $examplesCore = null;
        if ($this->examples !== null) {
            $examplesCore = [];
            foreach ($this->examples as $name => $ex) {
                $examplesCore[$name] = is_string($ex)
                    ? RefFactory::fromString($ex)
                    : $ex->toModel();
            }
        }

        // content → MIME => Core\MediaType
        $contentCore = null;
        if ($this->content !== null) {
            $contentCore = [];
            foreach ($this->content as $mime => $mtBuilder) {
                $contentCore[$mime] = $mtBuilder->toModel();
            }
        }

        return new Core\Parameter(
            name:            $this->name,
            in:              $this->in,
            description:     $this->description,
            required:        $this->required,
            deprecated:      $this->deprecated,
            allowEmptyValue: $this->allowEmptyValue,
            style:           $this->style,
            explode:         $this->explode,
            allowReserved:   $this->allowReserved,
            schema:          $schemaCore,
            example:         $this->example,
            examples:        Assembler::mapOrNull(ExampleMap::class, $examplesCore),
            content:         Assembler::mapOrNull(MediaTypeMap::class, $contentCore),
            extensions:      $this->extensions(),
        );
    }
}
