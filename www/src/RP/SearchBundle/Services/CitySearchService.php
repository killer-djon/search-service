<?php
/**
 * Сервис поиска по условиям городов
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\Location;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Util\ArrayHelper;
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
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchCityByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTermsFilter(CitySearchMapping::CITY_TYPE_FIELD, [
                    Location::CITY_TYPE,
                    //Location::TOWN_TYPE,
                ]),
            ]),
        ]);

        $searchText = mb_strtolower($searchText);

        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setFields([
                                             $this->setBoostField(CitySearchMapping::NAME_FIELD, 5),
                                             $this->setBoostField(CitySearchMapping::INTERNATIONAL_NAME_FIELD, 4),
                                             $this->setBoostField(CitySearchMapping::TRANSLIT_NAME_FIELD, 3),
                                         ])
                                         ->setQuery($searchText)
                                         ->setOperator(MultiMatch::OPERATOR_OR)
                                         ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
        ]);

        $this->setSortingQuery([
            $this->_sortingFactory->getFieldSort('_score', 'desc'),
            //$this->_sortingFactory->getFieldSort(CitySearchMapping::NAME_FIELD)
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
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchTopCityByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        $cities = $this->searchCityByName($userId, $searchText, $point);

        $cities = ArrayHelper::index($cities[CitySearchMapping::CONTEXT], 'id');

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
                        EventsSearchMapping::getMarkersSearchFilter($filter, $userId)
                    ),
                ]),
                // могу помочь
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        HelpOffersSearchMapping::getMarkersSearchFilter($filter, $userId)
                    ),
                ]),
                // пользователи
                /*$filter->getBoolAndFilter([
                    $filter->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        PeopleSearchMapping::getMarkersSearchFilter($filter, $userId)
                    )
                ]),
                // места БЕЗ скидок
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        PlaceSearchMapping::getMarkersSearchFilter($filter, $userId)
                    )
                ]),*/
                // скидочные места
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        DiscountsSearchMapping::getMarkersSearchFilter($filter, $userId)
                    ),
                ]),
                // русские места
                $filter->getBoolAndFilter([
                    $filter->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $filter->getBoolAndFilter(
                        RusPlaceSearchMapping::getMarkersSearchFilter($filter, $userId)
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
     * @param string $userId
     * @param GeoPointServiceInterface $point
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function getTopCitiesList($userId, GeoPointServiceInterface $point, $skip = self::DEFAULT_SKIP_CITIES, $count = self::DEFAULT_COUNT_CITIES)
    {
        /**
         * Получаем начальную точку на карте Европы
         * для того чтобы от нее сделать радиус максимальный
         * по европе только
         */
        $city = $this->searchCityByName(
            $userId,
            self::DEFAULT_START_CITY,
            $point
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
            $this->_queryFilterFactory->getBoolOrFilter([
                // события
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(EventsSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        EventsSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
                    ),
                ]),
                // могу помочь
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        HelpOffersSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
                    ),
                ]),
                // пользователи
                /*$this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(PeopleSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        PeopleSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
                    )
                ]),
                // места БЕЗ скидок
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        PlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
                    )
                ]),*/
                // скидочные места
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        DiscountsSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
                    ),
                ]),
                // русские места
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTypeFilter(PlaceSearchMapping::CONTEXT),
                    $this->_queryFilterFactory->getBoolAndFilter(
                        RusPlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId)
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

        return $this->searchDocuments($queryMatch);
    }
}