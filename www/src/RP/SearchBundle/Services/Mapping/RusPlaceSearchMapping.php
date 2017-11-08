<?php
/**
 * Маппинг класса поиска русских мест
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class RusPlaceSearchMapping extends PlaceSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'rusPlaces';

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [
            $filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(
                    self::AUTHOR_ID_FIELD,
                    self::$userProfile->getBlockedUsers()
                )
            ),
            $filterFactory->getTermFilter([parent::IS_RUSSIAN_FIELD => true]),
            $filterFactory->getTermFilter([parent::DISCOUNT_FIELD => 0]),
            $filterFactory->getNotFilter(
                $filterFactory->getExistsFilter(parent::BONUS_FIELD)
            ),
            AbstractSearchMapping::getVisibleCondition($filterFactory, $userId),
            AbstractSearchMapping::getModerateCondition($filterFactory, $userId),
        ];
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
        return self::getMarkersSearchFilter($filterFactory, $userId);
    }

}