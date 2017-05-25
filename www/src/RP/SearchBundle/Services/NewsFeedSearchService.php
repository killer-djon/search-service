<?php
/**
 * Сервис поиска в коллекции постов
 * т.е. поиск по ленте
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\RequestConstant;
use Common\Core\Constants\UserEventGroup;
use Common\Core\Constants\UserEventType;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Query\FunctionScore;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\AbstractSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PostSearchMapping;
use RP\SearchBundle\Services\Mapping\UserEventSearchMapping;

/**
 * Class NewsFeedSearchService
 *
 * @package RP\SearchBundle\Services
 */
class NewsFeedSearchService extends AbstractSearchService
{
    /**
     * ПОиск всех постов пользователя по ID ленты
     *
     * @param string $userId ID пользователя
     * @param string $wallId ID ленты
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function searchPostsByWallId($userId, $wallId, $skip = 0, $count = RequestConstant::DEFAULT_SEARCH_LIMIT)
    {
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PostSearchMapping::POST_WALL_ID, $wallId),
        ]);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
        ]);

        $this->setSortingQuery($this->_sortingFactory->getFieldSort(AbstractSearchMapping::CREATED_AT_FIELD, 'desc'));

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PostSearchMapping::CONTEXT);
    }

    /**
     * Получаем объекта поста по его ID
     *
     * @param string $userId ID пользователя
     * @param string $postId ID поста
     * @return array
     */
    public function searchPostById($userId, $postId)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
        ]);

        return $this->searchRecordById(PostSearchMapping::CONTEXT, AbstractSearchMapping::IDENTIFIER_FIELD, $postId);
    }

    /**
     * Получаем ленту новостей
     * @param string $userId
     * @param array $eventTypes Типы возвращаемых новостей в ленту
     * @param array $friendIds Список id Друзей
     * @return array
     */
    public function searchUserEventsByUserId($userId, $eventTypes, $friendIds)
    {
        /** @var FilterFactoryInterface */
        $filter = $this->_queryFilterFactory;

        $friendIdsNoRP = array_diff($friendIds, [PeopleSearchMapping::RP_USER_ID]);

        $this->setFilterQuery([
            $filter->getTermFilter([UserEventSearchMapping::IS_REMOVED_FIELD => false]),
            $filter->getBoolOrFilter([
                // Personal (читай подробный комментарий в getUserEventsGroups)
                $filter->getBoolAndFilter([
                    // Событие не удалено
                    $filter->getTermsFilter(UserEventSearchMapping::TYPE_FIELD, $eventTypes[UserEventGroup::PERSONAL]),
                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_ID_FIELD, $friendIdsNoRP),
                    $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_ID_FIELD => $userId])
                ]),
                // Friends и сам пользователь
                $filter->getBoolAndFilter([
                    $filter->getTermsFilter(UserEventSearchMapping::TYPE_FIELD, $eventTypes[UserEventGroup::FRIENDS]),
                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_ID_FIELD, $friendIdsNoRP),
                ]),
                // События от друзей о новых друзьях
                $filter->getBoolAndFilter([
                    $filter->getTermFilter([UserEventSearchMapping::TYPE_FIELD => UserEventType::NEW_FRIEND]),
                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_ID_FIELD, $friendIdsNoRP),
                    $filter->getNotFilter(
                        $filter->getTermsFilter(PeopleSearchMapping::FRIEND_LIST_FIELD, [$userId])
                    )
                ])
            ])
        ]);

        if( in_array(PeopleSearchMapping::RP_USER_ID, $friendIds) )
        {
            // Посты от RussianPlace если он есть в друзьях
            $this->setFilterQuery([
                $filter->getBoolAndFilter([
                    $filter->getTermFilter([UserEventSearchMapping::TYPE_FIELD => UserEventType::POST]),
                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => PeopleSearchMapping::RP_USER_ID]),
                ])
            ]);
        }

        $query = $this->createQuery();

        return $this->searchDocuments($query, UserEventSearchMapping::CONTEXT);
    }
}