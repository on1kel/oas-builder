<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder;

use On1kel\OAS\Builder\Components\Components as ComponentsBuilder;
use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Paths\PathItem as PathItemBuilder;
use On1kel\OAS\Builder\Paths\Paths as PathsBuilder;
use On1kel\OAS\Builder\Security\SecurityRequirement as SecurityRequirementBuilder;
use On1kel\OAS\Builder\Servers\Server as ServerBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Contracts\ProfileAware;
use On1kel\OAS\Builder\Support\Errors\RequiredMissing;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Builder\Tags\Tag as TagBuilder;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\List\SecurityRequirementList;
use On1kel\OAS\Core\Model\Collections\List\ServerList;
use On1kel\OAS\Core\Model\Collections\List\TagList;
use On1kel\OAS\Core\Model\Collections\Map\WebhookMap;

/**
 * OpenAPI Document (builder) — корневой узел OAS 3.1 / 3.2.
 *
 * Публичный API: только билдеры и скаляры. Никаких Core-типа в сигнатурах.
 * Конвертация в core — строго в toModel().
 */
final class OpenApi implements BuildsCoreModel, ProfileAware
{
    use UsesProfile;
    use HasExtensions;

    private ?string $openapi = null;                 // "3.1.0" | "3.2.0"
    private ?Info $info = null;                      // билдер

    private ?string $jsonSchemaDialect = null;       // 3.1+
    private ?string $self = null;                    // 3.2+

    private ?PathsBuilder $paths = null;             // билдер
    private ?ComponentsBuilder $components = null;   // билдер

    /** @var array<string, PathItemBuilder|string>|null */
    private ?array $webhooks = null;                 // name => PathItem|$ref

    /** @var list<ServerBuilder>|null */
    private ?array $servers = null;

    /** @var list<SecurityRequirementBuilder>|null */
    private ?array $security = null;

    /** @var list<TagBuilder>|null */
    private ?array $tags = null;

    // externalDocs без core
    private ?string $externalDocsUrl = null;
    private ?string $externalDocsDescription = null;

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

    // ── Метаданные документа ───────────────────────────────────────────────

    public function openapi(string $version): self
    {
        $x = clone $this;
        $x->openapi = $version;

        return $x;
    }

    /** Сахар: фиксированные версии */
    public function oas31(): self
    {
        return $this->openapi('3.1.0');
    }
    public function oas32(): self
    {
        return $this->openapi('3.2.0');
    }

    public function info(Info $info): self
    {
        $x = clone $this;
        $x->info = $info;

        return $x;
    }

    public function jsonSchemaDialect(?string $dialect): self
    {
        $this->guard->assertAllowedKey('OpenApiDocument', 'jsonSchemaDialect');
        $x = clone $this;
        $x->jsonSchemaDialect = $dialect;

        return $x;
    }

    /** 3.2+: $self */
    public function self(string $uri): self
    {
        $this->guard->assertAllowedKey('OpenApiDocument', 'self');
        $x = clone $this;
        $x->self = $uri;

        return $x;
    }

    // ── Основные секции ────────────────────────────────────────────────────

    public function paths(?PathsBuilder $paths): self
    {
        $x = clone $this;
        $x->paths = $paths;

        return $x;
    }

    public function components(?ComponentsBuilder $components): self
    {
        $x = clone $this;
        $x->components = $components;

        return $x;
    }

    /** Задать весь список серверов */
    public function servers(ServerBuilder ...$servers): self
    {
        $x = clone $this;
        $x->servers = $servers ? \array_values($servers) : null;

        return $x;
    }

    /** Добавить один сервер */
    public function addServer(ServerBuilder $server): self
    {
        $x = clone $this;
        $arr = $x->servers ?? [];
        $arr[] = $server;
        $x->servers = $arr;

        return $x;
    }

    /** Задать секцию security */
    public function security(SecurityRequirementBuilder ...$reqs): self
    {
        $x = clone $this;
        $x->security = $reqs ? \array_values($reqs) : null;

        return $x;
    }

    /** Добавить одно требование безопасности */
    public function addSecurity(SecurityRequirementBuilder $req): self
    {
        $x = clone $this;
        $arr = $x->security ?? [];
        $arr[] = $req;
        $x->security = $arr;

        return $x;
    }

    /** Задать теги */
    public function tags(TagBuilder ...$tags): self
    {
        $x = clone $this;
        $x->tags = $tags ? \array_values($tags) : null;

        return $x;
    }

