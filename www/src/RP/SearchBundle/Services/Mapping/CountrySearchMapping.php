<?php

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

/**
 * Class CountrySearchMapping
 * @package RP\SearchBundle\Services\Mapping
 */
abstract class CountrySearchMapping extends AbstractSearchMapping
{

    /** Индекс еластика по умолчанию */
    const DEFAULT_INDEX = 'russianplace_private';

    /** Контекст поиска */
    const CONTEXT = 'country';

    /** Поле ID страны */
    const ID_FIELD = 'id';

    /** Поле названия страны */
    const NAME_FIELD = 'name';

    /** Маппинг поля имени страны для префиксного поиска */
    const TRANSLIT_NAME_FIELD = 'name._translit';

    /** Поле названия страны в международном формате */
    const INTERNATIONAL_NAME_FIELD = 'InternationalName';

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiMatchQuerySearchFields()
    {
        return [
            self::NAME_FIELD,
            self::TRANSLIT_NAME_FIELD,
            self::INTERNATIONAL_NAME_FIELD,
        ];
    }

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [];
    }

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [];
    }
}
