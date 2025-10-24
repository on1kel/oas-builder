<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Servers;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\ServerVariableMap;

/**
 * Server (builder) — OAS 3.1 / 3.2
 *
 * Публичный API принимает ТОЛЬКО билдеры ServerVariable.
 * Конвертация в core — только в toModel().
 */
final class Server implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $url = null;
    private ?string $description = null;

    /** @var array<string, ServerVariable> */
    private array $variables = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // ── Синтаксический сахар ───────────────────────────────────────────────

    /** Быстрый конструктор */
    public static function of(string $url, ?string $description = null): self
    {
        return self::create()->url($url)->description($description);
    }

    /** Добавить набор переменных за один вызов (name => ServerVariable) */
    public function withVariables(array $vars): self
    {
        return $this->variables($vars);
    }

    // ── Поля ───────────────────────────────────────────────────────────────

    public function url(string $url): self
    {
        if ($url === '') {
            throw new \InvalidArgumentException('Server: url не может быть пустым.');
        }
        $x = clone $this;
        $x->url = $url;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    // ── Переменные ─────────────────────────────────────────────────────────

    /** Добавить/заменить одну переменную. */
    public function variable(string $name, ServerVariable $var): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Server: имя переменной не может быть пустым.');
        }
        $x = clone $this;
        $x->variables[$name] = $var;

        return $x;
    }

    /**
     * Массовая установка переменных.
     * @param array<string, ServerVariable> $vars
     */
    public function variables(array $vars): self
    {
        $map = [];
        foreach ($vars as $name => $var) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException('Server: ключи variables должны быть непустыми строками.');
            }
            if (!$var instanceof ServerVariable) {
                $got = is_object($var) ? $var::class : gettype($var);
                throw new \InvalidArgumentException("Server: переменная '{$name}' должна быть ServerVariable, получено {$got}.");
            }
            $map[$name] = $var;
        }
        $x = clone $this;
        $x->variables = $map;

        return $x;
    }

    /** Удалить переменную. */
    public function removeVariable(string $name): self
    {
        $x = clone $this;
        unset($x->variables[$name]);

        return $x;
    }

    /** Очистить все переменные. */
    public function clearVariables(): self
    {
        $x = clone $this;
        $x->variables = [];

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Server
    {
        if ($this->url === null || $this->url === '') {
            throw new \InvalidArgumentException('Server: необходимо задать url().');
        }

        // Преобразуем переменные в core
        $varsCore = [];
        foreach ($this->variables as $name => $v) {
            $varsCore[$name] = $v->toModel();
        }

        /** @var ServerVariableMap $varMap */
        $varMap = Assembler::map(ServerVariableMap::class, $varsCore);

        return new Core\Server(
            url:         $this->url,
            description: $this->description,
            variables:   $varMap,
            extensions:  $this->extensions(),
        );
    }
}
