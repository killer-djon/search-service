<?php
/**
 * Сервис поиска по условиям городов
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\Location;
use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use Common\Core\Facade\Service\Geo\GeoPointService;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\AbstractSearchMapping;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;

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
            $this->_queryFilterFactory->getTermsFilter(CitySearchMapping::CITY_TYPE_FIELD, [
                Location::CITY_TYPE,
                Location::TOWN_TYPE,
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
	        	->setType(MultiMatch::TYPE_PHRASE_PREFIX)
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
     * Поиск наиболее полулярных городов
     * популярность будет определяться суммой
     * (пользователей + мест + скидок) / коефициент
     *
     * @param int $skip
     * @param int $count
     *
     * @return array
     */
    public function getTopCitiesList($skip = self::DEFAULT_SKIP_CITIES, $count = self::DEFAULT_COUNT_CITIES)
    {
        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTermsAggregation(
                AbstractSearchMapping::LOCATION_CITY_ID_FIELD
            )->setOrder('_count', 'desc')->setSize($count),
            $this->_queryAggregationFactory->getFilterAggregation(
                'empty_location',
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getNotFilter(
                        $this->_queryFilterFactory->getMissingFilter(
                            AbstractSearchMapping::LOCATION_CITY_FIELD
                        )
                    ),
                    $this->_queryFilterFactory->getTermFilter(['isEnabled' => true])
                ])
            )
        ]);

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, PeopleSearchMapping::CONTEXT);
    }
}