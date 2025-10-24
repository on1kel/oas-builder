<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Traits;

/**
 * Имя узла (например, имени свойства Schema или компонента).
 */
trait HasName
{
    private ?string $name = null;

    public function named(string $name): static
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
