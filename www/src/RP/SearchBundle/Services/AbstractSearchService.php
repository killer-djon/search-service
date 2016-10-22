<?php
/**
 * Основной сервиса поиска людей в еластике
 * формирование условий запроса к еластику
 */
namespace RP\SearchBundle\Services;

use Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface;
use Common\Core\Facade\Search\QueryFactory\SearchEngine;
use Common\Core\Facade\Search\QueryFactory\SearchServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Query;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;

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
     * @var \Elastica\Script[]
     */
    protected $_scriptFunctions;

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
        if (!is_null($this->_scriptFields)) {
            $this->_scriptFields = array_merge($this->_scriptFields, $scriptFields);
        } else {
            $this->_scriptFields = $scriptFields;
        }
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
        if (!is_null($this->_filterQueryData)) {
            $this->_filterQueryData = array_merge($this->_filterQueryData, $filters);
        } else {
            $this->_filterQueryData = $filters;
        }
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

        $queryFactory = $this->setQueryOptions(
            $this->_queryFactory->setQueryFactory($searchQuery),
            $skip,
            $count
        );

        $this->clearQueryFactory();

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
        $searchText = null,
        array $fields = [],
        $skip = 0,
        $count = null,
        $operator = \Elastica\Query\MultiMatch::OPERATOR_OR,
        $type = \Elastica\Query\MultiMatch::TYPE_CROSS_FIELDS
    ) {
        $matchQuery = $this->_queryConditionFactory->getMatchAllQuery();

        if (!is_null($searchText) || !empty($searchText)) {
            $matchQuery = $this->_queryConditionFactory->getMultiMatchQuery();
            $matchQuery->setQuery($searchText);
            $matchQuery->setOperator($operator);
            $matchQuery->setType($type);
            $matchQuery->setFields($fields);
        }

        if (!is_null($this->_scriptFunctions) && !empty($this->_scriptFunctions)) {
            $customScore = new \Elastica\Query\FunctionScore();
            $customScore->setQuery($matchQuery);

            foreach ($this->_scriptFunctions as $scriptFunction) {
                $customScore->addScriptScoreFunction($scriptFunction);
            }

            $matchQuery = $customScore;
        }

        // Применить набор фильтров
        if (sizeof($this->_filterQueryData) > 0) {
            $filter = $this->_queryFilterFactory->getBoolAndFilter($this->_filterQueryData);
            $matchQuery = new \Elastica\Query\Filtered($matchQuery, $filter);
        }

        $query = $this->setQueryOptions(
            $this->_queryFactory->setQueryFactory($matchQuery),
            $skip,
            $count
        );
        $this->clearQueryFactory();

        return $query->getQueryFactory();
    }

    /**
     * Сбрасываем условия предыдущего конструктора запросов
     * необходимо для того чтобы не собирать в кучу из разных запросов
     * условия
     *
     * @return SearchServiceInterface
     */
    private function clearQueryFactory()
    {
        $this->setConditionQueryShould([]);
        $this->setAggregationQuery([]);
        $this->setConditionQueryMust([]);

        return $this;
    }

    /**
     * Дополняем объект запроса опциями
     * и возвращаем так же объект запроса
     *
     * @param QueryFactoryInterface $query
     * @param int $skip
     * @param int $count
     * @return QueryFactoryInterface
     */
    private function setQueryOptions(QueryFactoryInterface $query, $skip = 0, $count = null)
    {
        return $query->setAggregations($this->_aggregationQueryData)
                     ->setFields($this->_fieldsSelected)
                     ->setScriptFields($this->_scriptFields)
                     ->setHighlight([])// @todo доработать
                     ->setSort($this->_sortingQueryData)
                     ->setSize($count)
                     ->setFrom($skip);
    }

    /**
     * Формируем скриптовый запрос для рассчета разных фишек
     *
     * @param \Elastica\Script[] $scripts
     * @return \Elastica\Query\AbstractQuery
     */
    public function setScriptFunctions(array $scripts)
    {
        foreach ($scripts as $script) {
            if ($script instanceof \Elastica\Script) {
                $this->_scriptFunctions[] = $script;
            }
        }
    }

    /**
     * Получаем пользователя из еластика по его ID
     *
     * @param string $userId ID пользователя
     * @return UserProfileService
     */
    public function getUserById($userId)
    {
        /** указываем условия запроса */
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM, $userId),
        ]);

        /** аггрегируем запрос чтобы получить единственный результат а не многомерный массив с одним элементом */
        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTopHitsAggregation(),
        ]);

        /** генерируем объект запроса */
        $query = $this->createQuery();

        /** находим ползователя в базе еластика по его ID */
        $userSearchDocument = $this->searchSingleDocuments(PeopleSearchMapping::CONTEXT, $query);

        /** Возращаем объект профиля пользователя */
        return new UserProfileService($userSearchDocument);
    }
    
    /**
     * Получаем место по его ID
     *
     * @param string $placeId ID места
     * @return PlaceService
     */
    public function getPlaceById($placeId)
    {
        /** указываем условия запроса */
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PlaceSearchMapping::PLACE_ID_FIELD, $placeId),
        ]);

        /** аггрегируем запрос чтобы получить единственный результат а не многомерный массив с одним элементом */
        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTopHitsAggregation(),
        ]);

        /** генерируем объект запроса */
        $query = $this->createQuery();

        /** находим ползователя в базе еластика по его ID */
        $placeSearchDocument = $this->searchSingleDocuments(PlaceSearchMapping::CONTEXT, $query);

        /** Возращаем объект профиля пользователя */
        //return new UserProfileService($placeSearchDocument); - @to do надо сделать сервис места
        return $placeSearchDocument;
    }
}