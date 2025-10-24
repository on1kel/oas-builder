<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Paths;

use On1kel\OAS\Builder\Bodies\RequestBody as RequestBodyBuilder;
use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Parameters\Parameter as ParameterBuilder;
use On1kel\OAS\Builder\Paths\PathItem as PathItemBuilder;
use On1kel\OAS\Builder\Responses\Responses as ResponsesBuilder;
use On1kel\OAS\Builder\Security\SecurityRequirement as SecurityRequirementBuilder;
use On1kel\OAS\Builder\Servers\Server as ServerBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\List\ParameterList;
use On1kel\OAS\Core\Model\Collections\List\SecurityRequirementList;
use On1kel\OAS\Core\Model\Collections\List\ServerList;
use On1kel\OAS\Core\Model\Collections\List\TagList;
use On1kel\OAS\Core\Model\Collections\Map\CallbackMap;

/**
 * Builder для Operation Object.
 *
 * ВНИМАНИЕ:
 *  - внутри билдера НЕТ полей типа Core\...
 *  - сюда нельзя положить готовые Core-модели напрямую
 *  - допускаются только билдеры и простые значения ($ref-строки и скаляры)
 *
 * Финальная Core\Operation создаётся только в toModel().
 */
final class Operation implements BuildsCoreModel
{
    use UsesProfile;
    use HasExtensions;

    /** @var string[]|null */
    private ?array $tags = null;

    private ?string $summary = null;
    private ?string $description = null;
    private ?Core\ExternalDocumentation $externalDocs = null; // допустимо, как в Schema: некоторые узлы мы не ребилдим
    private ?string $operationId = null;
    private bool $deprecated = false;

    /**
     * Параметры операции.
     *
     * Каждый параметр может быть:
     *  - билдер параметра (ParameterBuilder)
     *  - строка $ref ('#/components/parameters/FooId')
     *
     * @var list<ParameterBuilder|string>
     */
    private array $parameters = [];

    /**
     * RequestBody.
     *
     * Может быть:
     *  - билдер RequestBody
     *  - строка $ref ('#/components/requestBodies/CreateUserBody')
     *  - null (нет тела)
     *
     * @var RequestBodyBuilder|string|null
     */
    private RequestBodyBuilder|string|null $requestBody = null;

    /**
     * Обязательный блок responses в OpenAPI.
     *
     * Мы храним его как билдер, не как Core-модель.
     */
    private ?ResponsesBuilder $responses = null;

    /**
     * callbacks — карта { имя_коллбэка: PathItemBuilder|string }
     * где string — это $ref ('#/components/callbacks/PaymentHook')
     *
     * @var array<string, PathItemBuilder|string>
     */
    private array $callbacks = [];

    /**
     * Список требований безопасности.
     *
     * @var list<SecurityRequirementBuilder>
     */
    private array $security = [];

    /**
     * Список кастомных серверов для этой операции.
     *
     * @var list<ServerBuilder>
     */
    private array $servers = [];

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

    // ─────────────────────────────
    // Метаданные операции
    // ─────────────────────────────

    public function tags(string ...$names): self
    {
        $this->guard->assertAllowedKey('Operation', 'tags');
        $x = clone $this;
        $x->tags = $names ? \array_values($names) : null;

        return $x;
    }

