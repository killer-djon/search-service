<?php
/**
 * Базовый абстрактный класс мапперов поиска
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class AbstractSearchMapping
{
    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    abstract public static function getMultiMatchQuerySearchFields();

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    abstract public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null);
}