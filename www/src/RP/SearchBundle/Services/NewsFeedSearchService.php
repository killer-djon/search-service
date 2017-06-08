<?php
/**
 * Сервис поиска в коллекции постов
 * т.е. поиск по ленте
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\RequestConstant;
use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\UserEventGroup;
use Common\Core\Constants\UserEventType;
use Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface;
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
     *
     * @param string $userId
     * @param array $eventTypes Типы возвращаемых новостей в ленту
     * @param array $friendIds Список id Друзей
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function searchUserEventsByUserId($userId, $eventTypes, $friendIds, $skip = 0, $count = null)
    {
        /** @var FilterFactoryInterface */
        $filter = $this->_queryFilterFactory;
        /** @var QueryFactoryInterface */
        $condition = $this->_queryConditionFactory;

        $personalType = array_map(function ($type) {
            return $this->_queryConditionFactory->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
        }, $eventTypes[UserEventGroup::PERSONAL]);

        $friendsType = array_map(function ($type) {
            return $this->_queryConditionFactory->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
        }, $eventTypes[UserEventGroup::FRIENDS]);

        $rpPostsFilter = [];
        if (in_array(PeopleSearchMapping::RP_USER_ID, $friendIds)) {
            // Посты от Друзей и самого ползователя
            $rpPostsFilter = [
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter([
                        $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => PeopleSearchMapping::RP_USER_ID]),
                    ]),
                ]),
            ];
        }

        $this->setFilterQuery([
            $filter->getBoolOrFilter(array_merge([
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                    // получаекм посты друзей
                    $filter->getBoolAndFilter([
                        $filter->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                    ]),
                ]),
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                    // получаекм свои посты
                    $filter->getBoolAndFilter([
                        $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId]),
                    ]),
                ]),
                // Personal (читай подробный комментарий в getUserEventsGroups)
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(UserEventSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter([
                        // события по типу
                        $filter->getQueryFilter(
                            $condition->getDisMaxQuery($personalType)
                        ),
                        // Только события от друзей и самого пользователя
                        $filter->getBoolOrFilter([
                            $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                            $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId]),
                        ]),
                        // только события направленные пользователю
                        $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_ID_FIELD => $userId]),
                    ]),
                ]),
                // Friends и сам пользователь
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(UserEventSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter([
                        // события по типу
                        $filter->getQueryFilter(
                            $condition->getDisMaxQuery($friendsType)
                        ),
                        // Только события от друзей и самого пользователя
                        $filter->getBoolOrFilter([
                            $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                            $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId]),
                        ]),
                    ]),
                ]),
                // События от друзей о новых друзьях
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(UserEventSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter([
                        $filter->getQueryFilter(
                            $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, UserEventType::NEW_FRIEND)
                        ),
                        // Авторы - друзья ползователя
                        $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                        // Не надо нам в ленте показывать событие добавления друга, которого мы сами и добавили
                        $filter->getNotFilter(
                            $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                        ),
                    ]),
                ]),
            ], $rpPostsFilter)),
        ]);

        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(AbstractSearchMapping::CREATED_AT_FIELD, SortingOrder::SORTING_DESC)
        );

        $this->setFlatFormatResult(true);
        $query = $this->createQuery($skip, $count);

        //print_r($query); die();

        return $this->searchDocuments($query, [UserEventSearchMapping::CONTEXT, PostSearchMapping::CONTEXT]);
    }
}