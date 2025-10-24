<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\CoreBridge;

final class Assembler
{
    /**
     * Универсальная сборка Map-коллекции ядра.
     * Пример: Assembler::map(\On1kel\OAS\Core\Model\Collections\Map\HeaderMap::class, $items)
     *
     * @template T of object
     * @param  class-string<T>     $mapClass FQN класса карты из ядра
     * @param  array<string,mixed> $items    данные (уже core-модели/Reference)
     * @return T
     */
    public static function map(string $mapClass, array $items): object
    {
        if (!class_exists($mapClass)) {
            throw new \InvalidArgumentException("Assembler::map(): класс '{$mapClass}' не найден.");
        }

        /** @psalm-suppress MixedMethodCall */
        return new $mapClass($items);
    }

    /**
     * Версия с null: вернёт null, если $items пуст/равен null.
     *
     * @template T of object
     * @param  class-string<T>          $mapClass
     * @param  array<string,mixed>|null $items
     * @return T|null
     */
    public static function mapOrNull(string $mapClass, ?array $items): ?object
    {
        if ($items === null || $items === []) {
            return null;
        }

        /** @psalm-suppress MixedMethodCall */
        return new $mapClass($items);
    }

    /**
     * Универсальная сборка List-коллекции ядра.
     * Пример: Assembler::list(\On1kel\OAS\Core\Model\Collections\List\SchemaList::class, $items)
     *
     * @template T of object
     * @param  class-string<T> $listClass FQN класса списка из ядра
     * @param  list<mixed>     $items     данные (уже core-модели/Reference)
     * @return T
     */
    public static function list(string $listClass, array $items): object
    {
        if (!class_exists($listClass)) {
            throw new \InvalidArgumentException("Assembler::list(): класс '{$listClass}' не найден.");
        }

        /** @psalm-suppress MixedMethodCall */
        return new $listClass($items);
    }

    /**
     * Версия с null для списков.
     *
     * @template T of object
     * @param  class-string<T>  $listClass
     * @param  list<mixed>|null $items
     * @return T|null
     */
    public static function listOrNull(string $listClass, ?array $items): ?object
    {
        if ($items === null || $items === []) {
            return null;
        }

        /** @psalm-suppress MixedMethodCall */
        return new $listClass($items);
    }
}
