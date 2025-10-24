<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\CoreBridge;

use On1kel\OAS\Builder\Support\Contracts\BuildsCoreModel;
use On1kel\OAS\Core\Model as Core;
use On1kel\OAS\Core\Model\Collections\Map\MediaTypeMap;
use On1kel\OAS\Core\Model\Collections\Map\ResponseMap;
use On1kel\OAS\Core\Model\Collections\Map\SchemaMap;

/**
 * Набор статических методов для «приведения» значений в core-модели:
 * принимает либо билдер (реализующий BuildsCoreModel), либо уже готовую core-модель,
 * либо Core\Reference (если допустимо). Возвращает core-тип.
 *
 * Это позволяет в билдере принимать объединённые типы без дублирования instanceof-проверок.
 */
final class Coerce
{
    /** @template T of object
     * @param  BuildsCoreModel|T $value
     * @return T
     */
    public static function model(object $value): object
    {
        if ($value instanceof BuildsCoreModel) {
            /** @var object */
            return $value->toModel();
        }

        return $value; // предполагается корректный core-тип
    }

    /** @param Core\Schema|Core\Reference|BuildsCoreModel $value */
    public static function schemaOrRef(Core\Schema|Core\Reference|BuildsCoreModel $value): Core\Schema|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Schema || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Schema|Reference', $m);
    }

    /** @param Core\Response|Core\Reference|BuildsCoreModel $value */
    public static function responseOrRef(Core\Response|Core\Reference|BuildsCoreModel $value): Core\Response|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Response || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Response|Reference', $m);
    }

    /** @param Core\Parameter|Core\Reference|BuildsCoreModel $value */
    public static function parameterOrRef(Core\Parameter|Core\Reference|BuildsCoreModel $value): Core\Parameter|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Parameter || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Parameter|Reference', $m);
    }

    /** @param Core\RequestBody|Core\Reference|BuildsCoreModel $value */
    public static function requestBodyOrRef(Core\RequestBody|Core\Reference|BuildsCoreModel $value): Core\RequestBody|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\RequestBody || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('RequestBody|Reference', $m);
    }

    /** @param Core\Header|Core\Reference|BuildsCoreModel $value */
    public static function headerOrRef(Core\Header|Core\Reference|BuildsCoreModel $value): Core\Header|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Header || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Header|Reference', $m);
    }

    /** @param Core\Link|Core\Reference|BuildsCoreModel $value */
    public static function linkOrRef(Core\Link|Core\Reference|BuildsCoreModel $value): Core\Link|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Link || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Link|Reference', $m);
    }

    /** @param Core\Encoding|Core\Reference|BuildsCoreModel $value */
    public static function encodingOrRef(Core\Encoding|Core\Reference|BuildsCoreModel $value): Core\Encoding|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\Encoding || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('Encoding|Reference', $m);
    }

    /** @param Core\MediaType|BuildsCoreModel $value */
    public static function mediaType(Core\MediaType|BuildsCoreModel $value): Core\MediaType
    {
        $m = self::model($value);
        if ($m instanceof Core\MediaType) {
            return $m;
        }
        self::failType('MediaType', $m);
    }

    /** @param Core\PathItem|Core\Reference|BuildsCoreModel $value */
    public static function pathItemOrRef(Core\PathItem|Core\Reference|BuildsCoreModel $value): Core\PathItem|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\PathItem || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('PathItem|Reference', $m);
    }

    /** @param Core\SecurityScheme|Core\Reference|BuildsCoreModel $value */
    public static function securitySchemeOrRef(Core\SecurityScheme|Core\Reference|BuildsCoreModel $value): Core\SecurityScheme|Core\Reference
    {
        $m = self::model($value);
        if ($m instanceof Core\SecurityScheme || $m instanceof Core\Reference) {
            return $m;
        }
        self::failType('SecurityScheme|Reference', $m);
    }

    /** @param Core\Operation|BuildsCoreModel $value */
    public static function operation(Core\Operation|BuildsCoreModel $value): Core\Operation
    {
        $m = self::model($value);
        if ($m instanceof Core\Operation) {
            return $m;
        }
        self::failType('Operation', $m);
    }

    /** @param Core\Components|BuildsCoreModel $value */
    public static function components(Core\Components|BuildsCoreModel $value): Core\Components
    {
        $m = self::model($value);
        if ($m instanceof Core\Components) {
            return $m;
        }
        self::failType('Components', $m);
    }

    /** @param Core\Paths|BuildsCoreModel $value */
    public static function paths(Core\Paths|BuildsCoreModel $value): Core\Paths
    {
        $m = self::model($value);
        if ($m instanceof Core\Paths) {
            return $m;
        }
        self::failType('Paths', $m);
    }

    /** @param ResponseMap|BuildsCoreModel $value */
    public static function responseMap(Core\Collections\Map\ResponseMap|BuildsCoreModel $value): Core\Collections\Map\ResponseMap
    {
        $m = self::model($value);
        if ($m instanceof Core\Collections\Map\ResponseMap) {
            return $m;
        }
        self::failType('ResponseMap', $m);
    }

    /** @param MediaTypeMap|BuildsCoreModel $value */
    public static function mediaTypeMap(Core\Collections\Map\MediaTypeMap|BuildsCoreModel $value): Core\Collections\Map\MediaTypeMap
    {
        $m = self::model($value);
        if ($m instanceof Core\Collections\Map\MediaTypeMap) {
            return $m;
        }
        self::failType('MediaTypeMap', $m);
    }

    /** @param SchemaMap|BuildsCoreModel $value */
    public static function schemaMap(Core\Collections\Map\SchemaMap|BuildsCoreModel $value): Core\Collections\Map\SchemaMap
    {
        $m = self::model($value);
        if ($m instanceof Core\Collections\Map\SchemaMap) {
            return $m;
        }
        self::failType('SchemaMap', $m);
    }

    // -- private -----------------------------------------------------------

    /** @return never */
    private static function failType(string $expected, object $value): void
    {
        $got = $value::class;
        throw new \InvalidArgumentException("Ожидается {$expected}, получено {$got}");
    }
}
