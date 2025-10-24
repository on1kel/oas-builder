<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Paths;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\Parameters\Parameter as ParameterBuilder;
use On1kel\OAS\Builder\Servers\Server as ServerBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\List\ParameterList;
// если у вас есть билдер серверов:
use On1kel\OAS\Core\Model\Collections\List\ServerList;

final class PathItem implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private ?string $ref = null;         // $ref (строка)
    private ?string $summary = null;
    private ?string $description = null;

    // Храним ИМЕННО fluent-операции; в core переводим в toModel()
    private ?Operation $get = null;
    private ?Operation $put = null;
    private ?Operation $post = null;
    private ?Operation $delete = null;
    private ?Operation $options = null;
    private ?Operation $head = null;
    private ?Operation $patch = null;
    private ?Operation $trace = null;

    /** @var list<ParameterBuilder> */
    private array $parameters = [];

    /** @var list<ServerBuilder> */
    private array $servers = [];

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx)
    {
        $this->profile = $ctx;
        $this->guard = new FeatureGuard($ctx);
    }

    public static function create(?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current());
    }

    // ---------------- Метаданные ----------------

    public function ref(string $ref): self
    {
        // В OAS 3.1 у PathItem разрешены siblings ($ref + summary/description)
        $this->guard->assertAllowedKey('PathItem', '$ref');
        $x = clone $this;
        $x->ref = $ref;

        return $x;
    }

    public function summary(?string $text): self
    {
        $this->guard->assertAllowedKey('PathItem', 'summary');
        $x = clone $this;
        $x->summary = $text;

        return $x;
    }

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('PathItem', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    // ---------------- Операции ------------------

    public function get(?Operation $op): self
    {
        return $this->withOp('get', $op);
    }

    public function put(?Operation $op): self
    {
        return $this->withOp('put', $op);
    }

    public function post(?Operation $op): self
    {
        return $this->withOp('post', $op);
    }

    public function delete(?Operation $op): self
    {
        return $this->withOp('delete', $op);
    }

    public function options(?Operation $op): self
    {
        return $this->withOp('options', $op);
    }

    public function head(?Operation $op): self
    {
        return $this->withOp('head', $op);
    }

    public function patch(?Operation $op): self
    {
        return $this->withOp('patch', $op);
    }

    public function trace(?Operation $op): self
    {
        return $this->withOp('trace', $op);
    }

    private function withOp(string $kind, ?Operation $op): self
    {
        $this->guard->assertAllowedKey('PathItem', $kind);
        $x = clone $this;

        match ($kind) {
            'get' => $x->get = $op,
            'put' => $x->put = $op,
            'post' => $x->post = $op,
            'delete' => $x->delete = $op,
            'options' => $x->options = $op,
            'head' => $x->head = $op,
            'patch' => $x->patch = $op,
            'trace' => $x->trace = $op,
            default => null,
        };

        return $x;
    }

    // ---------- Общие параметры и серверы ------

    public function parameters(ParameterBuilder ...$params): self
    {
        $this->guard->assertAllowedKey('PathItem', 'parameters');
        $x = clone $this;
        array_push($x->parameters, ...$params);

        return $x;
    }

    /** @param list<ParameterBuilder> $params */
    public function parametersList(array $params): self
    {
        $this->guard->assertAllowedKey('PathItem', 'parameters');
        $x = clone $this;
        $x->parameters = [...$this->parameters, ...$params];

        return $x;
    }

    // Если у вас нет ServerBuilder — сделайте аналогично, принимая только fluent,
    // либо дайте удобный сахар на уровне Paths/Operation
    public function servers(ServerBuilder ...$servers): self
    {
        $this->guard->assertAllowedKey('PathItem', 'servers');
        $x = clone $this;
        array_push($x->servers, ...$servers);

        return $x;
    }

    // ---------------- Build ---------------------

    public function toModel(): Core\PathItem
    {
        $paramsList = $this->parameters === []
            ? null
            : Assembler::list(
                ParameterList::class,
                array_map(static fn (ParameterBuilder $p) => $p->toModel(), $this->parameters)
            );

        $serverList = $this->servers === []
            ? null
            : Assembler::list(
                ServerList::class,
                array_map(static fn (ServerBuilder $s) => $s->toModel(), $this->servers)
            );

        return new Core\PathItem(
            ref: $this->ref,
            summary: $this->summary,
            description: $this->description,
            get: $this->get?->toModel(),
            put: $this->put?->toModel(),
            post: $this->post?->toModel(),
            delete: $this->delete?->toModel(),
            options: $this->options?->toModel(),
            head: $this->head?->toModel(),
            patch: $this->patch?->toModel(),
            trace: $this->trace?->toModel(),
            servers: $serverList,
            parameters: $paramsList,
            extensions: $this->extensions(),
        );
    }
}
