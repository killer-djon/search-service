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
     * @param string|null $searchText Текст поиска поста (поиск по тексту и интересу)
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function searchPostsByWallId(
        $userId,
        $wallId,
        $searchText = null,
        $skip = 0,
        $count = RequestConstant::DEFAULT_SEARCH_LIMIT
    ) {

        $userProfile = $this->getUserById($userId);

        if (!empty($searchText)) {
            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));

            if (count($slopPhrase) > 1) {
                // поиск по словосочетанию
                $this->setConditionQueryMust(PostSearchMapping::getSearchConditionQueryMust(
                    $this->_queryConditionFactory,
                    $searchText
                ));
            } else {
                $this->setConditionQueryShould(PostSearchMapping::getSearchConditionQueryShould(
                    $this->_queryConditionFactory,
                    $searchText
                ));
            }

            $this->setHighlightQuery(PostSearchMapping::getHighlightConditions());
        }

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_WALL_ID => $wallId]),
        ]);

        $canBeDeletedEdit = <<<JS
                var result = false;
if(doc['type'].value == 'post')
{   
    if(doc['author.id'].value == userId || doc['wallId'].value == wallId){
        result = true;
    }
}
                // return
                result;
JS;
        $this->setScriptFields([
            'canBeDeleted' => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            ),
            'canBeEdit'    => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            )
        ]);

        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(
                AbstractSearchMapping::CREATED_AT_FIELD,
                SortingOrder::SORTING_DESC
            )
        );

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PostSearchMapping::CONTEXT, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
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
        $userProfile = $this->getUserById($userId);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
        ]);

        $canBeDeletedEdit = <<<JS
                var result = false;
if(doc['type'].value == 'post')
{   
    if(doc['author.id'].value == userId || doc['wallId'].value == wallId){
        result = true;
    }
}
                // return
                result;
JS;

        $this->setScriptFields([
            'canBeDeleted' => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            ),
            'canBeEdit'    => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            )
        ]);

        return $this->searchRecordById(PostSearchMapping::CONTEXT, AbstractSearchMapping::IDENTIFIER_FIELD, $postId, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }

    /**
     * Получаем ленту новостей
     *
     * @param string $userId
     * @param array $eventTypes Типы возвращаемых новостей в ленту
     * @param string|null $searchText Поисковый запрос
     * @param array $friendIds Список id Друзей
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function searchUserEventsByUserId(
        $userId,
        $eventTypes,
        $searchText = null,
        $friendIds = [],
        $skip = 0,
        $count = null
    ) {
        $userProfile = $this->getUserById($userId);

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
                        $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                    ]),
                ]),
            ];
        }


        if (!empty($searchText)) {
            $this->setFilterQuery([
                $filter->getBoolOrFilter(array_merge([
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // получаекм посты друзей
                        $filter->getBoolAndFilter([
                            $filter->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                        ]),
                    ]),
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // получаекм свои посты
                        $filter->getBoolAndFilter([
                            $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId]),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                        ]),
                    ]),
                ], $rpPostsFilter))
            ]);

            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));
            $must = $should = [];

            if (count($slopPhrase) > 1) {

                /**
                 * Поиск по точному воспадению искомого словосочетания
                 */
                $queryMust = PostSearchMapping::getSearchConditionQueryMust($condition, $searchText);

                if (!empty($queryMust)) {
                    $this->setConditionQueryMust($queryMust);
                }

            } else {
                $queryShould = PostSearchMapping::getSearchConditionQueryShould(
                    $condition, $searchText
                );

                if (!empty($queryShould)) {
                    /**
                     * Ищем по частичному совпадению поисковой фразы
                     */
                    $this->setConditionQueryShould($queryShould);
                }
            }

            $this->setHighlightQuery(PostSearchMapping::getHighlightConditions());
        } else {

            $this->setFilterQuery([
                $filter->getBoolOrFilter(array_merge([
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // получаекм посты друзей
                        $filter->getBoolAndFilter([
                            $filter->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                        ]),
                    ]),
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // получаекм свои посты
                        $filter->getBoolAndFilter([
                            $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId]),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
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
        }


        $canBeDeletedEdit = <<<JS
                var result = false;
if(doc['type'].value == 'post')
{   
    if(doc['author.id'].value == userId || doc['wallId'].value == wallId){
        result = true;
    }
}
                // return
                result;
JS;
        $this->setScriptFields([
            'canBeDeleted' => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            ),
            'canBeEdit'    => $this->_scriptFactory->getScript(
                $canBeDeletedEdit,
                [
                    'userId' => $userId,
                    'wallId' => $userProfile->getWallId()
                ]
            )
        ]);

        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(
                AbstractSearchMapping::CREATED_AT_FIELD,
                SortingOrder::SORTING_DESC
            )
        );

        $query = $this->createQuery($skip, $count);

        $this->setIndices([
            PostSearchMapping::DEFAULT_INDEX
        ]);

        return $this->searchDocuments($query, null, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ], UserEventSearchMapping::CONTEXT);
    }

    /**
     * Выводим только посты по заданным критериям
     * данный метод необходим для группировки постов
     * под категории (например: путеводитель по риму)
     *
     * @param string $userId ID пользователя
     * @param string $rpUserId ID RP пользователя (от имени кого публиковался пост)
     * @param string|null $cityId ID города для которого выводим посты
     * @param string|null $searchText Строка поиска
     * @param string|null $categoryId ID категории постов (например: путеводитель)
     * @param int $skip
     * @param int $count
     *
     * @return array
     */
    public function getPostCategoriesByParams(
        $userId,
        $rpUserId = PeopleSearchMapping::RP_USER_ID,
        $categoryId = null,
        $cityId = null,
        $searchText = null,
        $skip = 0,
        $count = null
    ) {
        $userProfile = $this->getUserById($userId);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $rpUserId]),
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_DELETED => false])
            //$this->_queryFilterFactory->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
        ]);

        if (!empty($categoryId)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getExistsFilter(PostSearchMapping::POST_CATEGORIES_FIELD_ID),
                    $this->_queryFilterFactory->getTermsFilter(PostSearchMapping::POST_CATEGORIES_FIELD_ID,
                        [$categoryId])
                ])
            ]);
        }

        if (!empty($cityId)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getExistsFilter(PostSearchMapping::POST_CITY_FIELD_ID),
                    $this->_queryFilterFactory->getTermsFilter(PostSearchMapping::POST_CITY_FIELD_ID, [$cityId])
                ])
            ]);
        }

        if (!empty($searchText)) {
            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));

            if (count($slopPhrase) > 1) {
                // поиск по словосочетанию
                $this->setConditionQueryMust(PostSearchMapping::getSearchConditionQueryMust(
                    $this->_queryConditionFactory,
                    $searchText
                ));
            } else {
                $this->setConditionQueryShould(PostSearchMapping::getSearchConditionQueryShould(
                    $this->_queryConditionFactory,
                    $searchText
                ));
            }

            $this->setHighlightQuery(PostSearchMapping::getHighlightConditions());
        }

        $this->setScriptFields([
            'distance' => $this->_scriptFactory->getDistanceScript(
                PostSearchMapping::POST_CITY_POINT_FIELD,
                $userProfile->getLocation()
            )
        ]);

        $this->setSortingQuery([
            $this->_sortingFactory->getGeoDistanceSort(
                PostSearchMapping::POST_CITY_POINT_FIELD,
                $userProfile->getLocation(),
                SortingOrder::SORTING_ASC
            ),
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PostSearchMapping::CONTEXT, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }
}