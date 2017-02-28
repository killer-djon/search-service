<?php
/**
 * Общий сервис поиска
 * с помощью которого будем искать как глобально так и маркеры
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use Common\Core\Facade\Service\Geo\GeoPointService;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Aggregation\GeoDistance;
use Elastica\Query\FunctionScore;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\AbstractSearchMapping;
use RP\SearchBundle\Services\Mapping\TagNameSearchMapping;

class CommonSearchService extends AbstractSearchService
{

    /**
     * Кол-во выводимых данных
     * по блокам в общем поиске
     *
     * @const int DEFAULT_SEARCH_BLOCK_SIZE
     */
    const DEFAULT_SEARCH_BLOCK_SIZE = 3;

    /**
     * Глобальный (общий поиск) в системе
     * варианты поиска могут быть:
     *  1. Указан ID города и поисковый запрос
     *  2. Указан ID города но пустой поисковый запрос
     *  3. Не указан ID города, НО указан поисковый запрос
     *
     * @param string $userId
     * @param array $filterType Коллекция в которой ищем или же пусто тогда во всех
     * @param string|null $searchText Поисковый запрос
     * @param string|null $cityId ID города в котором будем искать
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface|null $point ТОчка координат
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function commonFlatSearchByFilters(
        $userId,
        array $filterType,
        $searchText = null,
        $cityId = null,
        GeoPointServiceInterface $point = null,
        $skip = 0,
        $count = null
    ) {

        $currentUser = $this->getUserById($userId);
        $searchFilters = [];

        foreach ($filterType as $type) {

            $searchFilters[] = $this->_queryFilterFactory->getBoolAndFilter(
                $this->filterSearchTypes[$type]::getFlatMatchSearchFilter($this->_queryFilterFactory, $this->searchTypes[$type], $userId)
            );
            $this->setScriptTagsConditions($currentUser, $this->filterSearchTypes[$type]);
            $this->setGeoPointConditions($point, $this->filterSearchTypes[$type]);
        }

        $this->setFilterQuery([$this->_queryFilterFactory->getBoolOrFilter($searchFilters)]);

        if (!is_null($cityId) && !empty($cityId)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    $this->filterSearchTypes[$type]::LOCATION_CITY_ID_FIELD => $cityId,
                ]),
            ]);
        }

        $queryMatch = $this->createQuery($skip, $count); // в случае если есть поисковая строка

        /*$queryMatch = $this->createMatchQuery(
            $searchText,
            $type::getMultiMatchQuerySearchFields(),
            $skip, (is_null($count) ? self::DEFAULT_SEARCH_BLOCK_SIZE : $count)
        );*/

        $this->setFlatFormatResult(true);

        /**
         * Надо допилить поиск flat данных в одну кучу
         * надо создать новый метод который это будет делать
         * потому что прежние методы делают это с индексом
         * например: searchFlatDocuments и transformFlatResult
         * это надо обязательно
         */
        return $this->searchDocuments($queryMatch);

        //print_r( $queryMatch ); die();
    }

    /**
     * Глобальный (общий поиск) в системе
     * варианты поиска могут быть:
     *  1. Указан ID города и поисковый запрос
     *  2. Указан ID города но пустой поисковый запрос
     *  3. Не указан ID города, НО указан поисковый запрос
     *
     * @param string $userId
     * @param string|null $searchText Поисковый запрос
     * @param string|null $cityId ID города в котором будем искать
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface|null $point ТОчка координат
     * @param array|null $filterType Коллекция в которой ищем или же пусто тогда во всех
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function commonSearchByFilters(
        $userId,
        $searchText = null,
        $cityId = null,
        GeoPointServiceInterface $point = null,
        $filterType = null,
        $skip = 0,
        $count = null
    ) {
        $currentUser = $this->getUserById($userId);
        if (is_null($filterType)) {
            /**
             * Массив объектов запроса
             * ключами в массиве служит тип поиска (в какой коллекции искать надо)
             */
            $queryMatchResults = [];

            /**
             * Если не задана категория поиска
             * тогда ищем во всех коллекциях еластика по условиям
             */
            if( isset($this->filterSearchTypes['people']) )
            {
                unset($this->filterSearchTypes['people']);
            }
            foreach ($this->filterSearchTypes as $keyType => $type) {
                $this->clearQueryFactory();

                $this->setFilterQuery($type::getMatchSearchFilter($this->_queryFilterFactory, $userId));
                $this->setScriptTagsConditions($currentUser, $type);
                $this->setGeoPointConditions($point, $type);

                if (!is_null($cityId) && !empty($cityId) && !is_null($type::LOCATION_CITY_ID_FIELD)) {
                    $this->setFilterQuery([
                        $this->_queryFilterFactory->getTermFilter([
                            $type::LOCATION_CITY_ID_FIELD => $cityId,
                        ]),
                    ]);
                }

                if ($point->isValid() && !is_null($point->getRadius())) {
                    $this->setAggregationQuery([
                        $this->_queryAggregationFactory->getGeoHashAggregation(
                            $type::LOCATION_POINT_FIELD,
                            [
                                "lat" => $point->getLatitude(),
                                "lon" => $point->getLongitude(),
                            ],
                            $point->getRadius()
                        ),
                    ]);
                }

                $this->setHighlightQuery($type::getHighlightConditions());

                $this->setScriptFunctions([
                    FunctionScore::DECAY_LINEAR => [
                        $type::LOCATION_POINT_FIELD => [
                            'origin' => "{$point->getLongitude()}, {$point->getLatitude()}",
                            'scale'  => '1km',
                            'offset' => '0km',
                            'decay'  => 0.2,
                        ],
                    ],
                ]);

                $this->setScriptFunctionOption([
                    'scoreMode' => 'multiply',
                    'boostMode' => 'multiply',
                ]);

                $this->setSortingQuery([
                    $this->_sortingFactory->getGeoDistanceSort(
                        $type::LOCATION_POINT_FIELD,
                        $point
                    ),
                ]);

                if (!is_null($searchText)) {
                    $searchText = mb_strtolower($searchText);
                    $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

                    $slopPhrase = array_filter(explode(" ", $searchText));
                    $queryShouldFields = $must = $should = [];

                    if (count($slopPhrase) > 1) {

                        /**
                         * Поиск по точному воспадению искомого словосочетания
                         */
                        $queryMust = $type::getSearchConditionQueryMust($this->_queryConditionFactory, $searchText);

                        if (!empty($queryMust)) {
                            $this->setConditionQueryMust($queryMust);
                        }

                    } else {
                        $queryShould = $type::getSearchConditionQueryShould(
                            $this->_queryConditionFactory, $searchText
                        );

                        if (!empty($queryShould)) {
                            /**
                             * Ищем по частичному совпадению поисковой фразы
                             */
                            $this->setConditionQueryShould($queryShould);
                        }
                    }

                    $queryMatchResults[$keyType] = $this->createQuery($skip, (is_null($count) ? self::DEFAULT_SEARCH_BLOCK_SIZE : $count));

                } else {
                    $this->setSortingQuery([
                        $this->_sortingFactory->getGeoDistanceSort(
                            $type::LOCATION_POINT_FIELD,
                            $point
                        ),
                    ]);
                    $queryMatchResults[$keyType] = $this->createMatchQuery(
                        $searchText,
                        $type::getMultiMatchQuerySearchFields(),
                        $skip, (is_null($count) ? self::DEFAULT_SEARCH_BLOCK_SIZE : $count)
                    );
                }

            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            return $this->searchMultiTypeDocuments($queryMatchResults);
        }

        $queryMatchResults = [];
        foreach ($filterType as $key => $type) {
            $this->clearQueryFactory();

            if (!is_null($cityId) && !empty($cityId) && !is_null($this->filterSearchTypes[$type]::LOCATION_CITY_ID_FIELD)) {
                $this->setFilterQuery([
                    $this->_queryFilterFactory->getTermFilter([
                        $this->filterSearchTypes[$type]::LOCATION_CITY_ID_FIELD => $cityId,
                    ]),
                ]);
            }

            $this->setFilterQuery($this->filterSearchTypes[$type]::getMatchSearchFilter($this->_queryFilterFactory, $userId));

            $this->setScriptTagsConditions($currentUser, $this->filterSearchTypes[$type]);
            $this->setGeoPointConditions($point, $this->filterSearchTypes[$type]);

            $this->setHighlightQuery($this->filterSearchTypes[$type]::getHighlightConditions());

            if (!is_null($searchText)) {

                $this->setScriptFunctions([
                    FunctionScore::DECAY_GAUSS => [
                        $this->filterSearchTypes[$type]::LOCATION_POINT_FIELD => [
                            /*'origin' => [
                                'lat' => $point->getLatitude(),
                                'lon' => $point->getLongitude(),
                            ],*/
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
                    'maxBoost'  => 10,
                ]);

                $this->setSortingQuery([
                    $this->_sortingFactory->getGeoDistanceSort(
                        $this->filterSearchTypes[$type]::LOCATION_POINT_FIELD,
                        $point
                    ),
                ]);

                $searchText = mb_strtolower($searchText);
                $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

                $slopPhrase = array_filter(explode(" ", $searchText));
                $queryShouldFields = $must = $should = $subShould = [];

                if (count($slopPhrase) > 1) {

                    /**
                     * Поиск по точному воспадению искомого словосочетания
                     */
                    $queryMust = $this->filterSearchTypes[$type]::getSearchConditionQueryMust($this->_queryConditionFactory, $searchText);

                    if (!empty($queryMust)) {
                        $this->setConditionQueryMust($queryMust);
                    }

                } else {
                    $queryShould = $this->filterSearchTypes[$type]::getSearchConditionQueryShould(
                        $this->_queryConditionFactory, $searchText
                    );

                    if (!empty($queryShould)) {
                        /**
                         * Ищем по частичному совпадению поисковой фразы
                         */

                        $this->setConditionQueryShould($queryShould);
                    }
                }

                $queryMatchResults[$type] = $this->createQuery($skip, $count);

            } else {
                $this->setSortingQuery([
                    $this->_sortingFactory->getGeoDistanceSort(
                        $this->filterSearchTypes[$type]::LOCATION_POINT_FIELD,
                        $point
                    ),
                ]);

                $queryMatchResults[$type] = $this->createMatchQuery(
                    $searchText,
                    $this->filterSearchTypes[$type]::getMultiMatchQuerySearchFields(),
                    $skip, (is_null($count) ? self::DEFAULT_SEARCH_BLOCK_SIZE : $count)
                );

            }
        }

        return $this->searchMultiTypeDocuments($queryMatchResults);
    }

    /**
     * Поиск маркеров по задданым типам
     * в поиске могут присутствовать несколько типов
     *
     * @param string $userId
     * @param array $filters По каким типам делаем поиск
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface $point ТОчка координат
     * @param bool $isCluster (default: false) выводить ли класстерные данные
     * @param string|null $geoHashCell GeoHash ячайка
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function searchMarkersByFilters(
        $userId,
        array $filters,
        GeoPointServiceInterface $point,
        $isCluster = false,
        $geoHashCell = null,
        $skip = 0,
        $count = null
    ) {
        $currentUser = $this->getUserById($userId);

        array_walk($filters, function ($filter) use (&$searchTypes) {
            // временный костыль для IOS приложения
            // это чтобы грамАтным угодить
            if ($filter == 'people') {
                $filter = 'peoples';
            }

            if (!preg_match('/(all)/i', $filter)) {
                array_key_exists($filter, $this->filterTypes) && $searchTypes[$filter] = $this->filterTypes[$filter]::getMultiMatchQuerySearchFields();
            } else {
                foreach ($this->getFilterTypes() as $key => $class) {
                    if ($key == 'people') {
                        $key = 'peoples';
                    }
                    $searchTypes[$key] = $class::getMultiMatchQuerySearchFields();
                }
            }
        });

        if (!is_null($searchTypes) && !empty($searchTypes)) {
            $queryMatchResults = [];

            foreach ($searchTypes as $keyType => $typeFields) {
                $this->clearQueryFactory();

                $this->setFilterQuery($this->filterTypes[$keyType]::getMarkersSearchFilter($this->_queryFilterFactory, $userId));
                $this->setScriptTagsConditions($currentUser, $this->filterTypes[$keyType]);

                if ($isCluster == false) {
                    if (!is_null($geoHashCell) && !empty($geoHashCell)) {

                        $geoDistanceRange = array_map(function ($itemDistance) {
                            return (int)$itemDistance;
                        }, explode('-', $geoHashCell));

                        $this->setFilterQuery([
                            $this->_queryFilterFactory->getGeoDistanceRangeFilter(
                                $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                                [
                                    "lat" => $point->getLatitude(),
                                    "lon" => $point->getLongitude(),
                                ],
                                [
                                    'from' => "{$geoDistanceRange[0]}m",
                                    'to'   => "{$geoDistanceRange[1]}m",
                                ]
                            ),
                        ]);
                    }
                }

                $this->setGeoPointConditions($point, $this->filterTypes[$keyType]);

                if ($isCluster) {

                    $this->setAggregationQuery([
                        $this->_queryAggregationFactory->getGeoDistanceAggregation(
                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                            [
                                "lat" => $point->getLatitude(),
                                "lon" => $point->getLongitude(),
                            ],
                            $point->getRadius(),
                            'm'
                        )->addAggregation($this->_queryAggregationFactory->getGeoCentroidAggregation(
                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD
                        ))->addAggregation($this->_queryAggregationFactory->setAggregationSource(
                            $this->filterTypes[$keyType]::LOCATION_FIELD,
                            [], 1
                        )),
                    ]);
                    /*$this->setAggregationQuery([
	                    $this->_queryAggregationFactory->getFilterAggregation(
		                    'filtered_cells', 
		                    $this->_queryFilterFactory->getBoundingBoxFilter(
			                    $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
	                            [
	                                "lat" => $point->getLatitude(),
	                                "lon" => $point->getLongitude(),
	                            ],
	                            $point->getRadius()
		                    )
		                )->addAggregation(
			                $this->_queryAggregationFactory->getGeoDistanceAggregation(
	                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
	                            [
	                                "lat" => $point->getLatitude(),
	                                "lon" => $point->getLongitude(),
	                            ],
	                            $point->getRadius(),
	                            'm'
	                        )->addAggregation($this->_queryAggregationFactory->getGeoCentroidAggregation(
	                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD
	                        ))->addAggregation($this->_queryAggregationFactory->setAggregationSource(
	                            $this->filterTypes[$keyType]::LOCATION_FIELD,
	                            [], 1
	                        ))
		                )
                    ]);*/
                }

                /** формируем условия сортировки */
                $this->setSortingQuery(
                    $this->_sortingFactory->getGeoDistanceSort(
                        $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                        $point
                    )
                );

                /**
                 * Получаем сформированный объект запроса
                 * когда запрос многотипный НЕТ необходимости
                 * указывать skip и count
                 */
                $queryMatchResults[$keyType] = $this->createMatchQuery(
                    null,
                    $typeFields,
                    $skip,
                    $count
                );
                //print_r( $queryMatchResults[$keyType]->toArray() ); die();
            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            $documents = $this->searchMultiTypeDocuments($queryMatchResults);

            if ($isCluster == true) {
                if ($this->getClusterGrouped() == true) {

                    $documents['cluster'] = $this->groupClasterLocationBuckets(
                        $documents['cluster'],
                        AbstractSearchMapping::LOCATION_FIELD
                    );
                    unset($documents['items']);
                }
            } else {
                unset($documents['cluster']);
            }

            return $documents;
        }
    }

    /**
     * Поиск интересов, возможные варианты:
     * 1. Либо вывод топ интересов (в случае пустой поисковой строки)
     * 2. либо вывод N кол-во интересов
     * 3. либо поиск интересов по запросу
     *
     * @param string $userId
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function searchCountInterests($userId, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getGtFilter(TagNameSearchMapping::USERS_COUNT_FIELD, 0),
        ]);

        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(
                TagNameSearchMapping::USERS_COUNT_FIELD,
                SortingOrder::SORTING_DESC
            )
        );

        $script_string = "doc['usersCount'].value + doc['placeCount'].value + doc['eventsCount'].value";

        $this->setScriptFields([
            'sumCount' => $this->_scriptFactory->getScript($script_string),
        ]);

        $queryMatchAll = $this->createMatchQuery(
            null,
            TagNameSearchMapping::getMultiMatchQuerySearchFields(),
            $skip,
            $count
        );

        return $this->getInterests($queryMatchAll);
    }

    /**
     * Поиск интересов по названию
     *
     * @param string $userId
     * @param string|null $searchText Поисковый запрос
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function searchInterestsByName($userId, $searchText, $skip = 0, $count = null)
    {
        $this->setSortingQuery(
            $this->_sortingFactory->getFieldSort(
                TagNameSearchMapping::USERS_COUNT_FIELD,
                SortingOrder::SORTING_DESC
            )
        );

        $script_string = "doc['usersCount'].value + doc['placeCount'].value + doc['eventsCount'].value";

        $this->setScriptFields([
            'sumCount' => $this->_scriptFactory->getScript($script_string),
        ]);

        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getDisMaxQuery([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setFields([
                                                 TagNameSearchMapping::NAME_FIELD,
                                                 TagNameSearchMapping::NAME_TRANSLIT_FIELD,
                                                 TagNameSearchMapping::RUS_TRANSLITERATE_NAME,
                                             ])
                                             ->setOperator(MultiMatch::OPERATOR_OR)
                                             ->setQuery($searchText),
                $this->_queryConditionFactory->getFieldQuery([
                    TagNameSearchMapping::NAME_FIELD,
                    TagNameSearchMapping::NAME_TRANSLIT_FIELD,
                    TagNameSearchMapping::RUS_TRANSLITERATE_NAME,
                ], $searchText),
                $this->_queryConditionFactory->getPrefixQuery(TagNameSearchMapping::NAME_FIELD, $searchText),
                $this->_queryConditionFactory->getPrefixQuery(TagNameSearchMapping::NAME_TRANSLIT_FIELD, $searchText),
                $this->_queryConditionFactory->getPrefixQuery(TagNameSearchMapping::RUS_TRANSLITERATE_NAME, $searchText),
            ]),
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->getInterests($queryMatch);
    }

    /**
     * Прокси метод который возвращает найденные данные
     * по заданным условиям поиска
     *
     * @param \Elastica\Query $query Объект запроса
     * @return array набора найденных данных
     */
    private function getInterests(\Elastica\Query $query)
    {
        return $this->searchDocuments($query, TagNameSearchMapping::CONTEXT);
    }

}