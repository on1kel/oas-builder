# on1kel/oas-builder

**OpenAPI Specification Builder** — это набор иммутабельных PHP-билдеров (fluent DSL), которые позволяют собрать спецификацию OpenAPI (3.1 / 3.2) программно, без ручного YAML/JSON.

Билдеры возвращают строго типизированные объекты ядра (`on1kel/oas-core`), которые дальше можно сериализовать в JSON/YAML и отдавать в документацию, Swagger UI, генераторы клиентов и т.д.

---

## Ключевые особенности

### 1. Fluent-DSL поверх OAS 3.1 / 3.2

```php
OpenApi::create()
    ->oas31()
    ->info(
        Info::of('My API', '1.0.0')
    )
    ->paths(
        Paths::of()
            ->get('/users/{id}', $getUserOp)
    )
    ->components(
        Components::create()
            ->schema('User', $userSchema)
    )
    ->toModel(); // -> Core\OpenApiDocument
```

### 2. Иммутабельность

Каждый вызов сеттера возвращает НОВЫЙ объект. Никаких "мутаций по месту", значит удобно переиспользовать и дополнять билдеры без побочных эффектов.

```php
$infoV1 = Info::of('Shop API', '1.0.0');
$infoV2 = $infoV1->version('2.0.0'); // $infoV1 не изменился
```

### 3. Жёсткие инварианты и валидация

* Обязательные поля проверяются (например, `openapi`, `info`, `Response::description()` и т.д.).
* Взаимоисключающие поля проверяются (например, `MediaType::example()` нельзя вместе с `MediaType::examples()`).
* Несовместимые с профилем спецификации поля блокируются (`FeatureGuard`).


### 4. Профили спецификации (Profile)

Профиль = знание того, какие фичи разрешены для конкретной версии спецификации (3.1 против 3.2):

* какие поля вообще допустимы,
* какие секции доступны (`webhooks`, `jsonSchemaDialect`, `itemSchema`, `tag.parent`, ...),
* какие ключи недоступны в текущем профиле → будет выброшено исключение.

Профиль хранится в `ProfileProvider` и автоматически прокидывается во ВСЕ билдеры.

```php
use On1kel\OAS\Builder\Support\ProfileProvider;
// Пример: профиль OpenAPI 3.1 или 3.2
// ProfileProvider::setDefault(new OAS31Profile());
// ProfileProvider::setDefault(new OAS32Profile());
```

Если профиль не установлен явно, вызов билдеров упадёт с понятной ошибкой:

```text
ProfileProvider: профиль не задан. Вызови ProfileProvider::setDefault(new OAS31Profile()|OAS32Profile()).
```

Пакет `on1kel/oas-profile-31` содержит реализацию профиля для OpenAPI 3.1 и описывает допустимые фичи этой версии.

### 5. Чёткое разделение "builder" → "core"

* Публичный код (вы пишете им) — билдеры из `On1kel\OAS\Builder\...`
* Результат сборки — строгие модели ядра (`On1kel\OAS\Core\Model\...`), которые возвращаются методом `toModel()`.

Билдер НИКОГДА не просит у вас готовые Core-модели (за редкими намеренно разрешёнными исключениями вроде `ExternalDocumentation`).
В билдер передаются только:

* скаляры,
* билдеры,
* строки `$ref` (например `#/components/schemas/User`).

---

## Установка

```bash
composer require on1kel/oas-builder
```

Пакет зависит от `on1kel/oas-core` (уже объявлено в `require`).

Для конкретной версии спецификации нужно добавить профиль.
Для OpenAPI 3.1:

```bash
composer require on1kel/oas-profile-31
```

(Пакет `on1kel/oas-profile-31` объявляет профиль правил и фич для OAS 3.1 и используется `ProfileProvider`.)

Требования:

* PHP ^8.2
* ext-json (непосредственно ядру почти всегда нужен JSON)
* семантика пакета — SemVer (`^1.0`)

Лицензия: MIT.

---

## Быстрый старт (полноценный пример)

Ниже пример минимального документа c одним GET `/users/{id}`.