    /** Добавить один тег */
    public function addTag(TagBuilder $tag): self
    {
        $x = clone $this;
        $arr = $x->tags ?? [];
        $arr[] = $tag;
        $x->tags = $arr;

        return $x;
    }

    /** externalDocs без core */
    public function externalDocs(?string $url, ?string $description = null): self
    {
        $x = clone $this;
        $x->externalDocsUrl = $url;
        $x->externalDocsDescription = $description;

        return $x;
    }

    // ── Webhooks (если разрешено профилем) ─────────────────────────────────

    /**
     * Полная карта вебхуков: имя → PathItemBuilder | $ref-строка.
     * @param array<string, PathItemBuilder|string>|null $webhooks
     */
    public function webhooks(?array $webhooks): self
    {
        $this->guard->assertAllowedKey('OpenApiDocument', 'webhooks');
        if ($webhooks === null) {
            $x = clone $this;
            $x->webhooks = null;

            return $x;
        }
        $out = [];
        foreach ($webhooks as $name => $node) {
            if (!\is_string($name) || $name === '') {
                throw new \InvalidArgumentException('OpenApi: webhooks — ключи должны быть непустыми строками.');
            }
            if (!\is_string($node) && !($node instanceof PathItemBuilder)) {
                $got = \is_object($node) ? $node::class : \gettype($node);
                throw new \InvalidArgumentException("OpenApi: webhook '{$name}' должен быть PathItemBuilder или \$ref-строкой, получено {$got}.");
            }
            $out[$name] = $node;
        }
        $x = clone $this;
        $x->webhooks = $out ?: null;

        return $x;
    }

    /** Положить один webhook */
    public function putWebhook(string $name, PathItemBuilder|string $item): self
    {
        $this->guard->assertAllowedKey('OpenApiDocument', 'webhooks');
        if ($name === '') {
            throw new \InvalidArgumentException('OpenApi: имя webhook не может быть пустым.');
        }
        $x = clone $this;
        $map = $x->webhooks ?? [];
        $map[$name] = $item;
        $x->webhooks = $map;

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\OpenApiDocument
    {
        if ($this->openapi === null || $this->openapi === '') {
            throw RequiredMissing::field('OpenApiDocument', 'openapi');
        }
        if ($this->info === null) {
            throw RequiredMissing::field('OpenApiDocument', 'info');
        }

        // paths / components
        $pathsCore = $this->paths?->toModel();
        $componentsCore = $this->components?->toModel();

        // servers → ServerList
        $serversCore = null;
        if ($this->servers !== null) {
            $list = [];
            foreach ($this->servers as $s) {
                $list[] = $s->toModel();
            }
            /** @var ServerList $serversCore */
            $serversCore = Assembler::list(ServerList::class, $list);
        }

        // security → SecurityRequirementList
        $securityCore = null;
        if ($this->security !== null) {
            $list = [];
            foreach ($this->security as $r) {
                $list[] = $r->toModel();
            }
            /** @var SecurityRequirementList $securityCore */
            $securityCore = Assembler::list(SecurityRequirementList::class, $list);
        }

        // tags → TagList
        $tagsCore = null;
        if ($this->tags !== null) {
            $list = [];
            foreach ($this->tags as $t) {
                $list[] = $t->toModel();
            }
            /** @var TagList $tagsCore */
            $tagsCore = Assembler::list(TagList::class, $list);
        }

        // externalDocs
        $extDocsCore = null;
        if ($this->externalDocsUrl !== null) {
            $extDocsCore = new Core\ExternalDocumentation(
                description: $this->externalDocsDescription,
                url: $this->externalDocsUrl,
                extensions: []
            );
        }

        // webhooks → WebhookMap
        $webhooksCore = null;
        if ($this->webhooks !== null) {
            $map = [];
            foreach ($this->webhooks as $name => $node) {
                $map[$name] = \is_string($node)
                    ? RefFactory::fromString($node)
                    : $node->toModel();
            }
            /** @var WebhookMap $webhooksCore */
            $webhooksCore = Assembler::map(WebhookMap::class, $map);
        }

        return new Core\OpenApiDocument(
            openapi:           $this->openapi,
            info:              $this->info->toModel(),
            jsonSchemaDialect: $this->jsonSchemaDialect,
            self:              $this->self,
            paths:             $pathsCore,
            webhooks:          $webhooksCore,
            components:        $componentsCore,
            servers:           $serversCore,
            security:          $securityCore,
            tags:              $tagsCore,
            externalDocs:      $extDocsCore,
            extensions:        $this->extensions(),
        );
    }

    // ProfileAware
    public function profile(): ProfileContext
    {
        return $this->profile;
    }
}
