<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Security;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * OAuthFlows (builder) — OAS 3.1 / 3.2
 *
 * Принимает ТОЛЬКО билдеры OAuthFlow (или null).
 * Конвертация в core — строго в toModel().
 */
final class OAuthFlows implements BuildsCoreModel
{
    use HasExtensions;

    private ?OAuthFlow $implicit = null;
    private ?OAuthFlow $password = null;
    private ?OAuthFlow $clientCredentials = null;
    private ?OAuthFlow $authorizationCode = null;
    private ?OAuthFlow $deviceAuthorization = null; // OAS 3.2

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // ── Синтаксический сахар: быстрые фабрики ─────────────────────────────

    /** Быстро создать только implicit */
    public static function withImplicit(OAuthFlow $flow): self
    {
        return self::create()->implicit($flow);
    }

    /** Только password */
    public static function withPassword(OAuthFlow $flow): self
    {
        return self::create()->password($flow);
    }

    /** Только clientCredentials */
    public static function withClientCredentials(OAuthFlow $flow): self
    {
        return self::create()->clientCredentials($flow);
    }

    /** Только authorizationCode */
    public static function withAuthorizationCode(OAuthFlow $flow): self
    {
        return self::create()->authorizationCode($flow);
    }

    /** Только deviceAuthorization (OAS 3.2) */
    public static function withDeviceAuthorization(OAuthFlow $flow): self
    {
        return self::create()->deviceAuthorization($flow);
    }

    /** Сразу несколько флоу разом (любой из аргументов можно передать null) */
    public static function of(
        ?OAuthFlow $implicit = null,
        ?OAuthFlow $password = null,
        ?OAuthFlow $clientCredentials = null,
        ?OAuthFlow $authorizationCode = null,
        ?OAuthFlow $deviceAuthorization = null
    ): self {
        return self::create()
            ->implicit($implicit)
            ->password($password)
            ->clientCredentials($clientCredentials)
            ->authorizationCode($authorizationCode)
            ->deviceAuthorization($deviceAuthorization);
    }

    // ── Установка флоу (только билдеры или null) ───────────────────────────

    public function implicit(?OAuthFlow $flow): self
    {
        $x = clone $this;
        $x->implicit = $flow;

        return $x;
    }

    public function password(?OAuthFlow $flow): self
    {
        $x = clone $this;
        $x->password = $flow;

        return $x;
    }

    public function clientCredentials(?OAuthFlow $flow): self
    {
        $x = clone $this;
        $x->clientCredentials = $flow;

        return $x;
    }

    public function authorizationCode(?OAuthFlow $flow): self
    {
        $x = clone $this;
        $x->authorizationCode = $flow;

        return $x;
    }

    public function deviceAuthorization(?OAuthFlow $flow): self
    {
        $x = clone $this;
        $x->deviceAuthorization = $flow;

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\OAuthFlows
    {
        return new Core\OAuthFlows(
            implicit:            $this->implicit?->toModel(),
            password:            $this->password?->toModel(),
            clientCredentials:   $this->clientCredentials?->toModel(),
            authorizationCode:   $this->authorizationCode?->toModel(),
            deviceAuthorization: $this->deviceAuthorization?->toModel(),
            extensions:          $this->extensions(),
        );
    }
}
