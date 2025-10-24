<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Components;

use On1kel\OAS\Builder\Bodies\RequestBody as RequestBodyBuilder;
use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Examples\Example as ExampleBuilder;
use On1kel\OAS\Builder\Headers\Header as HeaderBuilder;
use On1kel\OAS\Builder\Links\Link as LinkBuilder;
use On1kel\OAS\Builder\Parameters\Parameter as ParameterBuilder;
use On1kel\OAS\Builder\Paths\PathItem as PathItemBuilder;
use On1kel\OAS\Builder\Responses\Response as ResponseBuilder;
use On1kel\OAS\Builder\Schema\Schema as SchemaBuilder;
use On1kel\OAS\Builder\Security\SecurityScheme as SecuritySchemeBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\CallbackMap;
use On1kel\OAS\Core\Model\Collections\Map\ExampleMap;
use On1kel\OAS\Core\Model\Collections\Map\HeaderMap;
use On1kel\OAS\Core\Model\Collections\Map\LinkMap;
use On1kel\OAS\Core\Model\Collections\Map\ParameterMap;
use On1kel\OAS\Core\Model\Collections\Map\PathItemMap;
use On1kel\OAS\Core\Model\Collections\Map\RequestBodyMap;
use On1kel\OAS\Core\Model\Collections\Map\ResponseMap;
use On1kel\OAS\Core\Model\Collections\Map\SchemaMap;
use On1kel\OAS\Core\Model\Collections\Map\SecuritySchemeMap;
use On1kel\OAS\Core\Model\Reference;

/**
 * Components (builder) — упрощённый API:
 *  - на секцию ровно 1–2 метода: одиночный элемент и массовая загрузка массивом
 *  - допускаются только fluent-билдеры текущей секции или строка $ref ('#/components/...'),
 *    core-модели сюда НЕ принимаются
 */
final class Components implements BuildsCoreModel
{
    use UsesProfile;
    use HasExtensions;

    /** @var array<string, SchemaBuilder|string>|null */
    private ?array $schemas = null;
    /** @var array<string, ResponseBuilder|string>|null */
    private ?array $responses = null;
    /** @var array<string, ParameterBuilder|string>|null */
    private ?array $parameters = null;
    /** @var array<string, ExampleBuilder|string>|null */
    private ?array $examples = null;
    /** @var array<string, RequestBodyBuilder|string>|null */
    private ?array $requestBodies = null;
    /** @var array<string, HeaderBuilder|string>|null */
    private ?array $headers = null;
    /** @var array<string, SecuritySchemeBuilder|string>|null */
    private ?array $securitySchemes = null;
    /** @var array<string, LinkBuilder|string>|null */
    private ?array $links = null;
    /** @var array<string, PathItemBuilder|string>|null */
    private ?array $callbacks = null;
    /** @var array<string, PathItemBuilder|string>|null */
    private ?array $pathItems = null;

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

    // ── SCHEMAS ─────────────────────────────────────────────────────────────

    public function schema(string $name, SchemaBuilder $schema): self
    {
        $this->guard->assertAllowedKey('Components', 'schemas');

        return $this->put('schemas', $name, $schema);
    }

    /**
     * @param array<string, SchemaBuilder|string> $items name => builder|"$ref"
     */
    public function schemas(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'schemas');

