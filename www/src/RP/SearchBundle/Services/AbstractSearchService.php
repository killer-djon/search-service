<?php
/**
 * Основной сервиса поиска людей в еластике
 * формирование условий запроса к еластику
 */
namespace RP\SearchBundle\Services;

use Common\Core\Facade\Search\QueryFactory\SearchEngine;
use Common\Core\Facade\Search\QueryFactory\SearchServiceInterface;

class AbstractSearchService extends SearchEngine implements SearchServiceInterface
{
    /**
     * Набор условий запроса
     *
     * @var array $_conditionQueryMustData
     */
    private $_conditionQueryMustData = [];

    /**
     * Набор условий запроса
     *
     * @var array $_conditionQueryMustNotData
     */
    private $_conditionQueryMustNotData = [];

    /**
     * Набор условий запроса
     *
     * @var array $_conditionQueryShouldData
     */
    private $_conditionQueryShouldData = [];

    /**
     * Набор фильтров запроса
     *
     * @var array $_filterQuery
     */
    private $_filterQueryData = [];

    /**
     * Набор аггрегированных функций запроса
     *
     * @var array $_aggregationQuery
     */
    private $_aggregationQueryData = [];

    /**
     * Набор условий сортировки
     *
     * @var array $_sortingQuery
     */
    private $_sortingQueryData = [];

    /**
     * Метод который формирует условия запроса
     *
     * @param array $must Массив условий запроса $must
     * @return SearchServiceInterface
     */
    public function setConditionQueryMust(array $must = [])
    {
        $this->_conditionQueryMustData = $must;
    }

    /**
     * Метод который формирует условия запроса
     *
     * @param array $mustNot Массив условий запроса $mustNot
     * @return SearchServiceInterface
     */
    public function setConditionQueryMustNot(array $mustNot = [])
    {
        $this->_conditionQueryMustNotData = $mustNot;
    }

    /**
     * Метод который формирует условия запроса
     *
     * @param array $should Массив условий запроса $should
     * @return SearchServiceInterface
     */
    public function setConditionQueryShould(array $should = [])
    {
        $this->_conditionQueryShouldData = $should;
    }

    /**
     * Метод который формирует набор фильтров для запроса
     *
     * @param array $filters Массив фильтров
     * @return SearchServiceInterface
     */
    public function setFilterQuery(array $filters = [])
    {
        $this->_filterQueryData = $filters;
    }

    /**
     * Создаем аггрегированные условия запроса
     * так называемый aggregation (например суммирование результата по условию)
     * как аггрегированные функции
     *
     * @param array $aggregations Набор аггрегированных функций
     * @return SearchServiceInterface
     */
    public function setAggregationQuery(array $aggregations = [])
    {
        $this->_aggregationQueryData = $aggregations;
    }

    /**
     * Формируем условие сортировки
     *
     * @param array $sortings Массив c условиями сортировки данных
     * @return SearchServiceInterface
     */
    public function setSortingQuery(array $sortings = [])
    {
        $this->_sortingQueryData = $sortings;
    }

    /**
     * Метод который собирает в один запрос все условия
     *
     * @param int $skip
     * @param int $count
     * @return \Elastica\Query
     */
    public function createQuery($skip = 0, $count = null)
    {
        // Формируем объект запроса \Elastica\Query\Bool
        $searchQuery = $this->_queryConditionFactory->getBoolQuery(
            $this->_conditionQueryMustData,
            $this->_conditionQueryShouldData,
            $this->_conditionQueryMustNotData
        );

        // Применить набор фильтров
        if (sizeof($this->_filterQueryData) > 0) {
            $filter = $this->_queryFilterFactory->getBoolAndFilter($this->_filterQueryData);
            $searchQuery = new \Elastica\Query\Filtered($searchQuery, $filter);
        }

        $queryFactory = $this->_queryFactory->setQueryFactory($searchQuery)
                                            ->setSize($count)
                                            ->setFrom($skip)
                                            ->setAggregations($this->_aggregationQueryData)
                                            ->setMinScore(0.1)
                                            ->setSort($this->_sortingQueryData);

        return $queryFactory->getQueryFactory();
    }
}