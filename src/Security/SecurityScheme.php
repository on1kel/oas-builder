<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Security;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Enum\SecuritySchemeType;

/**
 * SecurityScheme (builder) — OAS 3.1 / 3.2
 * Публичный API — только билдеры и скаляры; без core-моделей.
 */
final class SecurityScheme implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private SecuritySchemeType $type;

    private ?string $description = null;

    // apiKey
    private ?string $name = null;
    /** 'header' | 'query' | 'cookie' */
    private ?string $in = null;

    // http
    private ?string $scheme = null;        // "basic" | "bearer" | "digest" | пр.
    private ?string $bearerFormat = null;  // e.g. "JWT"

    // oauth2
    private ?OAuthFlows $flows = null;

    // openIdConnect
    private ?string $openIdConnectUrl = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx, SecuritySchemeType $type)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->type    = $type;
    }

    // ── Фабрики ────────────────────────────────────────────────────────────

    public static function http(string $scheme, ?ProfileContext $ctx = null): self
    {
        $x = new self($ctx ?? ProfileProvider::current(), SecuritySchemeType::Http);

        return $x->scheme($scheme);
    }

    public static function httpBasic(?ProfileContext $ctx = null): self
    {
        return self::http('basic', $ctx);
    }

    public static function httpBearer(?string $bearerFormat = null, ?ProfileContext $ctx = null): self
    {
        $x = self::http('bearer', $ctx);

        return $bearerFormat !== null ? $x->bearerFormat($bearerFormat) : $x;
    }

    public static function httpDigest(?ProfileContext $ctx = null): self
    {
        return self::http('digest', $ctx);
    }

    public static function apiKey(string $name, string $in, ?ProfileContext $ctx = null): self
    {
        $x = new self($ctx ?? ProfileProvider::current(), SecuritySchemeType::ApiKey);

        return $x->apiKeyName($name)->apiKeyIn($in);
    }

    public static function apiKeyInHeader(string $name, ?ProfileContext $ctx = null): self
    {
        return self::apiKey($name, 'header', $ctx);
    }
    public static function apiKeyInQuery(string $name, ?ProfileContext $ctx = null): self
    {
        return self::apiKey($name, 'query', $ctx);
    }
    public static function apiKeyInCookie(string $name, ?ProfileContext $ctx = null): self
    {
        return self::apiKey($name, 'cookie', $ctx);
    }

    public static function oauth2(OAuthFlows $flows, ?ProfileContext $ctx = null): self
    {
        $x = new self($ctx ?? ProfileProvider::current(), SecuritySchemeType::OAuth2);

        return $x->flows($flows);
    }

    public static function openIdConnect(string $url, ?ProfileContext $ctx = null): self
    {
        $x = new self($ctx ?? ProfileProvider::current(), SecuritySchemeType::OpenIdConnect);

        return $x->openIdConnectUrl($url);
    }

    // ── Поля ───────────────────────────────────────────────────────────────

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'description');
        $y = clone $this;
        $y->description = $text;

        return $y;
    }

    // http
    public function scheme(string $httpAuthScheme): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'scheme');
        $y = clone $this;
        $y->scheme = $httpAuthScheme;

        return $y;
    }

    public function bearerFormat(?string $format): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'bearerFormat');
        $y = clone $this;
        $y->bearerFormat = $format;

        return $y;
    }

    // apiKey
    public function apiKeyName(string $name): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'name');
        if ($name === '') {
            throw new \InvalidArgumentException('SecurityScheme: apiKey.name не может быть пустым.');
        }
        $y = clone $this;
        $y->name = $name;

        return $y;
    }

    /** 'header' | 'query' | 'cookie' */
    public function apiKeyIn(string $in): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'in');
        $allowed = ['header','query','cookie'];
        if (!in_array($in, $allowed, true)) {
            $list = implode("', '", $allowed);
            throw new \InvalidArgumentException("SecurityScheme: apiKey.in должен быть одним из: '{$list}'. Получено: '{$in}'.");
        }
        $y = clone $this;
        $y->in = $in;

        return $y;
    }

    // oauth2 (только билдер!)
    public function flows(OAuthFlows $flows): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'flows');
        $y = clone $this;
        $y->flows = $flows;

        return $y;
    }

    // openIdConnect
    public function openIdConnectUrl(string $url): self
    {
        $this->guard->assertAllowedKey('SecurityScheme', 'openIdConnectUrl');
        if ($url === '') {
            throw new \InvalidArgumentException('SecurityScheme: openIdConnectUrl не может быть пустым.');
        }
        $y = clone $this;
        $y->openIdConnectUrl = $url;

        return $y;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\SecurityScheme
    {
        return new Core\SecurityScheme(
            type:             $this->type,
            description:      $this->description,
            name:             $this->name,
            in:               $this->in,
            scheme:           $this->scheme,
            bearerFormat:     $this->bearerFormat,
            flows:            $this->flows?->toModel(),
            openIdConnectUrl: $this->openIdConnectUrl,
            extensions:       $this->extensions(),
        );
    }
}
