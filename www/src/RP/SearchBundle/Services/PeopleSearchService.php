<?php
/**
 * Сервис поиска в коллекции пользователей
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use Elastica\Query\FunctionScore;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\HelpOffersSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use Common\Core\Facade\Service\Geo\GeoPointService;

class PeopleSearchService extends AbstractSearchService
{

    /**
     * Минимальное значение скора (вес найденного результата)
     *
     * @const string MIN_SCORE_SEARCH
     */
    const MIN_SCORE_SEARCH = '3';

    /**
     * Радиус по возможным друзьям
     * у которых максимальное значение
     * совпадение по интересам ( 50% - 100% )
     *
     * @const string DEFAULT_POSSIBLE_FRIENDS_RADIUS_MIN
     */
    const DEFAULT_POSSIBLE_FRIENDS_RADIUS_MIN = 10000;

    /**
     * Радиус по возможным друзьям
     * c остальным значением совпаденя по интересам
     *
     * @const string DEFAULT_POSSIBLE_FRIENDS_RADIUS_MAX
     */
    const DEFAULT_POSSIBLE_FRIENDS_RADIUS_MAX = 1000000;

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        /** формируем условия сортировки */
        $this->setSortingQuery(
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            )
        );

        /** Получаем сформированный объект запроса */
        /*$this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setQuery($searchText)
                                         ->setFields(PeopleSearchMapping::getMultiMatchQuerySearchFields()),
            $this->_queryConditionFactory->getWildCardQuery(
                PeopleSearchMapping::FULLNAME_MORPHOLOGY_FIELD,
                "*{$searchText}*"
            ),
        ]);*/
        $searchText = mb_strtolower($searchText);
        $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

        $slopPhrase = array_filter(explode(" ", $searchText));
        $queryShouldFields = $must = $should = [];

        if (count($slopPhrase) > 1) {

            /**
             * Поиск по точному воспадению искомого словосочетания
             */
            $this->setConditionQueryMust([
                $this->_queryConditionFactory
                    ->getFieldQuery(PeopleSearchMapping::getMultiMatchQuerySearchFields(), $searchText)
                    ->setDefaultOperator(MultiMatch::OPERATOR_AND),
            ]);

        } else {
            /**
             * Ищем по частичному совпадению поисковой фразы
             */
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setFields(PeopleSearchMapping::getMultiMatchQuerySearchFields())
                                             ->setQuery($searchText)
                                             ->setOperator(MultiMatch::OPERATOR_OR)
                                             ->setType(MultiMatch::TYPE_BEST_FIELDS),
            ]);
        }

        // Получаем сформированный объект запроса
        $queryMatchResult = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, PeopleSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск людей
     * в заданном городе по ID
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $cityId ID города поиска
     * @param GeoPointServiceInterface $point
     * @param string|null $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByCityId($userId, $cityId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** формируем условия сортировки */
        $this->setSortingQuery(
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            )
        );

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    PeopleSearchMapping::LOCATION_CITY_ID_FIELD => $cityId,
                ]),
            ]);

            /** Получаем сформированный объект запроса */
            $query = $this->createMatchQuery(
                $searchText,
                PeopleSearchMapping::getMultiMatchQuerySearchFields(),
                $skip,
                $count
            );
        } else {
            /** задаем условия поиска по городу */
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getTermQuery(PeopleSearchMapping::LOCATION_CITY_ID_FIELD, $cityId),
            ]);

            /** Получаем сформированный объект запроса */
            $query = $this->createQuery($skip, $count);
        }

        return $this->searchDocuments($query, PeopleSearchMapping::CONTEXT);
    }


    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя находяхищся в друзьях
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $targetUserId ID профиля
     * @param array $filters фильтры запроса (friends,commonFriends)
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleFriends(
        $userId,
        $targetUserId,
        array $filters,
        GeoPointServiceInterface $point,
        $searchText = null,
        $skip = 0,
        $count = null
    ) {
        $queryMatchResults = [];

        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** получаем объект профиля пользователя */
        $targetUser = ($userId == $targetUserId ? $currentUser : $this->getUserById($targetUserId));
        $filtersKey = array_flip($filters);

        if ($userId == $targetUserId && isset($filtersKey[PeopleSearchMapping::COMMON_FRIENDS_FILTER])) {
            unset($filtersKey[PeopleSearchMapping::COMMON_FRIENDS_FILTER]);
            $key = array_search(PeopleSearchMapping::COMMON_FRIENDS_FILTER, $filters);
            if (is_int($key)) {
                unset($filters[$key]);
            }

        }
        $friendsFilter = [];

        if ($userId != $targetUserId) {
            if (isset($filtersKey[PeopleSearchMapping::COMMON_FRIENDS_FILTER])) {
                $friendsFilter[PeopleSearchMapping::COMMON_FRIENDS_FILTER] = [
                    $this->_queryFilterFactory->getTermsFilter(
                        PeopleSearchMapping::FRIEND_LIST_FIELD,
                        [$userId]
                    ),
                    $this->_queryFilterFactory->getTermsFilter(
                        PeopleSearchMapping::FRIEND_LIST_FIELD,
                        [$targetUserId]
                    ),
                ];
            }

            if (isset($filtersKey[PeopleSearchMapping::FRIENDS_FILTER])) {
                if (isset($filtersKey[PeopleSearchMapping::COMMON_FRIENDS_FILTER])) {
                    $friendsFilter[PeopleSearchMapping::FRIENDS_FILTER] = [
                        $this->_queryFilterFactory->getNotFilter(
                            $this->_queryFilterFactory->getTermsFilter(
                                PeopleSearchMapping::FRIEND_LIST_FIELD,
                                [$userId]
                            )
                        ),
                        $this->_queryFilterFactory->getTermsFilter(
                            PeopleSearchMapping::FRIEND_LIST_FIELD,
                            [$targetUserId]
                        ),
                    ];
                } else {
                    $friendsFilter[PeopleSearchMapping::FRIENDS_FILTER] = [
                        $this->_queryFilterFactory->getTermsFilter(
                            PeopleSearchMapping::FRIEND_LIST_FIELD,
                            [$targetUserId]
                        ),
                    ];
                }
            }
        } else {
            $friendsFilter[PeopleSearchMapping::FRIENDS_FILTER] = [
                $this->_queryFilterFactory->getTermsFilter(
                    PeopleSearchMapping::FRIEND_LIST_FIELD,
                    [$targetUserId]
                ),
            ];
        }

        foreach ($filters as $filter) {
            $this->clearQueryFactory();

            /** добавляем к условию поиска рассчет по совпадению интересов */
            $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

            /** добавляем к условию поиска рассчет расстояния */
            $this->setGeoPointConditions($point, PeopleSearchMapping::class);

            if ($filter == PeopleSearchMapping::COMMON_FRIENDS_FILTER && isset($friendsFilter[PeopleSearchMapping::COMMON_FRIENDS_FILTER])) {
                $this->setFilterQuery($friendsFilter[PeopleSearchMapping::COMMON_FRIENDS_FILTER]);
            }

            if ($filter == PeopleSearchMapping::FRIENDS_FILTER && isset($friendsFilter[PeopleSearchMapping::FRIENDS_FILTER])) {
                $this->setFilterQuery($friendsFilter[PeopleSearchMapping::FRIENDS_FILTER]);
            }

            if ($point->isValid()) {
                /** формируем условия сортировки */
                $this->setSortingQuery(
                    $this->_sortingFactory->getGeoDistanceSort(
                        PeopleSearchMapping::LOCATION_POINT_FIELD,
                        $point
                    )
                );
            }

            $queryMatchResults[$filter] = $this->createMatchQuery(null, [], $skip, $count);

        }

        return $this->searchMultiTypeDocuments($queryMatchResults);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя находяхищся в друзьях
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function oldSearchPeopleFriends($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(
                PeopleSearchMapping::FRIEND_LIST_FIELD,
                [$userId]
            ),
        ]);

        if ($point->isValid()) {
            /** формируем условия сортировки */
            $this->setSortingQuery(
                $this->_sortingFactory->getGeoDistanceSort(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            );
        }

        if (!is_null($searchText) && !empty($searchText)) {

            /** Получаем сформированный объект запроса */
            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));

            $must = $should = [];

            if (count($slopPhrase) > 1) {

                /**
                 * Поиск по точному воспадению искомого словосочетания
                 */
                $this->setConditionQueryMust([
                    $this->_queryConditionFactory
                        ->getFieldQuery(PeopleSearchMapping::getMultiMatchQuerySearchFields(), $searchText)
                        ->setDefaultOperator(MultiMatch::OPERATOR_AND),
                ]);

            } else {

                $prefixWildCardByName = [];

                foreach (PeopleSearchMapping::getMultiMatchQuerySearchFields() as $field) {
                    $prefixWildCardByName[] = $this->_queryConditionFactory->getPrefixQuery($field, $searchText, 0.5);
                }

                $this->setConditionQueryShould([
                    $this->_queryConditionFactory->getMultiMatchQuery()
                                                 ->setFields(PeopleSearchMapping::getMultiMatchQuerySearchFields())
                                                 ->setQuery($searchText)
                                                 ->setOperator(MultiMatch::OPERATOR_OR)
                                                 ->setType(MultiMatch::TYPE_BEST_FIELDS),
                    $this->_queryConditionFactory->getBoolQuery([], $prefixWildCardByName, []),
                ]);
            }

            $queryMatchResult = $this->createQuery($skip, $count);

        } else {
            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(null, [], $skip, $count);
        }

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, PeopleSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * людей которые могут помочь (т.е. с флагом могуПомочь)
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleHelpOffers($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);
        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getExistsFilter(PeopleSearchMapping::HELP_OFFERS_LIST_FIELD),
        ]);

        if (!is_null($searchText) && !empty($searchText)) {

            /*$this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields(array_merge(
                                                     PeopleSearchMapping::getMultiMatchQuerySearchFields(),
                                                     [
                                                         PeopleSearchMapping::HELP_OFFERS_NAME_FIELD,
                                                         PeopleSearchMapping::HELP_OFFERS_NAME_NGRAM_FIELD,
                                                         PeopleSearchMapping::HELP_OFFERS_NAME_TRANSLIT_FIELD,
                                                         PeopleSearchMapping::HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD,
                                                     ]
                                                 )
                                             ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PeopleSearchMapping::HELP_OFFERS_WORDS_NAME_FIELD,
                    "{$searchText}*"
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PeopleSearchMapping::FULLNAME_MORPHOLOGY_FIELD,
                    "{$searchText}*"
                ),
            ]);*/
            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));
            $queryShouldFields = $must = $should = [];

            $this->setHighlightQuery([
                HelpOffersSearchMapping::getHighlightConditions(),
            ]);

            if (count($slopPhrase) > 1) {
                // поиск по словосочетанию
                $this->setConditionQueryMust([
                    $this->_queryConditionFactory->getDisMaxQuery([
                        $this->_queryConditionFactory->getMatchPhraseQuery(
                            HelpOffersSearchMapping::HELP_OFFERS_NAME_FIELD, $searchText
                        ),
                        $this->_queryConditionFactory->getMatchPhraseQuery(
                            HelpOffersSearchMapping::HELP_OFFERS_NAME_TRANSLIT_FIELD, $searchText
                        ),
                    ]),
                ]);
            } else {
                // ищем по одному слову, с учетом словоформы, с учетом вхождения морфологии
                $this->setConditionQueryShould([
                    $this->_queryConditionFactory->getDisMaxQuery([
                        $this->_queryConditionFactory->getMultiMatchQuery()
                                                     ->setFields([
                                                         HelpOffersSearchMapping::HELP_OFFERS_NAME_FIELD,
                                                         HelpOffersSearchMapping::HELP_OFFERS_NAME_TRANSLIT_FIELD,
                                                     ])
                                                     ->setQuery($searchText)
                                                     ->setOperator(MultiMatch::OPERATOR_OR),
                        $this->_queryConditionFactory->getFieldQuery([
                            HelpOffersSearchMapping::HELP_OFFERS_WORDS_NAME_FIELD,
                            HelpOffersSearchMapping::HELP_OFFERS_WORDS_NAME_TRANSLIT_FIELD,
                        ], $searchText),
                        $this->_queryConditionFactory->getMatchPhrasePrefixQuery(
                            HelpOffersSearchMapping::HELP_OFFERS_WORDS_NAME_FIELD, $searchText
                        ),
                        $this->_queryConditionFactory->getMatchPhrasePrefixQuery(
                            HelpOffersSearchMapping::HELP_OFFERS_WORDS_NAME_TRANSLIT_FIELD, $searchText
                        ),
                    ]),
                ]);

            }

            $queryMatch = $this->createQuery($skip, $count);
        } else {
            $queryMatch = $this->createMatchQuery(
                null, [], $skip, $count
            );
        }

        /** поиск документа */
        return $this->searchDocuments($queryMatch, PeopleSearchMapping::CONTEXT);
    }

    /**
     * Поиск людей которые приблезительно
     * совпав по проценту интересов могут быть
     * потенциальными друзьями
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPossibleFriendsForUser($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        $tagsIntersectRange = [
            'category1' => [
                'min'      => 50,
                'max'      => 100,
                'distance' => self::DEFAULT_POSSIBLE_FRIENDS_RADIUS_MIN,
            ],
            'category2' => [
                'min'      => 0,
                'max'      => 100,
                'distance' => self::DEFAULT_POSSIBLE_FRIENDS_RADIUS_MAX,
            ],
        ];

        $tags = array_map(function ($tag) {
            return $tag['id'];
        }, $currentUser->getTags());

        $script_string = "
            int count = 0;
            int tagsCount = 0;
            int tagInPercent = 0;
            
            if(tagsValue.size() > 0 && doc[tagIdField].values.size() > 0){
                for(i = 0; i < tagsValue.size(); i++){
                    ++tagsCount;
                    for(j = 0; j < doc[tagIdField].values.size(); j++){
                        if( tagsValue[i] == doc[tagIdField][j] ){
                            ++count;
                        }
                    }
                }
                tagInPercent = count/tagsCount*100;
                tagInPercent = Math.round(tagInPercent);
            }
            
            return (tagInPercent <= intersectRange.max && tagInPercent >= intersectRange.min);
        ";

        $resultQuery = [];

        foreach ($tagsIntersectRange as $key => $tagsRange) {
            $this->clearQueryFactory();

            $this->setFilterQuery([
                $this->_queryFilterFactory->getScriptFilter(
                    $this->_scriptFactory->getScript($script_string, [
                        'tagIdField'     => PeopleSearchMapping::TAGS_ID_FIELD,
                        'tagsValue'      => $tags,
                        'intersectRange' => [
                            'min' => (int)$tagsRange['min'],
                            'max' => (int)$tagsRange['max'],
                        ],
                    ], \Elastica\Script::LANG_GROOVY)
                ),
                $this->_queryFilterFactory->getNotFilter(
                    $this->_queryFilterFactory->getTermsFilter(
                        PeopleSearchMapping::FRIEND_LIST_FIELD,
                        [$userId]
                    )
                ),
                $this->_queryFilterFactory->getNotFilter(
                    $this->_queryFilterFactory->getTermFilter([PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM => $userId])
                ),
            ]);

            $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);
            $this->setGeoPointConditions($point, PeopleSearchMapping::class);

            if ($point->isValid()) {
                $this->setFilterQuery([
                    $this->_queryFilterFactory->getGeoDistanceFilter(
                        PeopleSearchMapping::LOCATION_POINT_FIELD,
                        [
                            'lat' => $point->getLatitude(),
                            'lon' => $point->getLongitude(),
                        ],
                        $tagsRange['distance'],
                        'm'
                    ),
                ]);
            }

            /** формируем условия сортировки по удаленности */
            /*$this->setSortingQuery(
                $this->_sortingFactory->getGeoDistanceSort(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            );*/
            $this->setScriptFunctions([
                FunctionScore::DECAY_GAUSS => [
                    PeopleSearchMapping::LOCATION_POINT_FIELD => [
                        'origin' => "{$point->getLongitude()}, {$point->getLatitude()}",
                        'scale'  => '1km',
                        'offset' => '0km',
                        'decay'  => 0.33,
                    ],
                ],
            ]);

            $this->setScriptFunctionOption([
                'scoreMode' => 'multiply',
                'boostMode' => 'multiply',
            ]);

            $this->setSortingQuery([
                $this->_sortingFactory->getGeoDistanceSort(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);

            $queryMatch = $this->createMatchQuery($searchText, PeopleSearchMapping::getMultiMatchQuerySearchFields(), $skip, $count);

            $resultQuery[$key] = $this->searchDocuments($queryMatch, PeopleSearchMapping::CONTEXT);
        }

        return $resultQuery;
    }


    /**
     * Поиск профиля по заданному ID
     * @param string $userId ID текущего пользователя
     * @param string $profileId ID профиля по которому ищем инфу
     * @param GeoPointServiceInterface $point
     * @return array
     */
    public function searchProfileById($userId, $profileId, GeoPointServiceInterface $point)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет по совпадению интересов (массив интересов) */
        $this->setScriptMatchInterestsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PeopleSearchMapping::IDENTIFIER_FIELD, $profileId)
        ]);

        $query = $this->createQuery(0, 1);

        return $this->searchDocuments($query, PeopleSearchMapping::CONTEXT);
    }
}