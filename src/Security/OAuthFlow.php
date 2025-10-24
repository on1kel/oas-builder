<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Security;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Core\Model as Core;

/**
 * OAuthFlow (builder) — OAS 3.1 / 3.2
 *
 * Поддерживает поля:
 *  - authorizationUrl
 *  - tokenUrl
 *  - refreshUrl
 *  - scopes (map<string,string>)
 *  - deviceAuthorizationUrl (3.2)
 *  - x-* extensions
 *
 * Иммутабельный, без замыканий. Возвращает Core\OAuthFlow.
 */
final class OAuthFlow implements BuildsCoreModel
{
    use HasExtensions;

    private ?string $authorizationUrl = null;
    private ?string $tokenUrl = null;
    private ?string $refreshUrl = null;

    /** @var array<string,string> */
    private array $scopes = [];

    private ?string $deviceAuthorizationUrl = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // ------------ URLs ------------

    public function authorizationUrl(?string $url): self
    {
        $x = clone $this;
        $x->authorizationUrl = $url;

        return $x;
    }

    public function tokenUrl(?string $url): self
    {
        $x = clone $this;
        $x->tokenUrl = $url;

        return $x;
    }

    public function refreshUrl(?string $url): self
    {
        $x = clone $this;
        $x->refreshUrl = $url;

        return $x;
    }

    /** Только для deviceAuthorization (OAS 3.2). */
    public function deviceAuthorizationUrl(?string $url): self
    {
        $x = clone $this;
        $x->deviceAuthorizationUrl = $url;

        return $x;
    }

    // ------------ Scopes ------------

    /** @param array<string,string> $map */
    public function scopes(array $map): self
    {
        $x = clone $this;
        $x->scopes = $map;

        return $x;
    }

    public function putScope(string $name, string $description): self
    {
        $x = clone $this;
        $x->scopes[$name] = $description;

        return $x;
    }

    public function removeScope(string $name): self
    {
        $x = clone $this;
        unset($x->scopes[$name]);

        return $x;
    }

    // ------------ Build ------------

    public function toModel(): Core\OAuthFlow
    {
        return new Core\OAuthFlow(
            authorizationUrl:       $this->authorizationUrl,
            tokenUrl:               $this->tokenUrl,
            refreshUrl:             $this->refreshUrl,
            scopes:                 $this->scopes,
            deviceAuthorizationUrl: $this->deviceAuthorizationUrl,
            extensions:             $this->extensions(),
        );
    }
}
