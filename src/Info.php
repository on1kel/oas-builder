<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder;

use On1kel\OAS\Builder\Info\Contact;
use On1kel\OAS\Builder\Info\License;
use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Builder\Support\FeatureGuard;
use On1kel\OAS\Builder\Support\ProfileContext;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Support\Traits\HasExtensions;
use On1kel\OAS\Builder\Support\Traits\UsesProfile;
use On1kel\OAS\Core\Model as Core;

/**
 * Info (builder) — OAS 3.1 / 3.2
 *
 * Публичный API: только билдеры и скаляры, без core-моделей.
 * Конвертация в core — только в toModel().
 */
final class Info implements BuildsCoreModel
{
    use HasExtensions;
    use UsesProfile;

    private ?string $title = null;
    private ?string $version = null;
    private ?string $summary = null;
    private ?string $description = null;
    private ?string $termsOfService = null;

    private ?Contact $contact = null;   // билдер
    private ?License $license = null;   // билдер

    private FeatureGuard $guard;

    private function __construct(ProfileContext $ctx)
    {
        $this->profile = $ctx;
        $this->guard   = new FeatureGuard($ctx);
    }

    // ── Фабрики ────────────────────────────────────────────────────────────

    public static function create(?ProfileContext $ctx = null): self
    {
        return new self($ctx ?? ProfileProvider::current());
    }

    /** Быстрая фабрика с обязательными полями */
    public static function of(string $title, string $version, ?ProfileContext $ctx = null): self
    {
        return self::create($ctx)->title($title)->version($version);
    }

    /** Сахар: одним вызовом задать title+version */
    public function with(string $title, string $version): self
    {
        return $this->title($title)->version($version);
    }

    // ── Метаданные ─────────────────────────────────────────────────────────

    public function title(string $title): self
    {
        $this->guard->assertAllowedKey('Info', 'title');
        if ($title === '') {
            throw new \InvalidArgumentException('Info: title не может быть пустым.');
        }
        $x = clone $this;
        $x->title = $title;

        return $x;
    }

    public function version(string $version): self
    {
        $this->guard->assertAllowedKey('Info', 'version');
        if ($version === '') {
            throw new \InvalidArgumentException('Info: version не может быть пустым.');
        }
        $x = clone $this;
        $x->version = $version;

        return $x;
    }

    public function summary(?string $summary): self
    {
        $this->guard->assertAllowedKey('Info', 'summary');
        $x = clone $this;
        $x->summary = $summary;

        return $x;
    }

    public function description(?string $text): self
    {
        $this->guard->assertAllowedKey('Info', 'description');
        $x = clone $this;
        $x->description = $text;

        return $x;
    }

    public function termsOfService(?string $uri): self
    {
        $this->guard->assertAllowedKey('Info', 'termsOfService');
        if ($uri === '') {
            throw new \InvalidArgumentException('Info: termsOfService не может быть пустой строкой; используйте null чтобы убрать поле.');
        }
        $x = clone $this;
        $x->termsOfService = $uri;

        return $x;
    }

    // ── Связанные объекты (только билдеры) ────────────────────────────────

    public function contact(?Contact $contact): self
    {
        $this->guard->assertAllowedKey('Info', 'contact');
        $x = clone $this;
        $x->contact = $contact;

        return $x;
    }

    public function license(?License $license): self
    {
        $this->guard->assertAllowedKey('Info', 'license');
        $x = clone $this;
        $x->license = $license;

        return $x;
    }

    // ── Build ──────────────────────────────────────────────────────────────

    public function toModel(): Core\Info
    {
        if ($this->title === null || $this->title === '') {
            throw new \InvalidArgumentException('Info: требуется title().');
        }
        if ($this->version === null || $this->version === '') {
            throw new \InvalidArgumentException('Info: требуется version().');
        }

        return new Core\Info(
            title:          $this->title,
            version:        $this->version,
            summary:        $this->summary,
            description:    $this->description,
            termsOfService: $this->termsOfService,
            contact:        $this->contact?->toModel(),
            license:        $this->license?->toModel(),
            extensions:     $this->extensions(),
        );
    }
}