```php
<?php

use On1kel\OAS\Builder\OpenApi;
use On1kel\OAS\Builder\Info;
use On1kel\OAS\Builder\Paths\Paths;
use On1kel\OAS\Builder\Paths\Operation;
use On1kel\OAS\Builder\Paths\PathItem;
use On1kel\OAS\Builder\Parameters\Parameter;
use On1kel\OAS\Builder\Parameters\ParameterIn;
use On1kel\OAS\Builder\Responses\Responses;
use On1kel\OAS\Builder\Responses\Response;
use On1kel\OAS\Builder\Media\MediaType;
use On1kel\OAS\Builder\Schema\Schema;
use On1kel\OAS\Builder\Components\Components;
use On1kel\OAS\Builder\Support\ProfileProvider;

// ШАГ 0. Устанавливаем активный профиль спецификации.
// Пример для OpenAPI 3.1 (класс профиля поставляется пакетом on1kel/oas-profile-31):
// ProfileProvider::setDefault(new OAS31Profile());

// ШАГ 1. Описываем схему пользователя
$userSchema = Schema::object('User')
    ->required(['id', 'name'])
    ->property('id',   Schema::integer()->format('int64'))
    ->property('name', Schema::string());

// ШАГ 2. path-параметр {id}
$userIdParam = Parameter::path('id')
    ->required(true)
    ->description('Идентификатор пользователя')
    ->schema(Schema::integer()->format('int64'));

// ШАГ 3. Ответ 200 application/json
$okResponse = Response::code(200)
    ->description('Успешный ответ')
    ->contentMap([
        'application/json' => MediaType::of('application/json')
            ->schema(
                // здесь можно либо inline-схему, либо ссылку на components:
                Schema::ref('#/components/schemas/User')
            ),
    ]);

// ШАГ 4. Набор ответов операции
$responses = Responses::create()
    ->add($okResponse)
    ->default(
        Response::code(500)
            ->description('Внутренняя ошибка')
    );

// ШАГ 5. Операция GET /users/{id}
$getUserOp = Operation::create()
    ->operationId('getUser')
    ->summary('Получить пользователя по ID')
    ->description('Возвращает объект пользователя')
    ->parameters($userIdParam) // можно несколько параметров через повторные вызовы ->parameters(...)
    ->responses($responses);

// ШАГ 6. Paths
$paths = Paths::of()
    ->get('/users/{id}', $getUserOp);

// ШАГ 7. Components (реестр переиспользуемых сущностей)
$components = Components::create()
    ->schema('User', $userSchema);

// ШАГ 8. Документ OpenAPI
$docBuilder = OpenApi::create()
    ->oas31() // или ->oas32()
    ->info(
        Info::of('User Service API', '1.0.0')
            ->description('Сервис пользователей')
    )
    ->paths($paths)
    ->components($components);

// ШАГ 9. Получаем core-модель
$openapiDocument = $docBuilder->toModel(); // Core\OpenApiDocument

// Дальше:
//   - сериализуем $openapiDocument в JSON/YAML (через on1kel/oas-core или свою обёртку),
//   - кладём в Swagger UI / ReDoc / кодогенератор клиента.
```

Что важно:

* В билдеры можно передавать `$ref` строками, например:

  ```php
  Schema::ref('#/components/schemas/User')
  ```

  или прямо `'#/components/schemas/User'` в тех местах, где явно сказано, что поле принимает строку `$ref`.

* Почти все сеттеры допускают `null`, чтобы "снести" поле (пригодно для ветвления конфигураций).

---

## Архитектура пакета

### 1. Билдеры по секциям спецификации

* `OpenApi` — корень документа. Версия спецификации (`oas31()` / `oas32()`), `info`, `servers`, `paths`, `components`, `security`, `tags`, `webhooks`, `externalDocs`.
* `Info`, `Info\Contact`, `Info\License` — описание API.
* `Servers\Server`, `Servers\ServerVariable` — список серверов и их переменные.
* `Paths\Paths` → `Paths\PathItem` → `Paths\Operation` — эндпоинты, HTTP-методы, параметры, тела, ответы.
* `Parameters\Parameter` — query/path/header/cookie параметры.
* `Bodies\RequestBody` — requestBody с контентом.
* `Responses\Response`, `Responses\Responses` — ответы операций.
* `Media\MediaType` — контент `application/json`, `text/plain` и т.д., включая schema/encoding.
* `Schema\Schema` — декларативная обёртка над JSON Schema / OAS Schema.
* `Components\Components` — `schemas`, `responses`, `parameters`, `requestBodies`, `headers`, `securitySchemes`, `links`, `callbacks`, `examples`.
* `Security\SecurityRequirement` — требования авторизации.
* `Tags\Tag` — теги (в т.ч. расширения OAS 3.2: `summary`, `parent`, `kind`).

Все билдеры реализуют общий контракт:

```php
interface BuildsCoreModel {
    public function toModel(): /* Core\Model\... */;
}
```

То есть любая часть дерева можно собрать отдельно и позже вклеить в документ.

### 2. CoreBridge

`CoreBridge\Assembler` и `CoreBridge\RefFactory` — технический слой между билдерами и моделью ядра:

* `Assembler::map(...)`, `Assembler::list(...)` аккуратно упаковывают обычные массивы/списки билдера в коллекции ядра (`Map`, `List` классы из `on1kel/oas-core`).
* `RefFactory::fromString('#/components/schemas/User')` создаёт `Core\Model\Reference`.

Этот слой гарантирует, что на выходе `toModel()` вы всегда получаете корректные структуры `on1kel/oas-core`, а не случайные PHP-массивы.

### 3. FeatureGuard

