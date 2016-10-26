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

class DiscountsSearchMapping extends PlaceSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'discounts';

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [
            $filterFactory->getBoolAndFilter([
                $filterFactory->getBoolOrFilter([
                    $filterFactory->getRangeFilter(self::DISCOUNT_FIELD, 1, 100),
                    $filterFactory->getExistsFilter(self::BONUS_FIELD),
                ]),
                $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::OK])
            ])
        ];
    }
}