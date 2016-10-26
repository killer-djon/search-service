<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 26.10.16
 * Time: 16:13
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

class HelpOffersSearchMapping extends PeopleSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'helpOffers';

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
            $filterFactory->getExistsFilter(self::HELP_OFFERS_LIST_FIELD)
        ];
    }
}