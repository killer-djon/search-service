<?php
/**
 * Маппинг класса поиска русских мест
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class RusPlaceSearchMapping extends PlaceSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'rusplaces';

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return array_merge(self::getMatchSearchFilter($filterFactory, $userId), [
            $filterFactory->getTermFilter([self::IS_RUSSIAN_FIELD => true])
        ]);
    }
}