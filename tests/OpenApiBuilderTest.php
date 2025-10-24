<?php
declare(strict_types=1);

namespace Tests;

use On1kel\OAS\Builder\Info;
use On1kel\OAS\Builder\Support\ProfileProvider;
use PHPUnit\Framework\TestCase;

// FLUENT
use On1kel\OAS\Builder\OpenApi;
use On1kel\OAS\Builder\Components\Components as ComponentsBuilder;
use On1kel\OAS\Builder\Paths\Paths;
use On1kel\OAS\Builder\Paths\PathItem;
use On1kel\OAS\Builder\Paths\Operation;
use On1kel\OAS\Builder\Parameters\Parameter;
use On1kel\OAS\Builder\Responses\Responses;
use On1kel\OAS\Builder\Responses\Response;
use On1kel\OAS\Builder\Media\MediaType;
use On1kel\OAS\Builder\Schema\Schema;

// CORE serialize
use On1kel\OAS\Core\Serialize\DefaultSerializer;
use On1kel\OAS\Core\Serialize\DefaultNormalizer;
use On1kel\OAS\Core\Serialize\DefaultDenormalizer;
use On1kel\OAS\Core\Serialize\TypeRegistry;

// Профиль
use On1kel\OAS\Profile31\Profile\OAS31Profile;

final class OpenApiBuilderTest extends TestCase
{
    private static DefaultSerializer $serializer;
    private static OAS31Profile $profile31;

    public static function setUpBeforeClass(): void
    {
        $registry = new TypeRegistry();
        self::$serializer = new DefaultSerializer(
            normalizers:  [new DefaultNormalizer(['On1kel\\OAS\\Model'])],
            denormalizer: new DefaultDenormalizer($registry)
        );

        self::$profile31 = new OAS31Profile();
    }

    public function test_build_and_serialize_openapi_document(): void
    {
        ProfileProvider::setDefault(new OAS31Profile());
        // 1) Components: схема Pet (properties(...) требуют named)
        $petSchema = Schema::create()
            ->type('object')
            ->properties(
                Schema::create()->named('id')->type('string'),
                Schema::create()->named('name')->type('string'),
            )
            ->required('id', 'name');

        $components = ComponentsBuilder::create()
            ->schema('Pet', $petSchema);

        // 2) Operation GET /pets/{id}
        $getPet = Operation::create()
            ->operationId('getPet')
            ->summary('Get pet by id')
            ->parameter(
                Parameter::path('id')
                    ->required()
                    ->deprecated(false) // core Parameter ждёт bool, не null
                    ->schema(Schema::create()->type('string'))
            )
            ->responses(
                Responses::create()->put(
                    Response::code(200)
                        ->description('OK')
                        ->content(
                            MediaType::of('application/json')
                                ->schema('#/components/schemas/Pet') // строковый $ref — чистый fluent
                        )
                )
            );

        // 3) Paths (используй фабрику, которая реально есть: of()/empty()/шорткат в Paths)
        $pathItem = PathItem::of()->get($getPet);


        if (method_exists($pathItem, 'get')) {
            $pathItem = $pathItem->get($getPet);
        }

        $paths = Paths::create()->put('/pets/{id}', $pathItem);
        // 4) Корень
        $doc = OpenApi::create()
            ->openapi('3.1.0')
            ->info(
                Info::create()
                    ->title('Petstore')
                    ->version('1.0.0')
                    ->description('Simple Petstore API')
            )
            ->components($components)
            ->paths($paths)
            ->toModel();

        // Базовые инварианты по core
        self::assertSame('3.1.0', $doc->openapi);
        self::assertSame('Petstore', $doc->info->title);

        // 5) Сериализация → массив/JSON
        $arr  = self::$serializer->toArray($doc, self::$profile31);
        $json = self::$serializer->toJson($doc, self::$profile31, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        self::assertNotSame('', $json);
        // 6) Жёсткие проверки структуры

        $param0 = $arr['paths']['/pets/{id}']['get']['parameters'][0] ?? null;
        self::assertTrue($doc->paths->has('/pets/{id}'));
        $pi = $doc->paths->get('/pets/{id}');
        self::assertInstanceOf(\On1kel\OAS\Core\Model\PathItem::class, $pi);
        self::assertSame('getPet', $pi->get?->operationId);

    }
}
