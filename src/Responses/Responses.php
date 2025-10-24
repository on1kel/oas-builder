<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Responses;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Errors\InvalidCombination;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\ResponseMap;

/**
 * Responses (builder)
 * Публичный API принимает ТОЛЬКО билдеры Response или $ref-строки.
 * Конверсия в Core — строго в toModel().
 */
final class Responses implements BuildsCoreModel
{
    use HasExtensions;

    /** @var array<string, Response|string> */
    private array $map = [];

    /** @var Response|string|null */
    private Response|string|null $default = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public static function of(Response ...$responses): self
    {
        $self = new self();
        foreach ($responses as $r) {
            $code = (string)$r->statusCode();
            self::assertCode($code);
            $self->map[$code] = $r; // храним билдер
        }

        return $self;
    }

    /**
     * default-ответ: билдер или $ref-строка; null — удалить.
     */
    public function default(Response|string|null $value): self
    {
        if (!is_null($value) && !is_string($value) && !($value instanceof Response)) {
            $got = is_object($value) ? $value::class : gettype($value);
            throw new \InvalidArgumentException("Responses::default ожидает Response или \$ref-строку, получено {$got}");
        }
        $x = clone $this;
        $x->default = $value;

        return $x;
    }

    /** Добавить Response-билдер (код берём из самого билдера). */
    public function add(Response $response): self
    {
        $x = clone $this;
        $code = (string)$response->statusCode();
        self::assertCode($code);
        $x->map[$code] = $response;

        return $x;
    }

    /**
     * Положить под конкретным кодом: билдер или $ref-строка.
     */
    public function put(Response|string $value): self
    {
        if (!is_string($value) && !($value instanceof Response)) {
            self::assertCode($value->statusCode());
            $got = is_object($value) ? $value::class : gettype($value);
            throw new \InvalidArgumentException("Responses::put('{$value->statusCode()}', ...) ожидает Response или \$ref-строку, получено {$got}");
        }
        $x = clone $this;
        $x->map[$value->statusCode()] = $value;

        return $x;
    }

    /** Положить $ref-строку под кодом. */
    public function ref(string $code, string $ref): self
    {
        $code = (string)$code;
        self::assertCode($code);
        if ($ref === '') {
            throw new \InvalidArgumentException('Responses::ref: $ref не может быть пустым.');
        }
        $x = clone $this;
        $x->map[$code] = $ref;

        return $x;
    }

    public function remove(string $code): self
    {
        $x = clone $this;
        unset($x->map[$code]);

        return $x;
    }

    public function has(string $code): bool
    {
        return \array_key_exists($code, $this->map);
    }

    /**
     * Собираем ResponseMap (core) с принудительной строковой нормализацией ключей.
     */
    public function toMap(): ResponseMap
    {
        $norm = [];
        foreach ($this->map as $k => $v) {
            $norm[(string)$k] = is_string($v) ? RefFactory::fromString($v) : $v->toModel();
        }
        /** @var ResponseMap $map */
        $map = Assembler::map(ResponseMap::class, $norm);

        return $map;
    }

    /**
     * Возвращаем Core\Responses.
     */
    public function toModel(): Core\Responses
    {
        $defaultCore = null;
        if ($this->default !== null) {
            $defaultCore = is_string($this->default)
                ? RefFactory::fromString($this->default)
                : $this->default->toModel();
        }

        return new Core\Responses(
            default:    $defaultCore,
            responses:  ($this->map === [] ? null : $this->toMap()),
            extensions: $this->extensions(),
        );
    }

    private static function assertCode(string $code): void
    {
        // допустимо: 'default' | 3-значный [100..599] | маска '[1-5]XX'
        if ($code === 'default') {
            return;
        }
        if (!\preg_match('/^(?:[1-5]\d{2}|[1-5]XX)$/', $code)) {
            throw InvalidCombination::because(
                "Некорректный HTTP статус '{$code}'. Ожидается 'default', 'xxx' или паттерн 'xXX' (например '2XX')."
            );
        }
    }
}
