<?php
/**
 * Общий сервис поиска
 * с помощью которого будем искать как глобально так и маркеры
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
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
     * Кол-во выводимых интересов по умолчанию
     *
     * @const int DEFAULT_INTERESTS_COUNT
     */
    const DEFAULT_INTERESTS_COUNT = 5;

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
     * @param string|null $filterType Коллекция в которой ищем или же пусто тогда во всех
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

                $this->setSortingQuery([
                    $this->_sortingFactory->getFieldSort('_score', SortingOrder::SORTING_DESC),
                    $this->_sortingFactory->getGeoDistanceSort(
                        $type::LOCATION_POINT_FIELD,
                        $point,
                        'asc'
                    ),
                ]);

                if (!is_null($searchText)) {
                    $queryShouldFields = $must = [];
                    if (!empty($type::getMultiMatchQuerySearchFields())) {
                        foreach ($type::getMultiMatchQuerySearchFields() as $fieldName) {
                            $queryShouldFields[] = $this->_queryConditionFactory
                                ->getMatchPhrasePrefixQuery($fieldName, $searchText)
                                ->setFieldBoost($fieldName, 2);
                        }

                    }

                    if (!empty($type::getMultiMatchNgramQuerySearchFields())) {

                        $must[] = $this->_queryConditionFactory->getMultiMatchQuery()
                                                               ->setFields($type::getMultiMatchNgramQuerySearchFields())
                                                               ->setQuery($searchText);
                    }

                    $this->setConditionQueryShould(array_merge($must, $queryShouldFields));

                    $queryMatchResults[$keyType] = $this->createQuery(0, self::DEFAULT_SEARCH_BLOCK_SIZE);
                } else {
                    $queryMatchResults[$keyType] = $this->createMatchQuery(
                        $searchText,
                        $type::getMultiMatchQuerySearchFields(),
                        0, self::DEFAULT_SEARCH_BLOCK_SIZE
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
                    $this->_scriptFactory->getScript("
                    scoreSorting = _score * doc[locationField].distanceInKm(lat, lon)
                ", [
                        'lat' => $point->getLatitude(),
                        'lon' => $point->getLongitude(),
                        'locationField' => $this->filterSearchTypes[$type]::LOCATION_POINT_FIELD
                    ])
                ], [
                    'scoreMode' => 'min',
                    'boostMode' => 'replace'
                ]);

                $this->setSortingQuery([
                    $this->_sortingFactory->getFieldSort('_score')
                ]);

                $queryShouldFields = $must = [];
                if (!empty($this->filterSearchTypes[$type]::getMultiMatchQuerySearchFields())) {
                    foreach ($this->filterSearchTypes[$type]::getMultiMatchQuerySearchFields() as $fieldName) {
                        $queryShouldFields[] = $this->_queryConditionFactory
                            ->getMatchPhrasePrefixQuery($fieldName, $searchText)
                            ->setFieldBoost($fieldName, 2);
                    }

                }

                if (!empty($this->filterSearchTypes[$type]::getMultiMatchNgramQuerySearchFields())) {

                    $must[] = $this->_queryConditionFactory->getMultiMatchQuery()
                                                           ->setFields($this->filterSearchTypes[$type]::getMultiMatchNgramQuerySearchFields())
                                                           ->setQuery($searchText);
                }

                $this->setConditionQueryShould(array_merge($must, $queryShouldFields));

                $queryMatchResults[$type] = $this->createQuery($skip, $count);

            } else {
                $queryMatchResults[$type] = $this->createMatchQuery(
                    $searchText,
                    $this->filterSearchTypes[$type]::getMultiMatchQuerySearchFields(),
                    $skip, $count
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
    public function searchMarkersByFilters($userId, array $filters, GeoPointServiceInterface $point, $isCluster = false, $geoHashCell = null, $skip = 0, $count = null)
    {
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

                if (mb_strlen($geoHashCell) > 0) {
                    $isCluster = false;
                    $this->setFilterQuery([
                        $this->_queryFilterFactory->getGeoHashFilter(
                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                            [
                                "lat" => $point->getLatitude(),
                                "lon" => $point->getLongitude(),
                            ],
                            mb_strlen($geoHashCell),
                            $geoHashCell
                        ),
                    ]);
                }
                $this->setGeoPointConditions($point, $this->filterTypes[$keyType]);

                if ($isCluster == true) {
                    $this->setAggregationQuery([
                        $this->_queryAggregationFactory->getGeoHashAggregation(
                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                            [
                                "lat" => $point->getLatitude(),
                                "lon" => $point->getLongitude(),
                            ],
                            $point->getRadius()
                        )->addAggregation($this->_queryAggregationFactory->setAggregationSource(
                            AbstractSearchMapping::LOCATION_FIELD,
                            [AbstractSearchMapping::IDENTIFIER_FIELD]
                        ))->addAggregation($this->_queryAggregationFactory->getGeoCentroidAggregation(
                            $this->filterTypes[$keyType]::LOCATION_POINT_FIELD
                        )),
                    ]);
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
            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            $documents = $this->searchMultiTypeDocuments($queryMatchResults);

            if ($this->getClusterGrouped() == true) {
                $documents['cluster'] = $this->groupClasterLocationBuckets($documents['cluster'], AbstractSearchMapping::LOCATION_FIELD);
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
     * @param string|null $searchText Поисковый запрос
     * @param int $skip (default: 0)
     * @param int|null $count Кол-во в результате
     * @return array Массив с найденными результатами
     */
    public function searchCountInterests($userId, $searchText = null, $skip = 0, $count = null)
    {
        $count = is_null($searchText) && empty($searchText) ? self::DEFAULT_INTERESTS_COUNT : $count;

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
            $searchText,
            TagNameSearchMapping::getMultiMatchQuerySearchFields(),
            $skip,
            $count
        );

        return $this->searchDocuments($queryMatchAll, TagNameSearchMapping::CONTEXT);
    }

}