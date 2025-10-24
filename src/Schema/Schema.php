<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Schema;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Contracts\ExtensibleNode;
use On1kel\OAS\Builder\Support\Contracts\NamedNode;
use On1kel\OAS\Builder\Support\Errors\InvalidCombination;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\HasName;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\List\SchemaList;
use On1kel\OAS\Core\Model\Collections\Map\PatternSchemaMap;
use On1kel\OAS\Core\Model\Collections\Map\SchemaMap;

final class Schema implements BuildsCoreModel, NamedNode, ExtensibleNode
{
    use HasExtensions;
    use HasName;

    // если нужно положить "true"/"false" как валидатор всего/ничего
    private bool|array $raw = [];

    /** @var string|string[]|null */
    private string|array|null $type = null;

    private ?string $title = null;
    private ?string $description = null;
    private bool $hasDefault = false;
    private mixed $default = null;
    private ?string $format = null;

    /** @var list<mixed>|null */
    private ?array $enum = null;
    private mixed $const = null;

    /** @var array<string, Schema|string>|null */
    private ?array $properties = null;
    /** @var string[]|null */
    private ?array $required = null;
    /** @var array<string, Schema|string>|null */
    private ?array $patternProperties = null;

    /** @var Schema|string|bool|null */
    private Schema|string|bool|null $additionalProperties = null;
    /** @var Schema|string|bool|null */
    private Schema|string|bool|null $unevaluatedProperties = null;

    /** @var array<string, Schema|string>|null */
    private ?array $dependentSchemas = null;

    /** @var Schema|string|null */
    private Schema|string|null $items = null;
    /** @var list<Schema|string>|null */
    private ?array $prefixItems = null;

    /** @var list<Schema|string>|null */
    private ?array $allOf = null;
    /** @var list<Schema|string>|null */
    private ?array $anyOf = null;
    /** @var list<Schema|string>|null */
    private ?array $oneOf = null;
    /** @var Schema|string|null */
    private Schema|string|null $not = null;

    /** @var Schema|string|null */
    private Schema|string|null $if = null;
    /** @var Schema|string|null */
    private Schema|string|null $then = null;
    /** @var Schema|string|null */
    private Schema|string|null $else = null;

    private ?string $contentMediaType = null;
    private ?string $contentEncoding = null;
    /** @var Schema|string|null */
    private Schema|string|null $contentSchema = null;

    private ?bool $nullable = null;
    private ?bool $readOnly = null;
    private ?bool $writeOnly = null;
    private ?bool $deprecated = null;

    // OAS-дополнения, оставляем как есть (если нужны билдеры — можно добавить позже)
    private ?Core\Discriminator $discriminator = null;
    private ?Core\Xml $xml = null;
    private ?Core\ExternalDocumentation $externalDocs = null;

    private mixed $example = null;
    /** @var list<mixed>|null */
    private ?array $examples = null;

    /** @var array<string,mixed> */
    private array $extra = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // ── Фабрики по типам ───────────────────────────────────────────────────

    public static function object(?string $name = null): self
    {
        $x = new self();
        $x->type = ['object'];

        return $name ? $x->named($name) : $x;
    }
    public static function array(?string $name = null): self
    {
        $x = new self();
        $x->type = ['array'];

        return $name ? $x->named($name) : $x;
    }
    public static function string(?string $name = null): self
    {
        $x = new self();
        $x->type = ['string'];

        return $name ? $x->named($name) : $x;
    }
    public static function integer(?string $name = null): self
    {
        $x = new self();
        $x->type = ['integer'];

        return $name ? $x->named($name) : $x;
    }
    public static function number(?string $name = null): self
    {
        $x = new self();
        $x->type = ['number'];

        return $name ? $x->named($name) : $x;
    }
    public static function boolean(?string $name = null): self
    {
        $x = new self();
        $x->type = ['boolean'];

        return $name ? $x->named($name) : $x;
    }

    /** Удобный helper для $ref (если надо прямо Reference в другом месте) */
    public static function ref(string $ref): Core\Reference
    {
        return RefFactory::fromString($ref);
    }

    // ── Общие поля JSON Schema ─────────────────────────────────────────────