`Support\FeatureGuard` получает `ProfileContext` и запрещает поля, которые недоступны в текущем профиле спецификации.
Например, если поле `itemSchema` разрешено только в OAS 3.2, попытка вызвать соответствующий сеттер в профиле 3.1 кинет `FeatureNotSupported`.

Это:

* защищает от случайного использования фич из будущих версий,
* делает миграцию с 3.1 → 3.2 прозрачной (вы просто меняете профиль и сразу видите где код несовместим).

---

## Работа с `$ref`

Во многих местах билдеры принимают ИЛИ билдер, ИЛИ строку `$ref`.

Примеры:

* `Operation::parameters(...)` — можно передать `Parameter` ИЛИ строку `'#/components/parameters/UserId'`
* `Components::schema('User', $userSchema)` — регистрирует схему;
* `Schema::ref('#/components/schemas/User')` — быстрый способ вставить `$ref` в другое место;

Это сознательное решение:

* `$ref` остаётся простой строкой, не надо создавать отдельный объект,
* в `toModel()` все `$ref` автоматически превращаются в `Core\Model\Reference`.

---

## Расширения (x-...)

Любой билдер с трейтом `HasExtensions` поддерживает vendor extensions:

```php
$tag = Tag::of('admin')
    ->extension('x-internal', true);

$api = OpenApi::create()
    ->oas31()
    ->info(Info::of('My API', '1.0.0'))
    ->tags($tag);
```

`->extension($name, $value)` хранит произвольные данные, которые потом попадут в финальную core-модель.

---

## Обработка результата (`toModel()`)

Каждый билдер имеет метод `toModel()`, который собирает финальный экземпляр ядра `on1kel/oas-core`.

Примеры:

* `OpenApi::toModel()` → `Core\OpenApiDocument`
* `Paths\Operation::toModel()` → `Core\Operation`
* `Schema\Schema::toModel()` → `Core\Schema`
* и т.д.

Дальше вы можете:

1. сериализовать объект ядра в YAML/JSON (например, отдать в Swagger UI),
2. использовать его в автогенерации клиентов/серверов,
3. сохранить как артефакт CI.

Способ сериализации зависит от того, как именно `on1kel/oas-core` представляет модель (`JsonSerializable`, export-вспомогательные методы и т.д.). Если ваш рантайм ожидает массив — преобразуйте core-модель соответствующим методом ядра или собственным маппером.
Структура уже нормализована и строгая (в том числе коллекции `Map` и `List` из ядра).

---

## Инварианты и правила использования

1. **Иммутабельность.**
   Любой сеттер (`->summary()`, `->description()`, `->schema()`, …) не изменяет текущий инстанс, а возвращает клон с изменениями.

2. **Только билдеры / $ref / скаляры.**
   В публичных методах вы НЕ передаёте "готовые" Core-модели ядра напрямую.
   Исключения чётко помечены в коде комментариями (например `ExternalDocumentation` и т.п., где это оправдано).

3. **Взаимоисключающие поля.**
   Некоторые поля по спецификации нельзя указывать одновременно.
   Пример: `MediaType::example()` конфликтует с `MediaType::examples()`.
   Попытка смешать — `InvalidCombination`.

4. **Обязательные поля.**
   Если вы не установили поле, которое требуется спекой (например, `OpenApi::info()`), `toModel()` бросит `RequiredMissing`.

5. **Проверка профиля.**
   Если поле недоступно для активного профиля спецификации, будет `FeatureNotSupported`.

6. **Валидация путей, кодов ответа и т.д.**
   `Paths::put('/users/{id}', ...)` не позволит пустой путь, всегда требует ведущий `/`.
   `Responses` валидирует коды (`'200'`, `'404'`, `'default'` и т.п.) и не даст положить мусор.

---

## Когда использовать этот пакет

### Подходит для:

* Генерации OpenAPI спецификации на лету в прод-окружении из актуального кода.
* Поддержки нескольких версий API (v1, v2) через чистый PHP-код вместо ручного копирования YAML.
* Гарантированного отслеживания несовместимых фич между OAS 3.1 и 3.2 (через профили).
* Сборки спецификации в тестах и валидации, что она корректна, ещё до деплоя.

### Удобен в CI:

* Собираете `Core\OpenApiDocument`,
* Сериализуете,
* Проверяете линтером/генератором клиентов,
* Публикуете артефакт.

---

## TL;DR

1. Установи пакет и профиль (3.1 или 3.2).
2. Вызови `ProfileProvider::setDefault(...)`.
3. Построй дерево через билдеры (`OpenApi`, `Info`, `Paths`, `Operation`, `Responses`, `Schema`, `Components`, …).
4. Вызови `->toModel()` и получи строгую модель ядра (`Core\OpenApiDocument`).
5. Сериализуй в JSON/YAML как тебе нужно.

Пакет под лицензией MIT.
