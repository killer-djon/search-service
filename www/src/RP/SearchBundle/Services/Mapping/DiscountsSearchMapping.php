<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 26.10.16
 * Time: 17:35
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class DiscountsSearchMapping extends PlaceSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'discounts';


    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiMatchQuerySearchFields()
    {
        return parent::getMultiMatchQuerySearchFields();
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
        return array_merge([
            $filterFactory->getBoolOrFilter([
                $filterFactory->getRangeFilter(PlaceSearchMapping::DISCOUNT_FIELD, 0, 101),
                $filterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
            ]),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getBoolAndFilter([
                    $filterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId]),
                    $filterFactory->getTermsFilter(PlaceSearchMapping::MODERATION_STATUS_FIELD, [
                        ModerationStatus::DIRTY,
                        ModerationStatus::OK
                    ]),
                ]),
                $filterFactory->getBoolAndFilter([
                    $filterFactory->getNotFilter(
                        $filterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId])
                    ),
                    $filterFactory->getTermFilter([
                        PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::OK
                    ])
                ]),
            ])
        ], AbstractSearchMapping::getVisibleConditions($filterFactory, $userId));
    }

    /**
     * Собираем фильтр для поиска c isFlat параметров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string $type Колекция для поиска
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getFlatMatchSearchFilter(FilterFactoryInterface $filterFactory, $type, $userId = null)
    {
        return array_merge(
            self::getMarkersSearchFilter($filterFactory, $userId),
            [$filterFactory->getTypeFilter($type)]
        );
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