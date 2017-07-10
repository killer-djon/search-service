<?php
/**
 * Сервис поиска по условиям городов
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\Location;
use Common\Util\ArrayHelper;
use Elastica\Filter\AbstractFilter;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\AbstractSearchMapping;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;
use RP\SearchBundle\Services\Mapping\DiscountsSearchMapping;
use RP\SearchBundle\Services\Mapping\EventsSearchMapping;
use RP\SearchBundle\Services\Mapping\HelpOffersSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\RusPlaceSearchMapping;

class CitySearchService extends AbstractSearchService
{
    /**
     * @const int
     */
    const DEFAULT_SKIP_CITIES = 0;

    /**
     * @const int
     */
    const DEFAULT_COUNT_CITIES = 3;

    /**
     * Метод осуществляет поиск в еластике
     * по названию города
     *
     * @param string $searchText Поисковый запрос
     * @param string $countryName
     * @param array $types
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchCityByName($searchText, $countryName = null, $types = [], $skip = 0, $count = null)
    {
        if (empty($types)) {
            $types = [
                Location::CITY_TYPE,
                Location::TOWN_TYPE,
            ];
        }

        $filter = $this->_queryFilterFactory;

        $this->setFilterQuery([
            $filter->getTermsFilter(CitySearchMapping::CITY_TYPE_FIELD, $types),
            $filter->getNestedFilter(
                CitySearchMapping::COUNTRY_FIELD,
                $filter->getExistsFilter(CitySearchMapping::COUNTRY_ID_FIELD)
            ),
        ]);

        if (!empty($countryName)) {
            $this->setFilterQuery([
                $filter->getQueryFilter(
                    $this->_queryConditionFactory->getNestedQuery(
                        CitySearchMapping::COUNTRY_FIELD,
                        $this->_queryConditionFactory->getMultiMatchQuery()
                                                     ->setFields([
                                                         $this->setBoostField(CitySearchMapping::COUNTRY_NAME_FIELD, 5),
                                                         $this->setBoostField(CitySearchMapping::COUNTRY_INTERNATIONAL_NAME_FIELD, 4),
                                                     ])
                                                     ->setQuery(mb_strtolower($countryName))
                                                     ->setOperator(MultiMatch::OPERATOR_OR)
                                                     ->setType(MultiMatch::TYPE_PHRASE_PREFIX)
                    )
                ),
            ]);
        }

        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setFields([
                                             $this->setBoostField(CitySearchMapping::NAME_FIELD, 5),
                                             $this->setBoostField(CitySearchMapping::INTERNATIONAL_NAME_FIELD, 4),
                                             $this->setBoostField(CitySearchMapping::TRANSLIT_NAME_FIELD, 3),
                                         ])
                                         ->setQuery(mb_strtolower($searchText))
                                         ->setOperator(MultiMatch::OPERATOR_OR)
                                         ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
        ]);

        $this->setSortingQuery([
            $this->_sortingFactory->getFieldSort('_score', 'desc'),
            // $this->_sortingFactory->getFieldSort(CitySearchMapping::NAME_FIELD)
        ]);

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, CitySearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по названию города и сортирует выборку по популярности города
     * (совокупное кол-во )
     *
     * @param string $searchText Поисковый запрос
     * @param string $countryName
     * @param array $types
     * @param bool $isAggregation Агрегировать ли данные при запросе или вывести списком
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchTopCityByName($searchText, $countryName = null, $types = [], $skip = 0, $count = null)
    {
        $cities = $this->searchCityByName($searchText, $countryName, $types);

        $cities = isset($cities[CitySearchMapping::CONTEXT])
            ? ArrayHelper::index($cities[CitySearchMapping::CONTEXT], 'id')
            : [];

        $filter = $this->_queryFilterFactory;

        $this->setFilterQuery([
            $filter->getTermsFilter(
                AbstractSearchMapping::LOCATION_CITY_ID_FIELD,
                array_keys($cities)
            ),
            $filter->getBoolOrFilter([
                // события
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(EventsSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        EventsSearchMapping::getMarkersSearchFilter($filter)
                    ),
                ]),
                // могу помочь
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        HelpOffersSearchMapping::getMarkersSearchFilter($filter)
                    ),
                ]),
                // пользователи
                /*$filter->getBoolAndFilter([
                    $filter->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        PeopleSearchMapping::getMarkersSearchFilter($filter)
                    )
                ]),
                // места БЕЗ скидок
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        PlaceSearchMapping::getMarkersSearchFilter($filter)
                    )
                ]),*/
                // скидочные места
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        DiscountsSearchMapping::getMarkersSearchFilter($filter)
                    ),
                ]),
                // русские места
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        RusPlaceSearchMapping::getMarkersSearchFilter($filter)
                    ),
                ]),
            ]),
        ]);

        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTermsAggregation(
                AbstractSearchMapping::LOCATION_CITY_ID_FIELD
            )->setOrder('_count', 'desc')->setSize($count),
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        $this->searchDocuments($queryMatch);

        $aggs = $this->getAggregations();

        $result = [];

        if (!empty($aggs)) {
            foreach ($aggs as $agg) {
                $city_id = $agg['key'];

                if (isset($cities[$city_id])) {
                    $result[] = $cities[$city_id];

                    unset($cities[$city_id]);
                }
            }
        }

        if (!empty($cities)) {
            $result = array_merge($result, $cities);
        }

        return [CitySearchMapping::CONTEXT => array_slice($result, $skip, $count)];
    }

    /**
     * Метод выозвращает список последних городов, которые исклал пользователя
     *
     * @param string $userId ID пользователя
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function getLastSearchedCitiesList($userId, $skip = self::DEFAULT_SKIP_CITIES, $count = self::DEFAULT_COUNT_CITIES)
    {
        $cities = [];

        if (!empty($userId)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([AbstractSearchMapping::AUTHOR_ID_FIELD => $userId]),
            ]);

            $this->setSortingQuery($this->_sortingFactory->getFieldSort(AbstractSearchMapping::IDENTIFIER_FIELD, 'desc'));

            $queryMatch = $this->createQuery($skip, $count);
            $this->setIndices();

            $history = $this->searchDocuments($queryMatch);

            if (!empty($history['search_history'])) {
                foreach ($history['search_history'] as $item) {
                    if (empty($item['city'])) {
                        continue;
                    }

                    if (!isset($cities[$item['city']['id']])) {
                        $cities[$item['city']['id']] = $item['city'];
                    }
                }
            }
        }

        return empty($cities) ? [] : array_values($cities);
    }

    /**
     * Стартовый город от которого
     * мы будем по радиусу определять 3 самых
     * популярных городов
     *
     * @const string
     */
    const DEFAULT_START_CITY = 'Мадрид';

    /**
     * Поиск наиболее полулярных городов
     * популярность будет определяться суммой
     * (пользователей + мест + скидок) / коефициент
     *
     * @param array $exclude исключить эти города из выборки
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function getTopCitiesList($exclude = [], $skip = self::DEFAULT_SKIP_CITIES, $count = self::DEFAULT_COUNT_CITIES)
    {
        /**
         * Получаем начальную точку на карте Европы
         * для того чтобы от нее сделать радиус максимальный
         * по европе только
         */
        $city = $this->searchCityByName(
            self::DEFAULT_START_CITY
        );

        if (empty($city)) {
            return [];
        }

        $city = current($city[CitySearchMapping::CONTEXT]);
        $cityLocationPoints = $city['CenterPoint'];

        $this->setFilterQuery([
            $this->_queryFilterFactory->getGeoDistanceFilter(
                AbstractSearchMapping::LOCATION_POINT_FIELD,
                $cityLocationPoints,
                2500, 'km'
            ),
            $this->getTopSummaryEntities(),
        ]);

        if (!empty($exclude)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getNotFilter(
                    $this->_queryFilterFactory->getTermsFilter(AbstractSearchMapping::LOCATION_CITY_ID_FIELD, $exclude)
                ),
            ]);
        }

        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTermsAggregation(
                AbstractSearchMapping::LOCATION_CITY_ID_FIELD
            )->setOrder('_count', 'desc')->setSize($count),
        ]);

        $queryMatch = $this->createQuery($skip, $count);
        $this->setIndices();

        return $this->searchDocuments($queryMatch);
    }

    /**
     * Возвращаем фильтр для аггрегирования данных
     * с подсчетом кол-ва сущностей
     *
     * @return AbstractFilter
     */
    public function getTopSummaryEntities()
    {
        return $this->_queryFilterFactory->getBoolOrFilter([
            // события
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(EventsSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    EventsSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                ),
            ]),
            // могу помочь
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(PeopleSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    HelpOffersSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                ),
            ]),
            // пользователи
            /*$this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(PeopleSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    PeopleSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                )
            ]),
            // места БЕЗ скидок
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    PlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                )
            ]),*/
            // скидочные места
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    DiscountsSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                ),
            ]),
            // русские места
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                $this->_queryFilterFactory->getBoolAndFilter(
                    RusPlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory)
                ),
            ]),
        ]);
    }
}