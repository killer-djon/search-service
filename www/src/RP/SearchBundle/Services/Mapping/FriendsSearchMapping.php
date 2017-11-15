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
            $filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(
                    self::IDENTIFIER_FIELD,
                    self::$userProfile->getBlockedUsers()
                )
            ),
            $filterFactory->getTermsFilter(self::FRIEND_LIST_FIELD, [$userId]),
            $filterFactory->getTermFilter([self::USER_REMOVED_FIELD => false]),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getNotFilter(
                    $filterFactory->getExistsFilter(self::SETTINGS_PRIVACY_VIEW_GEO_POSITION)
                ),
                $filterFactory->getTermFilter([self::SETTINGS_PRIVACY_VIEW_GEO_POSITION => self::SETTINGS_YES]),
            ]),
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
        return [
            $filterFactory->getTermsFilter(self::FRIEND_LIST_FIELD, [$userId]),
            $filterFactory->getTermFilter([self::USER_REMOVED_FIELD => false]),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getNotFilter(
                    $filterFactory->getExistsFilter(self::SETTINGS_PRIVACY_VIEW_GEO_POSITION)
                ),
                $filterFactory->getTermFilter([self::SETTINGS_PRIVACY_VIEW_GEO_POSITION => self::SETTINGS_YES]),
            ]),
        ];
    }
}