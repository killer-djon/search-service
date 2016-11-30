<?php
/**
 * Основной сервиса поиска людей в еластике
 * формирование условий запроса к еластику
 */
namespace RP\SearchBundle\Services;

use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface;
use Common\Core\Facade\Search\QueryFactory\SearchEngine;
use Common\Core\Facade\Search\QueryFactory\SearchServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Query;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use RP\SearchBundle\Services\Transformers\CityTransformer;
use RP\SearchBundle\Services\Transformers\PeopleTransformer;
use RP\SearchBundle\Services\Transformers\PlaceTypeTransformer;
use RP\SearchBundle\Services\Transformers\TagNameTransformer;

class AbstractSearchService extends SearchEngine implements SearchServiceInterface
{
    use SearchServiceTrait;

    /**
     * Дополнительные опции скрипт-функции
     * которые устанавливаются для достижения цели
     * манипуляции ранжированием
     *
     * @var array $_scriptFunctionOptions
     */
    private $_scriptFunctionOptions = [];

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
     * набор полей для подсветки при поиске
     *
     * @var array $_highlightQueryData
     */
    private $_highlightQueryData = [];

    /**
     * @var CityTransformer $cityTransformer
     */
    public $cityTransformer;

    /**
     * @var PeopleTransformer $peopleTransformer
     */
    public $peopleTransformer;

    /**
     * @var PlaceTypeTransformer $placeTypeTransformer
     */
    public $placeTypeTransformer;

    /**
     * @var TagNameTransformer $tagNamesTransformer
     */
    public $tagNamesTransformer;

    /**
     * Оперделяем проеобразователь для городов
     *
     * @param CityTransformer $cityTransformer
     * @return void
     */
    public function setCityTransformer(CityTransformer $cityTransformer)
    {
        $this->cityTransformer = $cityTransformer;
    }

    /**
     * Оперделяем проеобразователь интересов
     *
     * @param TagNameTransformer $tagNamesTransformer
     * @return void
     */
    public function setTagNamesTransformer(TagNameTransformer $tagNamesTransformer)
    {
        $this->tagNamesTransformer = $tagNamesTransformer;
    }

    /**
     * Оперделяем проеобразователь для городов
     *
     * @param PlaceTypeTransformer $placeTypeTransformer
     * @return void
     */
    public function setPlaceTypeTransformer(PlaceTypeTransformer $placeTypeTransformer)
    {
        $this->placeTypeTransformer = $placeTypeTransformer;
    }

    /**
     * Оперделяем проеобразователь для городов
     *
     * @param PeopleTransformer $peopleTransformer
     * @return void
     */
    public function setPeopleTransformer(PeopleTransformer $peopleTransformer)
    {
        $this->peopleTransformer = $peopleTransformer;
    }

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
     * Ручная очистка полей скриптов
     * необходимо для случая многотипного поиска
     *
     * @return SearchServiceInterface
     */
    public function clearScriptFields()
    {
        $this->_scriptFields = [];

        return $this;
    }

