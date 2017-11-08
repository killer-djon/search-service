<?php
/**
 * Сервис поиска в коллекции постов
 * т.е. поиск по ленте
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\NewsFeedSections;
use Common\Core\Constants\RequestConstant;
use Common\Core\Constants\SettingsNotifications;
use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\UserEventGroup;
use Common\Core\Constants\UserEventType;
use Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Exception\ElasticsearchException;
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

        $otherTypes = array_map(function ($type) {
            return $this->_queryConditionFactory->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
        }, $eventTypes[UserEventGroup::OTHERS]);


        $rpPostsFilter = [];
        if (in_array(PeopleSearchMapping::RP_USER_ID, $friendIds)) {
            // Посты от Друзей и самого ползователя
            $rpPostsFilter = [
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter([
                        $filter->getNestedFilter(
                            PostSearchMapping::AUTHOR_FIELD,
                            $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => PeopleSearchMapping::RP_USER_ID])
                        ),
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
                            $filter->getNestedFilter(
                                PostSearchMapping::AUTHOR_FIELD,
                                $filter->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                            ),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                        ]),
                    ]),
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // получаекм свои посты
                        $filter->getBoolAndFilter([
                            $filter->getNestedFilter(
                                PostSearchMapping::AUTHOR_FIELD,
                                $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId])
                            ),
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
                            $filter->getNestedFilter(
                                PostSearchMapping::AUTHOR_FIELD,
                                $filter->getTermsFilter(PostSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                            ),
                            $filter->getTermFilter([PostSearchMapping::POST_IS_DELETED => false]),
                            $filter->getNotFilter(
                                $filter->getNestedFilter(
                                    PostSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId])
                                )
                            )
                        ]),
                    ]),
                    // получаекм свои посты
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(PostSearchMapping::CONTEXT),
                        // Обязательно проверяем что тип поста возвращается
                        $filter->getQueryFilter(
                            $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, UserEventType::POST)
                        ),
                        $filter->getBoolAndFilter([
                            $filter->getNestedFilter(
                                PostSearchMapping::AUTHOR_FIELD,
                                $filter->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $userId])
                            ),
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
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                                ),
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                                ),
                            ]),
                            // только события направленные пользователю
                            $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_FIELD => $userId]),
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
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                                ),
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                                ),
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
                            $filter->getNestedFilter(
                                UserEventSearchMapping::AUTHOR_FIELD,
                                $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                            ),
                            // Не надо нам в ленте показывать событие добавления друга, которого мы сами и добавили
                            $filter->getNotFilter(
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                                )
                            ),
                        ]),
                    ]),
                    // Others - сейчас события от других приходят только в уведомления
                    $filter->getBoolAndFilter([
                        $filter->getTypeFilter(UserEventSearchMapping::CONTEXT),
                        $filter->getBoolAndFilter([
                            // события по типу
                            $filter->getQueryFilter(
                                $condition->getDisMaxQuery($otherTypes)
                            ),
                            // Авторы - не друзья ползователя
                            $filter->getNotFilter(
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                                )
                            ),
                            // Не надо нам в ленте показывать событие добавления друга, которого мы сами и добавили
                            $filter->getNotFilter(
                                $filter->getNestedFilter(
                                    UserEventSearchMapping::AUTHOR_FIELD,
                                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                                )
                            ),
                            $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_FIELD => $userId]),
                        ])
                    ])
                ], $rpPostsFilter)),
            ]);
        }

        $this->setFilterQuery([
            $filter->getBoolAndFilter([
                $filter->getNotFilter(
                    $filter->getNestedFilter(
                        UserEventSearchMapping::AUTHOR_FIELD,
                        $filter->getTermsFilter(
                            UserEventSearchMapping::AUTHOR_ID_FIELD,
                            $userProfile->getBlockedUsers()
                        )
                    )
                ),
                $filter->getNotFilter(
                    $filter->getNestedFilter(
                        PostSearchMapping::AUTHOR_FIELD,
                        $filter->getTermsFilter(
                            PostSearchMapping::AUTHOR_ID_FIELD,
                            $userProfile->getBlockedUsers()
                        )
                    )
                )
            ])
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
            $this->_queryFilterFactory->getNestedFilter(
                PostSearchMapping::AUTHOR_FIELD,
                $this->_queryFilterFactory->getTermFilter([PostSearchMapping::AUTHOR_ID_FIELD => $rpUserId])
            ),
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_DELETED => false])
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
        $this->setIndices([
            PostSearchMapping::DEFAULT_INDEX
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PostSearchMapping::CONTEXT, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }


    /**
     * Получить список уведомелний для ползователя
     * по заданным критериями в параметре types
     *
     * @param string $userId Пользователь который запрашивает данные
     * @param array $eventTypes Типы возвращаемых уведомлений
     * @param array $friendsId Список ID друзей пользвателя
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function getNewsFeedNotifications(
        $userId,
        array $eventTypes,
        $skip = 0,
        $count = RequestConstant::DEFAULT_SEARCH_LIMIT
    ) {
        $userProfile = $this->getUserById($userId);
        $userSettings = $userProfile->getUserSettings(NewsFeedSections::FEED_NOTIFICATIONS);

        $settingNotifications = [];
        if (!empty($userSettings)) {
            foreach ($eventTypes as $keyType => $type) {
                // проверяем включена ли опция для каждой из позиций
                $settingNotifications[$keyType] = $this->filterByAccess($userSettings, $type,
                    SettingsNotifications::SHOW);
            }
        } else {
            $settingNotifications = $eventTypes;
        }


        $filter = $this->_queryFilterFactory;
        $condition = $this->_queryConditionFactory;

        $personalTypes = $friendTypes = $otherTypes = $authorTypes = [];

        // определяем есть ли возможность смотреть Personal типы событий
        if (!empty($settingNotifications[UserEventGroup::PERSONAL])) {
            $queryTypes = array_map(function ($type) use ($condition) {
                return $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
            }, $settingNotifications[UserEventGroup::PERSONAL]);

            $personalTypes = [
                // Personal - события направленные непосредственно пользователю
                $filter->getBoolAndFilter([
                    // получаекм свои посты
                    $filter->getQueryFilter(
                        $condition->getDisMaxQuery(
                            $queryTypes
                        )
                    ),
                    // Только события, направленные пользователю
                    $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_FIELD => $userId]),
                    // Автором события не является сам пользователь
                    $filter->getNotFilter(
                        $filter->getNestedFilter(
                            UserEventSearchMapping::AUTHOR_FIELD,
                            $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                        )
                    )
                ])
            ];
        }

        // определяем есть ли возможность смотреть Friends типы событий
        if (!empty($settingNotifications[UserEventGroup::FRIENDS])) {
            $queryTypes = array_map(function ($type) use ($condition) {
                return $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
            }, $settingNotifications[UserEventGroup::FRIENDS]);

            $friendTypes = [
                // Friends - события от друзей
                $filter->getBoolAndFilter([
                    // События от друзей
                    $filter->getQueryFilter(
                        $condition->getDisMaxQuery(
                            $queryTypes
                        )
                    ),
                    // Событие свежее для пользователя
                    $filter->getGtFilter(UserEventSearchMapping::CREATED_AT_FIELD,
                        $userProfile->getRegistrationDate()->format('Y-m-d')),
                    // Список друзей и преследуемых
                    $filter->getNestedFilter(
                        UserEventSearchMapping::AUTHOR_FIELD,
                        $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                    )
                ])
            ];
        }

        // определяем есть ли возможность смотреть Other типы событий
        if (!empty($settingNotifications[UserEventGroup::OTHERS])) {
            $queryTypes = array_map(function ($type) use ($condition) {
                return $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
            }, $settingNotifications[UserEventGroup::OTHERS]);

            $otherTypes = [
                // Others - события сгенеренные пользователями, не входящих в список друзей пользователя
                $filter->getBoolAndFilter([
                    // События от других пользователей которые можно смотреть
                    $filter->getQueryFilter(
                        $condition->getDisMaxQuery(
                            $queryTypes
                        )
                    ),
                    // Событие свежее для пользователя
                    $filter->getGtFilter(UserEventSearchMapping::CREATED_AT_FIELD,
                        $userProfile->getRegistrationDate()->format('Y-m-d')),
                    // Только события, направленные пользователю
                    $filter->getTermFilter([UserEventSearchMapping::RECEIVER_USER_FIELD => $userId]),
                    // Исключаем события друзей и преследуемых
                    $filter->getNotFilter(
                        $filter->getNestedFilter(
                            UserEventSearchMapping::AUTHOR_FIELD,
                            $filter->getTermsFilter(UserEventSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                        )
                    ),
                    // Автором события не является сам пользователь
                    $filter->getNotFilter(
                        $filter->getNestedFilter(
                            UserEventSearchMapping::AUTHOR_FIELD,
                            $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                        )
                    )
                ])
            ];
        }

        $authorQueryTypes = array_map(function ($type) use ($condition) {
            return $condition->getMatchQuery(UserEventSearchMapping::TYPE_FIELD, $type);
        }, $settingNotifications[UserEventGroup::AUTHOR]);

        // Типы событий AUTHOR, их мы должны видеть всегда
        $authorTypes = [
            // Author - события, автором которых является сам пользователь, но оно должно быть в его уведомлениях
            $filter->getBoolAndFilter([
                // Событие из группы author
                $filter->getQueryFilter(
                    $condition->getDisMaxQuery(
                        $authorQueryTypes
                    )
                ),
                // Автором события является сам пользователь
                $filter->getNestedFilter(
                    UserEventSearchMapping::AUTHOR_FIELD,
                    $filter->getTermFilter([UserEventSearchMapping::AUTHOR_ID_FIELD => $userId])
                )
            ])
        ];

        // Или-или
        $this->setFilterQuery([
            $filter->getNotFilter(
                $filter->getNestedFilter(
                    UserEventSearchMapping::AUTHOR_FIELD,
                    $filter->getTermsFilter(
                        UserEventSearchMapping::AUTHOR_ID_FIELD,
                        $userProfile->getBlockedUsers()
                    )
                )
            ),
            $filter->getBoolOrFilter(array_merge(
                $personalTypes,
                $friendTypes,
                $otherTypes,
                $authorTypes
            ))
        ]);

        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(
                AbstractSearchMapping::CREATED_AT_FIELD,
                SortingOrder::SORTING_DESC
            )
        );

        $this->setIndices([
            UserEventSearchMapping::DEFAULT_INDEX
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, UserEventSearchMapping::CONTEXT, [
            'excludes' => ['friendList', 'relations', '*.friendList', 'receiver']
        ]);

    }

    /**
     * Фильтрует произвольный массив с типами нотификаций в зависимости от пользовательских настроек
     *
     * @param array $userSettings Пользовательские настройки
     * @param array $notificationTypes
     * @param string $accessType Тип доступности пункта уведомлений
     * @return array
     */
    public function filterByAccess(array $userSettings, array $notificationTypes, $accessType)
    {
        // Если настройки выключены целиком
        if (SettingsNotifications::getAll() == SettingsNotifications::NONE) {
            return [];
        }

        if (!in_array($accessType, SettingsNotifications::$accessTypes)) {
            return [];
        }

        $resultSettings = [];
        $intersectKeyTypes = array_diff_assoc($userSettings,
            array_diff_key($userSettings, array_flip($notificationTypes)));
        if (!empty($intersectKeyTypes)) {
            foreach ($intersectKeyTypes as $keyType => $settingValue) {
                $showingValues = explode(',', $settingValue);
                if (in_array($accessType, $showingValues) && SettingsNotifications::checkSettingsValue("_{$keyType}")) {
                    $resultSettings[] = $keyType;
                }
            }
        }

        return $resultSettings;
    }
}