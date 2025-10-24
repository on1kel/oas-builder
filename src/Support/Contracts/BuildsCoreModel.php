<?php

declare(strict_types=1);

namespace On1kel\OAS\Builder\Support\Contracts;

/**
 * Узел билдера, способный собирать неизменяемую core-модель OAS.
 */
interface BuildsCoreModel
{
    /**
     * Построить соответствующую модель из on1kel/oas-core.
     *
     * @return object Экземпляр класса пространства имён On1kel\OAS\Core\Model\*
     */
    public function toModel(): object;
}
