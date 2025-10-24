<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Links;

use On1kel\OAS\Builder\Servers\Server;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * Link (builder) — OAS 3.1 / 3.2
 *
 * Поля:
 *  - operationRef? (string)   // взаимоисключимо с operationId — валидирует core
 *  - operationId? (string)
 *  - parameters?: array<string,mixed>   // runtime expressions допускаются
 *  - requestBody?: mixed
 *  - description?: string
 *  - server?: Core\Server
 *  - x-* extensions
 */
final class Link implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $operationRef = null;
    private ?string $operationId = null;

    /** @var array<string,mixed>|null */
    private ?array $parameters = null;

    private mixed $requestBody = null;
    private ?string $description = null;

    private ?Core\Server $server = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function operationRef(?string $ref): self
    {
        $x = clone $this;
        $x->operationRef = $ref;

        return $x;
    }

    public function operationId(?string $id): self
    {
        $x = clone $this;
        $x->operationId = $id;

        return $x;
    }

    /** @param array<string,mixed> $map */
    public function parameters(array $map): self
    {
        $x = clone $this;
        $x->parameters = $map ?: null;

        return $x;
    }

    public function putParameter(string $name, mixed $value): self
    {
        $x = clone $this;
        $p = $x->parameters ?? [];
        $p[$name] = $value;
        $x->parameters = $p;

        return $x;
    }

    public function requestBody(mixed $body): self
    {
        $x = clone $this;
        $x->requestBody = $body;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function server(?Server $server): self
    {
        $x = clone $this;
        $x->server = $server?->toModel();

        return $x;
    }

    public function toModel(): Core\Link
    {
        return new Core\Link(
            operationRef: $this->operationRef,
            operationId:  $this->operationId,
            parameters:   $this->parameters ?? [],
            requestBody:  $this->requestBody,
            description:  $this->description,
            server:       $this->server,
            extensions:   $this->extensions(),
        );
    }
}
