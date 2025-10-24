<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support;

use On1kel\OAS\Core\Contract\Profile\SpecProfile;

/**
 * Глобальный провайдер профиля для билдеров (в стиле oooas).
 *
 * Идея: один раз установить активный профиль → все билдеры используют его
 * по умолчанию в статических фабриках. Без замыканий.
 *
 * Поддержан стек, чтобы можно было временно переключаться на другой профиль
 * (push/pop) без влияния на глобальный default.
 */
final class ProfileProvider
{
    /** @var ProfileContext|null */
    private static ?ProfileContext $default = null;

    /** @var list<ProfileContext> LIFO-стек временных профилей */
    private static array $stack = [];

    /**
     * Установить профиль по умолчанию.
     */
    public static function setDefault(SpecProfile|ProfileContext $profile): void
    {
        self::$default = $profile instanceof ProfileContext
            ? $profile
            : ProfileContext::fromProfile($profile);
    }

    /**
     * Текущий профиль (верх стека, иначе default).
     */
    public static function current(): ProfileContext
    {
        if (self::$stack !== []) {
            return self::$stack[\count(self::$stack) - 1];
        }
        if (!self::$default) {
            throw new \LogicException(
                'ProfileProvider: профиль не задан. Вызови ProfileProvider::setDefault(new OAS31Profile()|OAS32Profile()).'
            );
        }

        return self::$default;
    }

    /**
     * Временно переключиться на другой профиль.
     * Парная операция — pop().
     */
    public static function push(SpecProfile|ProfileContext $profile): void
    {
        self::$stack[] = $profile instanceof ProfileContext
            ? $profile
            : ProfileContext::fromProfile($profile);
    }

    /**
     * Снять временный профиль.
     */
    public static function pop(): void
    {
        if (self::$stack === []) {
            throw new \LogicException('ProfileProvider::pop(): стек пуст.');
        }
        \array_pop(self::$stack);
    }

    /**
     * Получить текущий профиль
     * @return SpecProfile
     */
    public static function profile(): SpecProfile
    {
        return self::current()->profile;
    }
}
