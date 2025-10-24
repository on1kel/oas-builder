<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Examples;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * Example (builder) — OAS 3.1 / 3.2
 *
 * Поддерживает все поля модели:
 *  - summary?: string
 *  - description?: string
 *  - dataValue?: mixed          (3.2)
 *  - serializedValue?: string   (3.2)
 *  - externalValue?: string
 *  - value?: mixed
 *  - x-* extensions
 *
 * Правила (см. OAS 3.2 §4.19.1–4.19.2):
 *   • dataValue → value ДОЛЖЕН отсутствовать.
 *   • serializedValue → value и externalValue ДОЛЖНЫ отсутствовать.
 *   • externalValue → serializedValue и value ДОЛЖНЫ отсутствовать.
 *   • value ↔ externalValue — взаимоисключаемы (историческое правило 3.1).
 */
final class Example implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $summary = null;
    private ?string $description = null;
    private mixed $dataValue = null;
    private ?string $serializedValue = null;
    private ?string $externalValue = null;
    private mixed $value = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function summary(?string $text): self
    {
        $x = clone $this;
        $x->summary = $text;

        return $x;
    }

    public function description(?string $text): self
    {
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    /** 3.2: пример в виде уже проверенных данных (валидных по Schema). */
    public function dataValue(mixed $data): self
    {
        if ($this->value !== null) {
            throw new \InvalidArgumentException('Example: при наличии "dataValue" поле "value" должно отсутствовать.');
        }
        $x = clone $this;
        $x->dataValue = $data;

        return $x;
    }

    /** 3.2: сериализованная строка (например, JSON/URLencoded). */
    public function serializedValue(?string $serialized): self
    {
        if ($this->value !== null || $this->externalValue !== null) {
            throw new \InvalidArgumentException('Example: при наличии "serializedValue" поля "value" и "externalValue" должны отсутствовать.');
        }
        $x = clone $this;
        $x->serializedValue = $serialized;

        return $x;
    }

    /** URI на внешний пример (файл). */
    public function externalValue(?string $uri): self
    {
        if ($this->value !== null || $this->serializedValue !== null) {
            throw new \InvalidArgumentException('Example: при наличии "externalValue" поля "serializedValue" и "value" должны отсутствовать.');
        }
        $x = clone $this;
        $x->externalValue = $uri;

        return $x;
    }

    /** Inline value (для 3.1 или fallback). */
    public function value(mixed $v): self
    {
        if ($this->externalValue !== null) {
            throw new \InvalidArgumentException('Example: "value" и "externalValue" взаимоисключают друг друга.');
        }
        if ($this->dataValue !== null) {
            throw new \InvalidArgumentException('Example: при наличии "dataValue" поле "value" должно отсутствовать.');
        }
        if ($this->serializedValue !== null) {
            throw new \InvalidArgumentException('Example: при наличии "serializedValue" поле "value" должно отсутствовать.');
        }
        $x = clone $this;
        $x->value = $v;

        return $x;
    }

    // ---- Build ----
    public function toModel(): Core\Example
    {
        return new Core\Example(
            summary:         $this->summary,
            description:     $this->description,
            dataValue:       $this->dataValue,
            serializedValue: $this->serializedValue,
            externalValue:   $this->externalValue,
            value:           $this->value,
            extensions:      $this->extensions(),
        );
    }
}
