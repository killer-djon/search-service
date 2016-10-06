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
     * Набор полей со скриптами
     * т.е. inline скрипты например
     *
     * @var array $_scriptFields
     */
    private $_scriptFields;

    /**
     * Объект скрипта в запросе
     *
     * @var \Elastica\Script
     */
    protected $_scriptFunction;

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
     * Набор полей для выбора
     * необходимо чтобы не каждый раз выбирать все поля
     *
     * @var array $_fieldsSelected
     */
    private $_fieldsSelected = [];

    /**
     * Определяем какой набор полей нам нужно выбрать
     *
     * @param array $fields набор полей для выборки
     * @return SearchServiceInterface
     */
    public function setFieldsQuery(array $fields = [])
    {
        $this->_fieldsSelected = $fields;
    }

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
     * Устанавливаем набор полей со скриптами
     * для определения custom значений в рассчете
     *
     * @param array $scriptFields
     * @return void
     */
    public function setScriptFields(array $scriptFields = null)
    {
        $this->_scriptFields = $scriptFields;
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

        $queryFactory = $this->_queryFactory
            ->setQueryFactory($searchQuery)
            ->setSize($count)
            ->setFrom($skip)
            ->setAggregations($this->_aggregationQueryData)
            ->setFields($this->_fieldsSelected)
            ->setScriptFields($this->_scriptFields)
            ->setSort($this->_sortingQueryData);

        return $queryFactory->getQueryFactory();
    }

    /**
     * Создание объект поиска на основе совпадения по полям
     *
     * @param string $searchText Текст поиска
     * @param array $fields Набор полей учавствующих в поиске
     * @param int $skip
     * @param int $count
     * @param string $operator Оператор логического выражения ( or and )
     * @param string $type Тип перебора полей в поиске
     * @return \Elastica\Query
     */
    public function createMatchQuery(
        $searchText,
        array $fields,
        $skip = 0,
        $count = null,
        $operator = \Elastica\Query\MultiMatch::OPERATOR_OR,
        $type = \Elastica\Query\MultiMatch::TYPE_CROSS_FIELDS
    ) {

        $matchQuery = $this->_queryConditionFactory->getMultiMatchQuery();

        if (!empty($searchText)) {
            $matchQuery = $this->_queryConditionFactory->getMultiMatchQuery();
            $matchQuery->setQuery($searchText);
            $matchQuery->setOperator($operator);
            $matchQuery->setType($type);
            $matchQuery->setFields($fields);
        }

        if (!is_null($this->_scriptFunction)) {
            $customScore = new \Elastica\Query\FunctionScore();
            $customScore->setQuery($matchQuery);
            $matchQuery = $customScore->addScriptScoreFunction($this->_scriptFunction);
        }

        // Применить набор фильтров
        if (sizeof($this->_filterQueryData) > 0) {
            $filter = $this->_queryFilterFactory->getBoolAndFilter($this->_filterQueryData);
            $matchQuery = new \Elastica\Query\Filtered($matchQuery, $filter);
        }

        $query = $this->_queryFactory
            ->setQueryFactory($matchQuery)
            ->setSize($count)
            ->setFrom($skip)
            ->setAggregations($this->_aggregationQueryData)
            ->setFields($this->_fieldsSelected)
            ->setScriptFields($this->_scriptFields)
            ->setSort($this->_sortingQueryData);

        return $query->getQueryFactory();
    }

    public function setScriptFunction($script)
    {
        $this->_scriptFunction = new \Elastica\Script($script);
        $this->_scriptFunction->setLang(\Elastica\Script::LANG_GROOVY);

        return $this;
    }
}