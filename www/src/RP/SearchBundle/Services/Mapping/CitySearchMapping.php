<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 14.11.16
 * Time: 16:36
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class CitySearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'city';

    const ID_FIELD = 'id';

    /** Поле названия города */
    const NAME_FIELD = 'Name';

    /** Поле названия страны */
    const COUNTRY_FIELD = 'Country';

    /** Маппинг поля имени города для префиксного поиска */
    const TRANSLIT_NAME_FIELD = 'Name._translit';

    /** Поле названия города в международном формате */
    const INTERNATIONAL_NAME_FIELD = 'InternationalName';

    /** Центр города */
    const CENTER_CITY_POINT_FIELD = 'CenterPoint';

    /** Тип населенного пункта */
    const CITY_TYPE_FIELD = 'Type';

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
            self::INTERNATIONAL_NAME_FIELD,
            self::TRANSLIT_NAME_FIELD
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