    /** Вспомогательно, чтобы получать теги на фильтрации */
    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function summary(?string $text): self
    {
        $this->guard->assertAllowedKey('Operation', 'summary');
        $x = clone $this;
        $x->summary = $text;

        return $x;
    }

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('Operation', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function externalDocs(?Core\ExternalDocumentation $docs): self
    {
        // ExternalDocumentation — маленькая core-модель без билдера,
        // как и discriminator/xml в Schema. Это допустимо.
        $this->guard->assertAllowedKey('Operation', 'externalDocs');
        $x = clone $this;
        $x->externalDocs = $docs;

        return $x;
    }

    public function operationId(?string $id): self
    {
        $this->guard->assertAllowedKey('Operation', 'operationId');
        $x = clone $this;
        $x->operationId = $id;

        return $x;
    }

    public function deprecated(bool $flag = true): self
    {
        $this->guard->assertAllowedKey('Operation', 'deprecated');
        $x = clone $this;
        $x->deprecated = $flag;

        return $x;
    }

    // ─────────────────────────────
    // Параметры
    // ─────────────────────────────

    /**
     * @param ParameterBuilder|string $p
     */
    public function parameter(ParameterBuilder|string $p): self
    {
        $this->guard->assertAllowedKey('Operation', 'parameters');
        $x = clone $this;
        $x->parameters[] = $p;

        return $x;
    }

    /**
     * @param list<ParameterBuilder|string> $params
     */
    public function parameters(array $params): self
    {
        $this->guard->assertAllowedKey('Operation', 'parameters');
        $x = clone $this;
        $x->parameters = \array_values($params);

        return $x;
    }

    // ─────────────────────────────
    // RequestBody
    // ─────────────────────────────

    /**
     * @param RequestBodyBuilder|string|null $rb
     */
    public function requestBody(RequestBodyBuilder|string|null $rb): self
    {
        $this->guard->assertAllowedKey('Operation', 'requestBody');
        $x = clone $this;
        $x->requestBody = $rb;

        return $x;
    }

    // ─────────────────────────────
    // Responses
    // ─────────────────────────────

    public function responses(ResponsesBuilder $responses): self
    {
        $this->guard->assertAllowedKey('Operation', 'responses');
        $x = clone $this;
        $x->responses = $responses;

        return $x;
    }

    // ─────────────────────────────
    // Callbacks
    // ─────────────────────────────

    /**
     * Полностью переопределить callbacks.
     *
     * @param array<string, PathItemBuilder|string> $map
     */
    public function callbacks(array $map): self
    {
        $this->guard->assertAllowedKey('Operation', 'callbacks');
        $x = clone $this;
        $x->callbacks = $map;

        return $x;
    }

    /**
     * @param PathItemBuilder|string $item
     */
    public function putCallback(string $name, PathItemBuilder|string $item): self
    {
        $this->guard->assertAllowedKey('Operation', 'callbacks');
        $x = clone $this;
        $x->callbacks[$name] = $item;

        return $x;
    }

    // ─────────────────────────────
    // Security
    // ─────────────────────────────

    public function securityRequirement(SecurityRequirementBuilder $req): self
    {
        $this->guard->assertAllowedKey('Operation', 'security');
        $x = clone $this;
        $x->security[] = $req;

        return $x;
    }

    /**
     * @param list<SecurityRequirementBuilder> $reqs
     */
    public function security(array $reqs): self
    {
        $this->guard->assertAllowedKey('Operation', 'security');
        $x = clone $this;
        $x->security = \array_values($reqs);

        return $x;
    }

    // ─────────────────────────────
    // Servers
    // ─────────────────────────────

    public function server(ServerBuilder $server): self
    {
        $this->guard->assertAllowedKey('Operation', 'servers');
        $x = clone $this;
        $x->servers[] = $server;

        return $x;
    }

    /**
     * @param list<ServerBuilder> $servers
     */
    public function servers(array $servers): self
    {
        $this->guard->assertAllowedKey('Operation', 'servers');
        $x = clone $this;
        $x->servers = \array_values($servers);

        return $x;
    }

    // ─────────────────────────────
    // Сборка ядра
    // ─────────────────────────────

    public function toModel(): Core\Operation
    {
        if ($this->responses === null) {
            throw new \InvalidArgumentException('Operation: "responses" обязателен.');
        }

        // parameters → Core\Parameter|Core\Reference
        $coreParams = [];
        foreach ($this->parameters as $p) {
            if ($p instanceof ParameterBuilder) {
                $coreParams[] = $p->toModel();
            } else {
                // $p это string "$ref"
                $coreParams[] = RefFactory::fromString($p);
            }
        }

        // requestBody → Core\RequestBody|Core\Reference|null
        $coreRequestBody = null;
        if ($this->requestBody !== null) {
            if ($this->requestBody instanceof RequestBodyBuilder) {
                $coreRequestBody = $this->requestBody->toModel();
            } else {
                // string "$ref"
                $coreRequestBody = RefFactory::fromString($this->requestBody);
            }
        }

        // responses → Core\Responses
        $coreResponses = $this->responses->toModel();

        // callbacks → array<string, Core\PathItem|Core\Reference>
        $coreCallbacksAssoc = [];
        foreach ($this->callbacks as $name => $cb) {
            if ($cb instanceof PathItemBuilder) {
                $coreCallbacksAssoc[$name] = $cb->toModel();
            } else {
                // string "$ref"
                $coreCallbacksAssoc[$name] = RefFactory::fromString($cb);
            }
        }
        $coreCallbacks = $coreCallbacksAssoc
            ? Assembler::map(CallbackMap::class, $coreCallbacksAssoc)
            : null;

        // security → SecurityRequirementList
        $coreSecurity = [];
        foreach ($this->security as $secReqBuilder) {
            $coreSecurity[] = $secReqBuilder->toModel();
        }

        // servers → ServerList
        $coreServers = [];
        foreach ($this->servers as $srvBuilder) {
            $coreServers[] = $srvBuilder->toModel();
        }

        // tags → TagList
        $coreTags = Assembler::listOrNull(TagList::class, $this->tags ?? []);

        return new Core\Operation(
            tags: $coreTags,
            summary: $this->summary,
            description: $this->description,
            externalDocs: $this->externalDocs,
            operationId: $this->operationId,
            parameters: Assembler::listOrNull(ParameterList::class, $coreParams),
            requestBody: $coreRequestBody,
            responses: $coreResponses,
            callbacks: $coreCallbacks,
            deprecated: $this->deprecated,
            security: Assembler::listOrNull(SecurityRequirementList::class, $coreSecurity),
            servers: Assembler::listOrNull(ServerList::class, $coreServers),
            extensions: $this->extensions(),
        );
    }
}