    public function title(string $value): self
    {
        $y = clone $this;
        $y->title = $value;

        return $y;
    }
    public function type(string|array|null $value): self
    {
        $y = clone $this;
        $y->type = $value;

        return $y;
    }
    public function description(?string $value): self
    {
        $y = clone $this;
        $y->description = $value;

        return $y;
    }
    public function format(?string $value): self
    {
        $y = clone $this;
        $y->format = $value;

        return $y;
    }
    public function default(mixed $value): self
    {
        $y = clone $this;
        $y->hasDefault = true;
        $y->default = $value;

        return $y;
    }
    /** @param list<mixed> $values */
    public function enum(mixed ...$values): self
    {
        $y = clone $this;
        $y->enum = $values ?: null;

        return $y;
    }
    public function const(mixed $value): self
    {
        $y = clone $this;
        $y->const = $value;

        return $y;
    }

    /** @param array<string,mixed> $keywords */
    public function extra(array $keywords): self
    {
        $y = clone $this;
        $y->extra = $keywords;

        return $y;
    }
    public function raw(bool|array $raw): self
    {
        $y = clone $this;
        $y->raw = $raw;

        return $y;
    }

    // ── Object keywords ────────────────────────────────────────────────────

    /** properties из набора именованных Schema-билдеров */
    public function properties(Schema ...$props): self
    {
        $y = clone $this;
        $map = [];
        foreach ($props as $p) {
            $n = $p->name();
            if (!$n) {
                throw InvalidCombination::because('Schema::properties(): свойство без имени. Используй ->named().');
            }
            $map[$n] = $p;
        }
        $y->properties = $map;

        return $y;
    }

    /**
     * properties именованной картой (Schema или $ref-строка)
     * @param array<string, Schema|string> $map
     */
    public function propertiesNamed(array $map): self
    {
        $y = clone $this;
        $out = [];
        foreach ($map as $name => $node) {
            if (!is_string($name) || $name === '') {
                throw InvalidCombination::because('Schema::propertiesNamed(): пустое имя свойства.');
            }
            if (!is_string($node) && !$node instanceof Schema) {
                $got = is_object($node) ? $node::class : gettype($node);
                throw InvalidCombination::because("Schema::propertiesNamed(): ожидался Schema или \$ref-строка, получено {$got}.");
            }
            $out[$name] = $node;
        }
        $y->properties = $out;

        return $y;
    }

    /** @param string[] $names */
    public function required(string ...$names): self
    {
        $y = clone $this;
        $y->required = $names ?: null;
        if ($y->required && $y->properties) {
            foreach ($y->required as $n) {
                if (!\array_key_exists($n, $y->properties)) {
                    throw InvalidCombination::because("Schema::required(): '{$n}' отсутствует в properties.");
                }
            }
        }

        return $y;
    }

    /**
     * @param array<string, Schema|string> $map
     */
    public function patternProperties(array $map): self
    {
        $y = clone $this;
        $out = [];
        foreach ($map as $pattern => $node) {
            if (!is_string($pattern) || $pattern === '') {
                throw InvalidCombination::because('Schema::patternProperties(): пустой ключ-паттерн.');
            }
            if (!is_string($node) && !$node instanceof Schema) {
                $got = is_object($node) ? $node::class : gettype($node);
                throw InvalidCombination::because("Schema::patternProperties(): ожидался Schema или \$ref-строка, получено {$got}.");
            }
            $out[$pattern] = $node;
        }
        $y->patternProperties = $out;

        return $y;
    }

    public function additionalProperties(bool|Schema|string|null $v): self
    {
        $y = clone $this;
        if (!is_null($v) && !is_bool($v) && !is_string($v) && !$v instanceof Schema) {
            $got = is_object($v) ? $v::class : gettype($v);
            throw InvalidCombination::because("Schema::additionalProperties(): bool|Schema|\$ref|null, получено {$got}.");
        }
        $y->additionalProperties = $v;

        return $y;
    }

    public function unevaluatedProperties(bool|Schema|string|null $v): self
    {
        $y = clone $this;
        if (!is_null($v) && !is_bool($v) && !is_string($v) && !$v instanceof Schema) {
            $got = is_object($v) ? $v::class : gettype($v);
            throw InvalidCombination::because("Schema::unevaluatedProperties(): bool|Schema|\$ref|null, получено {$got}.");
        }
        $y->unevaluatedProperties = $v;

        return $y;
    }

    /**
     * @param array<string, Schema|string> $map
     */
    public function dependentSchemas(array $map): self
    {
        $y = clone $this;
        $out = [];
        foreach ($map as $name => $node) {
            if (!is_string($name) || $name === '') {
                throw InvalidCombination::because('Schema::dependentSchemas(): пустой ключ.');
            }
            if (!is_string($node) && !$node instanceof Schema) {
                $got = is_object($node) ? $node::class : gettype($node);
                throw InvalidCombination::because("Schema::dependentSchemas(): ожидался Schema или \$ref-строка, получено {$got}.");
            }
            $out[$name] = $node;
        }
        $y->dependentSchemas = $out;

        return $y;
    }