    public function setScriptFunctionOption(array $options)
    {
        $this->_scriptFunctionOptions = $options;
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
     * Ручная очистка фильтра
     * необходимо для случая многотипного поиска
     *
     * @return SearchServiceInterface
     */
    public function clearFilter()
    {
        $this->_filterQueryData = [];

        return $this;
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
        //$this->_aggregationQueryData = $aggregations;
        if (!empty($aggregations)) {
            foreach ($aggregations as $key => $aggregation) {
                $this->_aggregationQueryData[] = $aggregation;
            }
        }
    }

    /**
     * Формируем условие сортировки
     *
     * @param array $sortings Массив c условиями сортировки данных
     * @return SearchServiceInterface
     */
    public function setSortingQuery(array $sortings = [])
    {
        if (AbstractTransformer::is_assoc($sortings)) {
            $this->_sortingQueryData = [$sortings];
        } else {
            $this->_sortingQueryData = $sortings;
        }
    }

    /**
     * Устанавливаем условия подсветки для искомых значений полей
     *
     * @param array $highlights Набор полей с параметрами подсветки
     * @return SearchServiceInterface
     */
    public function setHighlightQuery(array $highlights = [])
    {
        $this->_highlightQueryData = $highlights;
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

        if (!is_null($this->_scriptFunctions) && !empty($this->_scriptFunctions)) {
            $customScore = new \Elastica\Query\FunctionScore();
            $customScore->setQuery($searchQuery);

            if (!is_null($this->_scriptFunctionOptions) && !empty($this->_scriptFunctionOptions)) {
                foreach ($this->_scriptFunctionOptions as $key => $scriptFunctionOptionValue) {
                    $methodSetterName = 'set' . ucfirst($key);
                    if (method_exists($customScore, $methodSetterName)) {
                        $customScore->{$methodSetterName}($scriptFunctionOptionValue);
                    }
                }
            }

            foreach ($this->_scriptFunctions as $scriptFunction) {
                $customScore->addScriptScoreFunction($scriptFunction);
            }

            $searchQuery = $customScore;
        }
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

            if (!is_null($this->_scriptFunctionOptions) && !empty($this->_scriptFunctionOptions)) {
                foreach ($this->_scriptFunctionOptions as $key => $scriptFunctionOptionValue) {
                    $methodSetterName = 'set' . ucfirst($key);
                    if (method_exists($customScore, $methodSetterName)) {
                        $customScore->{$methodSetterName}($scriptFunctionOptionValue);
                    }
                }
            }

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
        $queryFactory = $query->getQueryFactory();

        return $queryFactory;
    }

    /**
     * Сбрасываем условия предыдущего конструктора запросов
     * необходимо для того чтобы не собирать в кучу из разных запросов
     * условия
     *
     * @return SearchServiceInterface
     */
    public function clearQueryFactory()
    {
        $this->_conditionQueryMustData = [];
        $this->_conditionQueryShouldData = [];
        $this->_conditionQueryMustNotData = [];
        $this->_filterQueryData = [];
        $this->_fieldsSelected = [];
        $this->_highlightQueryData = [];
        $this->_aggregationQueryData = [];
        $this->_scriptFields = null;
        $this->_scriptFunctions = null;
        $this->_sortingQueryData = [];

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
                     ->setHighlight($this->_highlightQueryData)// @todo доработать
                     ->setSort($this->_sortingQueryData)
                     ->setSize(is_null($count) ? self::DEFAULT_SIZE_QUERY : (int)$count)
                     ->setFrom(is_null($skip) ? self::DEFAULT_SKIP_QUERY : $skip);
    }

    /**
     * Формируем скриптовый запрос для рассчета разных фишек
     *
     * @param \Elastica\Script[] $scripts
     * @param array $scriptOptions
     * @return \Elastica\Query\AbstractQuery
     */
    public function setScriptFunctions(array $scripts, array $scriptOptions = null)
    {
        foreach ($scripts as $script) {
            if ($script instanceof \Elastica\Script) {
                $this->_scriptFunctions[] = $script;
            }
        }

        if (!is_null($scriptOptions) && !empty($scriptOptions)) {
            $this->setScriptFunctionOption($scriptOptions);
        }
    }

    /**
     * Получить пользователя по его ID
     * проксируем к методу получения записи по ID контекста
     *
     * @param string $userId ID пользователя для получения
     * @throws SearchServiceException
     * @return UserProfileService
     */
    public function getUserById($userId)
    {
        try {
            $user = $this->searchRecordById(PeopleSearchMapping::CONTEXT, PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM, $userId);

            return new UserProfileService($user);
        } catch (SearchServiceException $e) {
            throw new SearchServiceException('User not found with ID ' . $userId);
        }
    }

    /**
     * Поиск единственной записи по ID в заданном контексте
     *
     * @param string $context Контекст поиска
     * @param string $fieldId Поле идентификатора
     * @param string $recordId ID искомой записи
     * @return array|null Найденный результат или ничего
     */
    public function searchRecordById($context, $fieldId, $recordId)
    {
        /** указываем условия запроса */
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery($fieldId, $recordId),
        ]);

        /** аггрегируем запрос чтобы получить единственный результат а не многомерный массив с одним элементом */
        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTopHitsAggregation(),
        ]);

        /** генерируем объект запроса */
        $query = $this->createQuery();

        /** находим ползователя в базе еластика по его ID */
        $userSearchDocument = $this->searchSingleDocuments($query, $context);

        /** Возращаем объект профиля пользователя */
        return $userSearchDocument;
    }

}