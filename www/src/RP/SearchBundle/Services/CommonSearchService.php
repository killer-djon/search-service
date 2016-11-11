<?php
/**
 * Общий сервис поиска
 * с помощью которого будем искать как глобально так и маркеры
 */
namespace RP\SearchBundle\Services;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;

class CommonSearchService extends AbstractSearchService
{

    /**
     * Кол-во выводимых данных
     * по блокам в общем поиске
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
                $this->clearScriptFields();
                $this->clearFilter();

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

                /**
                 * Получаем сформированный объект запроса
                 * когда запрос многотипный НЕТ необходимости
                 * указывать skip и count
                 */
                $queryMatchResults[$keyType] = $this->createMatchQuery(
                    $searchText,
                    $type::getMultiMatchQuerySearchFields(),
                    0, self::DEFAULT_SEARCH_BLOCK_SIZE
                );
            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            return $this->searchMultiTypeDocuments($queryMatchResults);
        }

        if (!is_null($cityId) && !empty($cityId) && !is_null($this->filterSearchTypes[$filterType]::LOCATION_CITY_ID_FIELD)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    $this->filterSearchTypes[$filterType]::LOCATION_CITY_ID_FIELD => $cityId,
                ]),
            ]);
        }

        $this->setFilterQuery($this->filterSearchTypes[$filterType]::getMatchSearchFilter($this->_queryFilterFactory, $userId));
        $this->setScriptTagsConditions($currentUser, $this->filterSearchTypes[$filterType]);
        $this->setGeoPointConditions($point, $this->filterSearchTypes[$filterType]);

        $queryMatch = $this->createMatchQuery(
            $searchText,
            $this->filterSearchTypes[$filterType]::getMultiMatchQuerySearchFields(),
            $skip,
            $count
        );

        return $this->searchDocuments($queryMatch, $this->searchTypes[$filterType], true, $filterType);
    }

    /**
     * Поиск маркеров по задданым типам
     * в поиске могут присутствовать несколько типов
     *
     * @param string $userId
     * @param array $filters По каким типам делаем поиск
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface $point ТОчка координат
     * @return array Массив с найденными результатами
     */
    public function searchMarkersByFilters($userId, array $filters, GeoPointServiceInterface $point)
    {
        $currentUser = $this->getUserById($userId);

        array_walk($filters, function ($filter) use (&$searchTypes) {
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
                $this->clearScriptFields();
                $this->clearFilter();

                $this->setFilterQuery($this->filterTypes[$keyType]::getMarkersSearchFilter($this->_queryFilterFactory, $userId));
                $this->setScriptTagsConditions($currentUser, $this->filterTypes[$keyType]);
                $this->setGeoPointConditions($point, $this->filterTypes[$keyType]);

                $this->setAggregationQuery([
                    $this->_queryAggregationFactory->getGeoHashAggregation(
                        $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                        [
                            "lat" => $point->getLatitude(),
                            "lon" => $point->getLongitude(),
                        ],
                        $point->getRadius()
                    ),
                ]);

                /** формируем условия сортировки */
                $this->setSortingQuery([
                    $this->_sortingFactory->getGeoDistanceSort(
                        $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                        $point
                    ),
                ]);

                /**
                 * Получаем сформированный объект запроса
                 * когда запрос многотипный НЕТ необходимости
                 * указывать skip и count
                 */
                $queryMatchResults[$keyType] = $this->createMatchQuery(
                    null,
                    $typeFields
                );
            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            return $this->searchMultiTypeDocuments($queryMatchResults);
        }
    }
}