    // ── Array keywords ─────────────────────────────────────────────────────

    public function items(Schema|string $item): self
    {
        $y = clone $this;
        $y->items = $item;

        return $y;
    }

    public function prefixItems(Schema|string ...$schemas): self
    {
        $y = clone $this;
        $y->prefixItems = $schemas ?: null;

        return $y;
    }

    // ── Compositions ───────────────────────────────────────────────────────

    public function allOf(Schema|string ...$schemas): self
    {
        $y = clone $this;
        $y->allOf = $schemas ?: null;

        return $y;
    }

    public function anyOf(Schema|string ...$schemas): self
    {
        $y = clone $this;
        $y->anyOf = $schemas ?: null;

        return $y;
    }

    public function oneOf(Schema|string ...$schemas): self
    {
        $y = clone $this;
        $y->oneOf = $schemas ?: null;

        return $y;
    }

    public function not(Schema|string $schema): self
    {
        $y = clone $this;
        $y->not = $schema;

        return $y;
    }

    // ── Conditionals ───────────────────────────────────────────────────────

    public function if(Schema|string $schema): self
    {
        $y = clone $this;
        $y->if = $schema;

        return $y;
    }
    public function then(Schema|string $schema): self
    {
        $y = clone $this;
        $y->then = $schema;

        return $y;
    }
    public function else(Schema|string $schema): self
    {
        $y = clone $this;
        $y->else = $schema;

        return $y;
    }

    // ── Content keywords ───────────────────────────────────────────────────

    public function contentMediaType(?string $v): self
    {
        $y = clone $this;
        $y->contentMediaType = $v;

        return $y;
    }
    public function contentEncoding(?string $v): self
    {
        $y = clone $this;
        $y->contentEncoding = $v;

        return $y;
    }
    public function contentSchema(Schema|string|null $v): self
    {
        $y = clone $this;
        $y->contentSchema = $v;

        return $y;
    }

    // ── OAS add-ons ────────────────────────────────────────────────────────

    public function nullable(bool $flag = true): self
    {
        $y = clone $this;
        $y->nullable = $flag;

        return $y;
    }
    public function readOnly(bool $flag = true): self
    {
        $y = clone $this;
        $y->readOnly = $flag;

        return $y;
    }
    public function writeOnly(bool $flag = true): self
    {
        $y = clone $this;
        $y->writeOnly = $flag;

        return $y;
    }
    public function deprecated(bool $flag = true): self
    {
        $y = clone $this;
        $y->deprecated = $flag;

        return $y;
    }

    // оставляем core-типизацию для этих трёх, чтобы не гадать их билдеры
    public function discriminator(?Core\Discriminator $d): self
    {
        $y = clone $this;
        $y->discriminator = $d;

        return $y;
    }
    public function xml(?Core\Xml $xml): self
    {
        $y = clone $this;
        $y->xml = $xml;

        return $y;
    }
    public function externalDocs(?Core\ExternalDocumentation $docs): self
    {
        $y = clone $this;
        $y->externalDocs = $docs;

        return $y;
    }

    public function example(mixed $v): self
    {
        $y = clone $this;
        $y->example = $v;

        return $y;
    }
    /** @param list<mixed> $values */
    public function examples(mixed ...$values): self
    {
        $y = clone $this;
        $y->examples = $values ?: null;

        return $y;
    }

    // ── СИНТАКСИЧЕСКИЙ САХАР (частые ключи) ───────────────────────────────

    // string-keywords
    public function minLength(int $n): self
    {
        return $this->withExtra('minLength', $n);
    }
    public function maxLength(int $n): self
    {
        return $this->withExtra('maxLength', $n);
    }
    public function pattern(string $re): self
    {
        return $this->withExtra('pattern', $re);
    }

    // number/integer-keywords
    public function minimum(int|float $n): self
    {
        return $this->withExtra('minimum', $n);
    }
    public function exclusiveMinimum(int|float $n): self
    {
        return $this->withExtra('exclusiveMinimum', $n);
    }
    public function maximum(int|float $n): self
    {
        return $this->withExtra('maximum', $n);
    }
    public function exclusiveMaximum(int|float $n): self
    {
        return $this->withExtra('exclusiveMaximum', $n);
    }
    public function multipleOf(int|float $n): self
    {
        return $this->withExtra('multipleOf', $n);
    }

