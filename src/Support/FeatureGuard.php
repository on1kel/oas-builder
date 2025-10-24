<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support;

use On1kel\OAS\Builder\Support\Errors\FeatureNotSupported;

/**
 * Универсальная проверка разрешённых ключей по данным профиля.
 * Никаких специфичных для версии методов — только "узел/ключ".
 */
final class FeatureGuard
{
    public function __construct(private ProfileContext $ctx)
    {
    }

    /**
     * Бросает, если ключ недоступен для данного типа узла.
     */
    public function assertAllowedKey(string $nodeType, string $key): void
    {
        if (!$this->isAllowedKey($nodeType, $key)) {
            throw FeatureNotSupported::feature("{$nodeType}.{$key}", $this->ctx->id());
        }
    }

    /**
     * Возвращает true, если ключ доступен.
     */
    public function isAllowedKey(string $nodeType, string $key): bool
    {
        $allowed = $this->ctx->profile->allowedKeysFor($nodeType);
        $normalized = $this->ctx->profile->normalizeKey($nodeType, $key);

        // прямое совпадение
        if (\in_array($normalized, $allowed, true)) {
            return true;
        }

        // wildcard для x-* из профиля
        if (\in_array('x-', $allowed, true) && \str_starts_with($normalized, 'x-')) {
            return true;
        }

        // можно добавить обработку шаблонов, если появятся (например, 'header:*')
        return false;
    }

    /**
     * Удобный шорткат для нескольких ключей.
     */
    public function assertAllowedKeys(string $nodeType, string ...$keys): void
    {
        foreach ($keys as $k) {
            $this->assertAllowedKey($nodeType, $k);
        }
    }
}
