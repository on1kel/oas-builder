<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support;

/**
 * Полезный утилитный класс для формирования "пути сборки" (для сообщений об ошибках).
 * Иммутабельный: каждый append() возвращает новый экземпляр.
 *
 * Пример: BuildPath::root()->append('components')->append('schemas')->append('User')
 * ->toString() === "$.components.schemas.User"
 */
final class BuildPath
{
    /** @var string[] */
    private array $segments;

    /**
     * @param string[] $segments
     */
    private function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    public static function root(): self
    {
        return new self(['$']);
    }

    public static function fromString(string $dotPath): self
    {
        $dotPath = trim($dotPath);
        if ($dotPath === '') {
            return self::root();
        }
        $segments = \explode('.', $dotPath);
        if ($segments[0] !== '$') {
            \array_unshift($segments, '$');
        }

        return new self($segments);
    }

    public function append(string $segment): self
    {
        $clone = clone $this;
        $clone->segments[] = $segment;

        return $clone;
    }

    /**
     * Формат в стиле $.a.b.c
     */
    public function toString(): string
    {
        return \implode('.', $this->segments);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
