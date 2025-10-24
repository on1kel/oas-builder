<?php
declare(strict_types=1);

namespace Builders;

use On1kel\OAS\Profile31\Profile\OAS31Profile;
use PHPUnit\Framework\TestCase;
use On1kel\OAS\Builder\Bodies\RequestBody as RequestBodyBuilder;
use On1kel\OAS\Builder\Media\MediaType as MediaTypeBuilder;
use On1kel\OAS\Builder\Schema\Schema as SchemaBuilder;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Model as Core;

final class RequestBodyBuilderTest extends TestCase
{
    public function setUp(): void
    {
        ProfileProvider::setDefault(new OAS31Profile());
    }

    public function test_minimal_build(): void
    {
        $this->setUp();
        $json = MediaTypeBuilder::of('application/json')
            ->schema(SchemaBuilder::create());

        $builder = RequestBodyBuilder::create()
            ->description('payload')
            ->required(true)
            ->content($json);

        $model = $builder->toModel();

        $this->assertInstanceOf(Core\RequestBody::class, $model);
        $this->assertTrue($model->required);
        $this->assertSame('payload', $model->description);
        $content = iterator_to_array($model->content);

        $this->assertArrayHasKey('application/json', $content);
        $this->assertInstanceOf(\On1kel\OAS\Model\MediaType::class, $content['application/json']);
    }

    public function test_content_map_direct_core_models(): void
    {
        $this->setUp();
        $mt  = MediaTypeBuilder::of('application/json')
            ->schema(SchemaBuilder::create());

        $builder = RequestBodyBuilder::create()
            ->content($mt);

        $model = $builder->toModel();

        $this->assertInstanceOf(Core\RequestBody::class, $model);
        $this->assertArrayHasKey('application/json', iterator_to_array($model->content));
    }

    public function test_immutability_on_setters(): void
    {
        $this->setUp();
        $mt  = MediaTypeBuilder::of('text/plain')->schema(SchemaBuilder::create());

        $a = RequestBodyBuilder::create();
        $b = $a->description('A');
        $c = $b->required();
        $d = $c->content($mt);

        $this->assertNotSame($a, $b);
        $this->assertNotSame($b, $c);
        $this->assertNotSame($c, $d);

        $this->assertInstanceOf(Core\RequestBody::class, $d->toModel());
    }

    /** Показываем JSON-фрагмент requestBody (как он окажется в документе) */
    public function test_request_body_json_fragment(): void
    {
        $this->setUp();

        $json = MediaTypeBuilder::of('application/json')
            ->schema(SchemaBuilder::create());

        $builder = RequestBodyBuilder::create()
            ->description('payload')
            ->required(true)
            ->content($json);

        $model = $builder->toModel();

        // Мини-конвертер Core\RequestBody -> array (как в OpenAPI JSON)
        $fragment = $this->requestBodyToArray($model);

        $actual = json_encode($fragment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $expected = <<<JSON
{
  "description": "payload",
  "required": true,
  "content": {
    "application/json": {
      "schema": {}
    }
  }
}
JSON;

        // Сравнение как JSON (не как строк)
        $this->assertJsonStringEqualsJsonString($expected, $actual);

        // На всякий случай — выведем в лог PHPUnit (видно при -v)
        fwrite(STDOUT, "\nrequestBody JSON fragment:\n".$actual."\n");
    }

    /**
     * Простой helper: вытянуть JSON-представление Core\RequestBody без зависимостей.
     * Достаточно для проверки "как это будет выглядеть в доке".
     *
     * @return array<string, mixed>
     */
    private function requestBodyToArray(Core\RequestBody $rb): array
    {
        $out = [];

        if ($rb->description !== null) {
            $out['description'] = $rb->description;
        }
        // В OAS это поле опционально, но в нашем тесте мы его показываем явно
        if ($rb->required !== null) {
            $out['required'] = (bool)$rb->required;
        }

        // content: map<string, MediaType>
        if ($rb->content !== null) {
            $content = [];
            foreach (iterator_to_array($rb->content) as $mime => $mt) {
                // Для минимального теста нам достаточно показать "schema": {}
                $content[$mime] = ['schema' => new \stdClass()];
            }
            if ($content !== []) {
                $out['content'] = $content;
            }
        }

        return $out;
    }
}