    // array-keywords
    public function minItems(int $n): self
    {
        return $this->withExtra('minItems', $n);
    }
    public function maxItems(int $n): self
    {
        return $this->withExtra('maxItems', $n);
    }
    public function uniqueItems(bool $flag = true): self
    {
        return $this->withExtra('uniqueItems', $flag);
    }

    // object-keywords
    public function minProperties(int $n): self
    {
        return $this->withExtra('minProperties', $n);
    }
    public function maxProperties(int $n): self
    {
        return $this->withExtra('maxProperties', $n);
    }

    // распространённые форматы
    public function asUUID(): self
    {
        return $this->format('uuid');
    }
    public function asDateTime(): self
    {
        return $this->format('date-time');
    }
    public function asEmail(): self
    {
        return $this->format('email');
    }
    public function asURI(): self
    {
        return $this->format('uri');
    }

    // enum удобнее массивом
    /** @param list<mixed> $values */
    public function enumOf(array $values): self
    {
        $y = clone $this;
        $y->enum = $values ?: null;

        return $y;
    }

    private function withExtra(string $k, mixed $v): self
    {
        $y = clone $this;
        $y->extra[$k] = $v;

        return $y;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Schema
    {
        $extra = $this->extra;
        if ($this->title !== null) {
            $extra['title'] = $this->title;
        }
        if ($this->description !== null) {
            $extra['description'] = $this->description;
        }
        if ($this->format !== null) {
            $extra['format'] = $this->format;
        }
        if ($this->hasDefault) {
            $extra['default'] = $this->default;
        }

        // helpers: билдер|string -> core
        $conv = fn (Schema|string|null $n) => is_null($n) ? null : (is_string($n) ? RefFactory::fromString($n) : $n->toModel());
        $convList = function (?array $list): ?array {
            if ($list === null) {
                return null;
            }
            $out = [];
            foreach ($list as $n) {
                $out[] = is_string($n) ? RefFactory::fromString($n) : $n->toModel();
            }

            return $out;
        };
        $convMap = function (?array $map): ?array {
            if ($map === null) {
                return null;
            }
            $out = [];
            foreach ($map as $k => $n) {
                $out[$k] = is_string($n) ? RefFactory::fromString($n) : $n->toModel();
            }

            return $out;
        };
        $convBoolSchema = function (Schema|string|bool|null $v) use ($conv) {
            if ($v === null || is_bool($v)) {
                return $v;
            }

            return $conv($v);
        };

        $props    = ($m = $convMap($this->properties)) ? Assembler::map(SchemaMap::class, $m) : null;
        $patProp  = ($m = $convMap($this->patternProperties)) ? Assembler::map(PatternSchemaMap::class, $m) : null;
        $dep      = ($m = $convMap($this->dependentSchemas)) ? Assembler::map(SchemaMap::class, $m) : null;

        $allOf    = ($l = $convList($this->allOf)) ? Assembler::list(SchemaList::class, $l) : null;
        $anyOf    = ($l = $convList($this->anyOf)) ? Assembler::list(SchemaList::class, $l) : null;
        $oneOf    = ($l = $convList($this->oneOf)) ? Assembler::list(SchemaList::class, $l) : null;
        $prefIt   = ($l = $convList($this->prefixItems)) ? Assembler::list(SchemaList::class, $l) : null;

        $required = $this->required ?? [];
        $enum     = $this->enum ?? [];
        $examples = $this->examples ?? [];

        return new Core\Schema(
            raw: $this->raw,
            type: $this->type,
            enum: $enum,
            const: $this->const,
            allOf: $allOf,
            anyOf: $anyOf,
            oneOf: $oneOf,
            not: $conv($this->not),
            items: $conv($this->items),
            prefixItems: $prefIt,
            properties: $props,
            patternProperties: $patProp,
            additionalProperties: $convBoolSchema($this->additionalProperties),
            unevaluatedProperties: $convBoolSchema($this->unevaluatedProperties),
            dependentSchemas: $dep,
            required: $required,
            if: $conv($this->if),
            then: $conv($this->then),
            else: $conv($this->else),
            contentMediaType: $this->contentMediaType,
            contentEncoding: $this->contentEncoding,
            contentSchema: $conv($this->contentSchema),
            nullable: $this->nullable,
            readOnly: $this->readOnly,
            writeOnly: $this->writeOnly,
            deprecated: $this->deprecated,
            discriminator: $this->discriminator,
            xml: $this->xml,
            externalDocs: $this->externalDocs,
            example: $this->example,
            examples: $examples,
            extraKeywords: $extra,
            extensions: $this->extensions(),
        );
    }
}
