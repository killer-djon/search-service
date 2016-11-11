<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 11.11.16
 * Time: 17:40
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

class FriendsSearchMapping extends PeopleSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'friends';

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
            $filterFactory->getTermsFilter(self::FRIEND_LIST_FIELD, [$userId])
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
        return [];
    }
}