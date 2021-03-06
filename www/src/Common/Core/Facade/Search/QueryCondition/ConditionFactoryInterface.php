<?php
namespace Common\Core\Facade\Search\QueryCondition;

use Elastica\Filter\AbstractFilter;
use Elastica\Query\AbstractQuery;

interface ConditionFactoryInterface
{
    /**
     * Минимальный коэффициент схожести по-умолчанию
     */
    const MIN_SIMILARITY = 0.5;

    /**
     * Условие запроса поиска всех документов
     *
     * @abstract
     * @link http://www.elasticsearch.org/guide/reference/query-dsl/match-all-query.html
     * @return \Elastica\Query\MatchAll
     */
    public function getMatchAllQuery();

    /**
     * Условие запроса поиска всех документов
     *
     * @param string $fieldName
     * @param string $queryString
     * @param float $boost
     * @param int $prefixLength
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
     * @return \Elastica\Query\Match
     */
    public function getMatchQuery($fieldName, $queryString, $boost = 1.0, $prefixLength = null);

    /**
     * Условие запроса по полной фразе
     *
     * @param string $fieldName
     * @param string $queryString
     * @param float $boost
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#_phrase
     * @return \Elastica\Query\MatchPhrase
     */
    public function getMatchPhraseQuery($fieldName, $queryString, $boost = 1.0);

    /**
     * Находит все слова на своих местах
     *
     * @param string $fieldName
     * @param string $queryString
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#_phrase
     * @return \Elastica\Query\MatchPhrase
     */
    public function getMatchPhrasePrefixQuery($fieldName, $queryString);

    /**
     * Условие запроса по полной фразе
     *
     * @param string $path
     * @param AbstractQuery $queryString
     * @param string $scoreMode (max, min, avg, sum)
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-query.html
     * @return \Elastica\Query\Nested
     */
    public function getNestedQuery($path, AbstractQuery $queryString = null, $scoreMode = null);

    /**
     * Получаем объект запроса поиска по совпадению в полях
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
     * @return \Elastica\Query\MultiMatch
     */
    public function getMultiMatchQuery();

    /**
     * Условие запроса поиска по похожести текста
     *
     * @param string $fieldName
     * @param string $textToSearch
     * @return \Elastica\Query\Fuzzy
     */
    public function getFuzzyQuery($fieldName, $textToSearch);

    /**
     * Условие префиксного запроса
     *
     * @abstract
     * @param string $fieldName
     * @param string $value
     * @return \Elastica\Query\Prefix
     */
    public function getPrefixQuery($fieldName, $value, $boost = 1.0);

    /**
     * Условие в виде логических комбинаций других условий
     *
     * @abstract
     * @param array $must
     * @param array $should
     * @param array $mustNot
     * @return \Elastica\Query\Bool
     */
    public function getBoolQuery(array $must, array $should, array $mustNot);

    /**
     * Условие расчета кастомного значения очков релевантности результатов поиска
     *
     * @param QueryConditionInterface $query
     * @param string $script
     * @param \Elastica\Filter\AbstractFilter $filter
     * @param array $params
     * @param string $langScript
     * @return \Elastica\Query\FunctionScore
     */
    public function getCustomScoreQuery(AbstractQuery $query, $script, AbstractFilter $filter = null, array $params = [], $lang = \Elastica\Script::LANG_GROOVY);

    /**
     * Условие точного совпадения запроса по заданному терму
     *
     * @param string $fieldName
     * @param string $value
     * @return mixed
     */
    public function getTermQuery($fieldName, $value);

    /**
     * Условие совпадения запроса с перечисленным списком термов
     *
     * @param string $fieldName
     * @param array $values
     * @return mixed
     */
    public function getTermsQuery($fieldName, array $values);

    /**
     * Условие запроса поиска по ids
     *
     * @param array $ids
     * @return mixed
     */
    public function getIdsQuery(array $ids);

    /**
     * Условаие запроса по совпадению поля (в зависимости от анализатора конкретного поля).
     *
     * @param string|array $fieldsName
     * @param string $value
     * @param bool $analyzer
     * @param float $boost Бустинг запроса
     * @param int $phraseSlop
     * @return \Elastica\Query\QueryString
     */
    public function getFieldQuery($fieldsName, $value, $analyzer = true, $boost = 1.0, $phraseSlop = 0);

    /**
     * Типа regexp запроса.
     *
     * @param string $fieldName
     * @param string $value
     * @param float $boost Бустинг запроса
     * @return mixed
     */
    public function getWildCardQuery($fieldName, $value, $boost = 1.0);

    /**
     * Объединяющий несколько условий для UNION результата
     *
     * @param \Elastica\Query\AbstractQuery|array $query Query
     * @param float $boost
     * @param float $tieBreaker
     * @return \Elastica\Query\DisMax
     */
    public function getDisMaxQuery($query, $boost = 1.0, $tieBreaker = 0.0);

    /**
     * Типа regexp запроса.
     *
     * @param string $queryString Поисковый запрос
     * @param array $fields Массив полей
     * @param string $operator ( 'or' 'and' )
     * @param string|int $shouldMatch Процент совпадения
     * @return \Elastica\Query\SimpleQueryString
     */
    public function getSimpleStringQuery($queryString, array $fields = [], $operator = MultiMatch::OPERATOR_AND, $shouldMatch = '50%');

    /**
     * Условие запроса с регулярным выражением
     * @param string $fieldName Название поля
     * @param string $query Строка запроса
     * @param float $boost
     * @return \Elastica\Query\Regexp
     */
    public function getRegexpQuery($fieldName, $query, $boost = 1.0);

}