        return $this->set('schemas', $items, SchemaBuilder::class);
    }

    // ── RESPONSES ───────────────────────────────────────────────────────────

    public function response(string $name, ResponseBuilder $response): self
    {
        $this->guard->assertAllowedKey('Components', 'responses');

        return $this->put('responses', $name, $response);
    }

    /**
     * @param array<string, ResponseBuilder|string> $items
     */
    public function responses(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'responses');

        return $this->set('responses', $items, ResponseBuilder::class);
    }

    // ── PARAMETERS ──────────────────────────────────────────────────────────

    public function parameter(string $name, ParameterBuilder $parameter): self
    {
        $this->guard->assertAllowedKey('Components', 'parameters');

        return $this->put('parameters', $name, $parameter);
    }

    /**
     * @param array<string, ParameterBuilder|string> $items
     */
    public function parameters(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'parameters');

        return $this->set('parameters', $items, ParameterBuilder::class);
    }

    // ── EXAMPLES ────────────────────────────────────────────────────────────

    public function example(string $name, ExampleBuilder $example): self
    {
        $this->guard->assertAllowedKey('Components', 'examples');

        return $this->put('examples', $name, $example);
    }

    /**
     * @param array<string, ExampleBuilder|string> $items
     */
    public function examples(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'examples');

        return $this->set('examples', $items, ExampleBuilder::class);
    }

    // ── REQUEST BODIES ─────────────────────────────────────────────────────

    public function requestBody(string $name, RequestBodyBuilder $body): self
    {
        $this->guard->assertAllowedKey('Components', 'requestBodies');

        return $this->put('requestBodies', $name, $body);
    }

    /**
     * @param array<string, RequestBodyBuilder|string> $items
     */
    public function requestBodies(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'requestBodies');

        return $this->set('requestBodies', $items, RequestBodyBuilder::class);
    }

    // ── HEADERS ────────────────────────────────────────────────────────────

    public function header(string $name, HeaderBuilder $header): self
    {
        $this->guard->assertAllowedKey('Components', 'headers');

        return $this->put('headers', $name, $header);
    }

    /**
     * @param array<string, HeaderBuilder|string> $items
     */
    public function headers(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'headers');

        return $this->set('headers', $items, HeaderBuilder::class);
    }

    // ── SECURITY SCHEMES ───────────────────────────────────────────────────

    public function securityScheme(string $name, SecuritySchemeBuilder $scheme): self
    {
        $this->guard->assertAllowedKey('Components', 'securitySchemes');

        return $this->put('securitySchemes', $name, $scheme);
    }

    /**
     * @param array<string, SecuritySchemeBuilder|string> $items
     */
    public function securitySchemes(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'securitySchemes');

        return $this->set('securitySchemes', $items, SecuritySchemeBuilder::class);
    }

    // ── LINKS ──────────────────────────────────────────────────────────────

    public function link(string $name, LinkBuilder $link): self
    {
        $this->guard->assertAllowedKey('Components', 'links');

        return $this->put('links', $name, $link);
    }

    /**
     * @param array<string, LinkBuilder|string> $items
     */
    public function links(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'links');

        return $this->set('links', $items, LinkBuilder::class);
    }

    // ── CALLBACKS ──────────────────────────────────────────────────────────

    public function callback(string $name, PathItemBuilder $pathItem): self
    {
        $this->guard->assertAllowedKey('Components', 'callbacks');

        return $this->put('callbacks', $name, $pathItem);
    }

    /**
     * @param array<string, PathItemBuilder|string> $items
     */
    public function callbacks(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'callbacks');

        return $this->set('callbacks', $items, PathItemBuilder::class);
    }

    // ── PATH ITEMS ─────────────────────────────────────────────────────────

    public function pathItem(string $name, PathItemBuilder $pathItem): self
    {
        $this->guard->assertAllowedKey('Components', 'pathItems');

        return $this->put('pathItems', $name, $pathItem);
    }

    /**
     * @param array<string, PathItemBuilder|string> $items
     */
    public function pathItems(array $items): self
    {
        $this->guard->assertAllowedKey('Components', 'pathItems');

        return $this->set('pathItems', $items, PathItemBuilder::class);
    }

    // ── BUILD ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Components
    {
        return new Core\Components(
            schemas:         Assembler::mapOrNull(SchemaMap::class, $this->convertMap($this->schemas)),
            responses:       Assembler::mapOrNull(ResponseMap::class, $this->convertMap($this->responses)),
            parameters:      Assembler::mapOrNull(ParameterMap::class, $this->convertMap($this->parameters)),
            examples:        Assembler::mapOrNull(ExampleMap::class, $this->convertMap($this->examples)),
            requestBodies:   Assembler::mapOrNull(RequestBodyMap::class, $this->convertMap($this->requestBodies)),
            headers:         Assembler::mapOrNull(HeaderMap::class, $this->convertMap($this->headers)),
            securitySchemes: Assembler::mapOrNull(SecuritySchemeMap::class, $this->convertMap($this->securitySchemes)),
            links:           Assembler::mapOrNull(LinkMap::class, $this->convertMap($this->links)),
            callbacks:       Assembler::mapOrNull(CallbackMap::class, $this->convertMap($this->callbacks)),
            pathItems:       Assembler::mapOrNull(PathItemMap::class, $this->convertMap($this->pathItems)),
            extensions:      $this->extensions(),
        );
    }

    // ── ВНУТРЕННЕЕ ─────────────────────────────────────────────────────────

    /**
     * @param string        $section
     * @param string        $name
     * @param object|string $value   fluent-builder текущей секции или строка $ref
     */
    private function put(string $section, string $name, object|string $value): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException("Components: empty name is not allowed for {$section}");
        }
        $x = clone $this;
        $x->{$section} ??= [];
        $this->assertValueType($section, $value);
        $x->{$section}[$name] = $value;

        return $x;
    }

    /**
     * @template T of object
     * @param string                  $section
     * @param array<string, T|string> $map
     * @param class-string<T>         $builderClass
     */
    private function set(string $section, array $map, string $builderClass): self
    {
        $x = clone $this;
        $x->{$section} = [];
        foreach ($map as $name => $value) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException("Components: invalid name for {$section}");
            }
            if (!is_string($value) && !($value instanceof $builderClass)) {
                $got = is_object($value) ? $value::class : gettype($value);
                throw new \InvalidArgumentException("Components: value for '{$section}.{$name}' must be {$builderClass} or \$ref string, got {$got}");
            }
            $x->{$section}[$name] = $value;
        }

        return $x;
    }

    /**
     * Превращает builder|string($ref) → core-модель|Reference.
     *
     * @param  array<string, object|string>|null                                                                                                                            $map
     * @return array<string, Core\Schema|Core\Response|Core\Parameter|Core\Example|Core\RequestBody|Core\Header|Core\SecurityScheme|Core\Link|Core\PathItem|Reference>|null
     */
    private function convertMap(?array $map): ?array
    {
        if ($map === null) {
            return null;
        }

        $out = [];
        foreach ($map as $name => $value) {
            if (is_string($value)) {
                $out[$name] = RefFactory::fromString($value);
                continue;
            }
            if (!method_exists($value, 'toModel')) {
                $got = $value::class;
                throw new \InvalidArgumentException("Components: unsupported object for '{$name}' — missing toModel(), got {$got}");
            }
            /** @var object $value */
            $core = $value->toModel();
            $out[$name] = $core;
        }

        return $out;
    }

    /**
     * Гарантирует, что в put() летят либо строки ($ref), либо корректный билдер указанной секции.
     */
    private function assertValueType(string $section, object|string $value): void
    {
        if (is_string($value)) {
            return;
        }

        $ok = match ($section) {
            'schemas'         => $value instanceof SchemaBuilder,
            'responses'       => $value instanceof ResponseBuilder,
            'parameters'      => $value instanceof ParameterBuilder,
            'examples'        => $value instanceof ExampleBuilder,
            'requestBodies'   => $value instanceof RequestBodyBuilder,
            'headers'         => $value instanceof HeaderBuilder,
            'securitySchemes' => $value instanceof SecuritySchemeBuilder,
            'links'           => $value instanceof LinkBuilder,
            'callbacks', 'pathItems' => $value instanceof PathItemBuilder,
            default           => false,
        };

        if (!$ok) {
            $need = match ($section) {
                'schemas'         => SchemaBuilder::class,
                'responses'       => ResponseBuilder::class,
                'parameters'      => ParameterBuilder::class,
                'examples'        => ExampleBuilder::class,
                'requestBodies'   => RequestBodyBuilder::class,
                'headers'         => HeaderBuilder::class,
                'securitySchemes' => SecuritySchemeBuilder::class,
                'links'           => LinkBuilder::class,
                'callbacks', 'pathItems' => PathItemBuilder::class,
                default           => 'builder',
            };
            $got = $value::class;
            throw new \InvalidArgumentException("Components: value for '{$section}' must be {$need} or \$ref string, got {$got}");
        }
    }
}
