<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Traits;

/**
 * Иммутабельное хранение x-* расширений.
 */
trait HasExtensions
{
    /** @var array<string,mixed> */
    private array $extensions = [];

    /**
     * @param array<string,mixed> $extensions
     */
    public function withExtensions(array $extensions): static
    {
        $self = clone $this;
        $self->extensions = $extensions;

        return $self;
    }

    public function extension(string $name, mixed $value): static
    {
        $self = clone $this;
        $self->extensions[$name] = $value;

        return $self;
    }

    /**
     * @return array<string,mixed>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }
}
