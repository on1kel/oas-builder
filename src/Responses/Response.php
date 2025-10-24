<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Responses;

use On1kel\OAS\Builder\CoreBridge\Assembler;
use On1kel\OAS\Builder\CoreBridge\RefFactory;
use On1kel\OAS\Builder\Headers\Header as HeaderBuilder;
use On1kel\OAS\Builder\Links\Link as LinkBuilder;
use On1kel\OAS\Builder\Media\MediaType as MediaTypeBuilder;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\HeaderMap;
use On1kel\OAS\Core\Model\Collections\Map\LinkMap;
use On1kel\OAS\Core\Model\Collections\Map\MediaTypeMap;

final class Response implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    /** HTTP статус/идентификатор (в core не входит) */
    private int|string $code = 'default';

    private ?string $description = null;

    /** @var array<string, HeaderBuilder|string>|null */
    private ?array $headers = null;

    /** @var array<string, MediaTypeBuilder>|null */
    private ?array $content = null;

    /** @var array<string, LinkBuilder|string>|null */
    private ?array $links = null;

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx, int $code)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
        $this->code    = $code;
    }

    public static function code(int $code, ?ProfileContext $ctx = null): self
    {
        return self::with($code, null, $ctx);
    }

    // ── Синтаксический сахар по распространённым кодам ─────────────────────
    public static function ok(?string $desc = 'OK', ?ProfileContext $ctx = null): self
    {
        return self::with(200, $desc, $ctx);
    }
    public static function created(?string $desc = 'Created', ?ProfileContext $ctx = null): self
    {
        return self::with(201, $desc, $ctx);
    }
    public static function accepted(?string $desc = 'Accepted', ?ProfileContext $ctx = null): self
    {
        return self::with(202, $desc, $ctx);
    }
    public static function noContent(?string $desc = 'No Content', ?ProfileContext $ctx = null): self
    {
        return self::with(204, $desc, $ctx);
    }
    public static function movedPermanently(?string $desc = 'Moved Permanently', ?ProfileContext $ctx = null): self
    {
        return self::with(301, $desc, $ctx);
    }
    public static function found(?string $desc = 'Found', ?ProfileContext $ctx = null): self
    {
        return self::with(302, $desc, $ctx);
    }
    public static function notModified(?string $desc = 'Not Modified', ?ProfileContext $ctx = null): self
    {
        return self::with(304, $desc, $ctx);
    }
    public static function badRequest(?string $desc = 'Bad Request', ?ProfileContext $ctx = null): self
    {
        return self::with(400, $desc, $ctx);
    }
    public static function unauthorized(?string $desc = 'Unauthorized', ?ProfileContext $ctx = null): self
    {
        return self::with(401, $desc, $ctx);
    }
    public static function paymentRequired(?string $desc = 'Payment Required', ?ProfileContext $ctx = null): self
    {
        return self::with(402, $desc, $ctx);
    }
    public static function forbidden(?string $desc = 'Forbidden', ?ProfileContext $ctx = null): self
    {
        return self::with(403, $desc, $ctx);
    }
    public static function notFound(?string $desc = 'Not Found', ?ProfileContext $ctx = null): self
    {
        return self::with(404, $desc, $ctx);
    }
    public static function methodNotAllowed(?string $desc = 'Method Not Allowed', ?ProfileContext $ctx = null): self
    {
        return self::with(405, $desc, $ctx);
    }
    public static function conflict(?string $desc = 'Conflict', ?ProfileContext $ctx = null): self
    {
        return self::with(409, $desc, $ctx);
    }
    public static function gone(?string $desc = 'Gone', ?ProfileContext $ctx = null): self
    {
        return self::with(410, $desc, $ctx);
    }
    public static function unsupportedMediaType(?string $desc = 'Unsupported Media Type', ?ProfileContext $ctx = null): self
    {
        return self::with(415, $desc, $ctx);
    }
    public static function unprocessableEntity(?string $desc = 'Unprocessable Entity', ?ProfileContext $ctx = null): self
    {
        return self::with(422, $desc, $ctx);
    }
    public static function tooManyRequests(?string $desc = 'Too Many Requests', ?ProfileContext $ctx = null): self
    {
        return self::with(429, $desc, $ctx);
    }
    public static function internalServerError(?string $desc = 'Internal Server Error', ?ProfileContext $ctx = null): self
    {
        return self::with(500, $desc, $ctx);
    }
    public static function notImplemented(?string $desc = 'Not Implemented', ?ProfileContext $ctx = null): self
    {
        return self::with(501, $desc, $ctx);
    }
    public static function badGateway(?string $desc = 'Bad Gateway', ?ProfileContext $ctx = null): self
    {
        return self::with(502, $desc, $ctx);
    }
    public static function serviceUnavailable(?string $desc = 'Service Unavailable', ?ProfileContext $ctx = null): self
    {
        return self::with(503, $desc, $ctx);
    }
    public static function gatewayTimeout(?string $desc = 'Gateway Timeout', ?ProfileContext $ctx = null): self
    {
        return self::with(504, $desc, $ctx);
    }

    /** Общая фабрика с дефолтным description */
    private static function with(int $code, ?string $desc, ?ProfileContext $ctx): self
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException("Response: недопустимый HTTP статус {$code}.");
        }
        $self = new self($ctx ?? ProfileProvider::current(), $code);
        if ($desc !== null) {
            $self->description = $desc;
        }

        return $self;
    }

    // ── Поля ────────────────────────────────────────────────────────────────

    public function description(string $text): self
    {
        $this->guard->assertAllowedKey('Response', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    /**
     * headers: имя → HeaderBuilder|$ref
     * @param array<string, HeaderBuilder|string> $headers
     */
    public function headers(array $headers): self
    {
        $this->guard->assertAllowedKey('Response', 'headers');
        foreach ($headers as $name => $h) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException('Response: headers — ключи должны быть непустыми строками.');
            }
            if (!is_string($h) && !($h instanceof HeaderBuilder)) {
                $got = is_object($h) ? $h::class : gettype($h);
                throw new \InvalidArgumentException("Response: headers['{$name}'] — нужен HeaderBuilder или \$ref-строка, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->headers = $headers ?: null;

        return $x;
    }

    /** Добавить один header. */
    public function header(string $name, HeaderBuilder|string $header): self
    {
        $this->guard->assertAllowedKey('Response', 'headers');
        if ($name === '') {
            throw new \InvalidArgumentException('Response: header name не может быть пустым.');
        }
        if (!is_string($header) && !($header instanceof HeaderBuilder)) {
            $got = is_object($header) ? $header::class : gettype($header);
            throw new \InvalidArgumentException("Response: header '{$name}' — нужен HeaderBuilder или \$ref-строка, получено {$got}.");
        }
        $x = clone $this;
        $map = $x->headers ?? [];
        $map[$name] = $header;
        $x->headers = $map;

        return $x;
    }

    /**
     * content: MIME → MediaTypeBuilder
     * @param array<string, MediaTypeBuilder> $byMime
     */
    public function contentMap(array $byMime): self
    {
        $this->guard->assertAllowedKey('Response', 'content');
        foreach ($byMime as $mime => $mt) {
            if (!is_string($mime) || $mime === '' || !($mt instanceof MediaTypeBuilder)) {
                $got = is_object($mt) ? $mt::class : gettype($mt);
                throw new \InvalidArgumentException("Response: content['{$mime}'] — нужен MediaTypeBuilder, получено {$got}.");
            }
        }
        $x = clone $this;
        $map = $x->content ?? [];
        foreach ($byMime as $mime => $mt) {
            $map[$mime] = $mt;
        }
        $x->content = $map ?: null;

        return $x;
    }

    /** Добавить один media type. */
    public function content(MediaTypeBuilder ...$media): self
    {
        $this->guard->assertAllowedKey('Response', 'content');
        $x = clone $this;
        $map = $x->content ?? [];
        foreach ($media as $m) {
            $map[$m->mime()] = $m;
        }
        $x->content = $map ?: null;

        return $x;
    }

    /**
     * links: имя → LinkBuilder|$ref
     * @param array<string, LinkBuilder|string> $links
     */
    public function links(array $links): self
    {
        $this->guard->assertAllowedKey('Response', 'links');
        foreach ($links as $name => $l) {
            if (!is_string($name) || $name === '') {
                throw new \InvalidArgumentException('Response: links — ключи должны быть непустыми строками.');
            }
            if (!is_string($l) && !($l instanceof LinkBuilder)) {
                $got = is_object($l) ? $l::class : gettype($l);
                throw new \InvalidArgumentException("Response: links['{$name}'] — нужен LinkBuilder или \$ref-строка, получено {$got}.");
            }
        }
        $x = clone $this;
        $x->links = $links ?: null;

        return $x;
    }

    /** Добавить один link. */
    public function link(string $name, LinkBuilder|string $link): self
    {
        $this->guard->assertAllowedKey('Response', 'links');
        if ($name === '') {
            throw new \InvalidArgumentException('Response: link name не может быть пустым.');
        }
        if (!is_string($link) && !($link instanceof LinkBuilder)) {
            $got = is_object($link) ? $link::class : gettype($link);
            throw new \InvalidArgumentException("Response: link '{$name}' — нужен LinkBuilder или \$ref-строка, получено {$got}.");
        }
        $x = clone $this;
        $map = $x->links ?? [];
        $map[$name] = $link;
        $x->links = $map;

        return $x;
    }

    /** Статус/идентификатор (для внешних контейнеров) */
    public function statusCode(): int|string
    {
        return $this->code;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Response
    {
        // headers → map<string, Core\Header|Core\Reference>|null
        $headersCore = null;
        if ($this->headers !== null) {
            $headersCore = [];
            foreach ($this->headers as $name => $h) {
                $headersCore[$name] = is_string($h)
                    ? RefFactory::fromString($h)
                    : $h->toModel();
            }
        }

        // content → map<string, Core\MediaType>|null
        $contentCore = null;
        if ($this->content !== null) {
            $contentCore = [];
            foreach ($this->content as $mime => $mt) {
                $contentCore[$mime] = $mt->toModel();
            }
        }

        // links → map<string, Core\Link|Core\Reference>|null
        $linksCore = null;
        if ($this->links !== null) {
            $linksCore = [];
            foreach ($this->links as $name => $l) {
                $linksCore[$name] = is_string($l)
                    ? RefFactory::fromString($l)
                    : $l->toModel();
            }
        }

        return new Core\Response(
            description: $this->description ?? '',
            headers:     Assembler::mapOrNull(HeaderMap::class, $headersCore),
            content:     Assembler::mapOrNull(MediaTypeMap::class, $contentCore),
            links:       Assembler::mapOrNull(LinkMap::class, $linksCore),
            extensions:  $this->extensions(),
        );
    }
}
