<?php
/**
 * Интерфейс объекта запроса \Elastica\Query
 * просто проксирование функционала
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Query\AbstractQuery;

interface QueryFactoryInterface
{
    /**
     * Форимруем объект запроса еластика
     * который в последствии и будет представлять сам запрос
     *
     * @param \Elastica\Query\AbstractQuery $query Query object
     * @return QueryFactoryInterface
     */
    public function setQueryFactory(AbstractQuery $query);

    /**
     * Получить сформированный объект запроса
     *
     * @return \Elastica\Query
     */
    public function getQueryFactory();

    /**
     * Sets the start from which the search results should be returned.
     *
     * @param int $from
     * @return QueryFactoryInterface
     */
    public function setFrom($from = null);

    /**
     * Sets maximum number of results for this query.
     *
     * @param int $size OPTIONAL Maximal number of results for query (default = 10)
     * @return QueryFactoryInterface
     */
    public function setSize($size = null);

    /**
     * Устанавливаем набор полей которые должны вернутся в результате
     *
     * @param array $fields Fields to be returned
     * @return QueryFactoryInterface
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-fields.html
     */
    public function setFields(array $fields = []);

    /**
     * Adds an Aggregation to the query.
     *
     * @param \Elastica\Aggregation\AbstractAggregation[] $aggs
     * @return $this
     */
    public function setAggregations(array $aggs = []);

    /**
     * Set script fields.
     *
     * @param array|\Elastica\ScriptFields $scriptFields Script fields
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-script-fields.html
     */
    public function setScriptFields(array $scriptFields = []);

    /**
     * Sets sort arguments for the query
     * Replaces existing values.
     *
     * @param array $sortArgs Sorting arguments
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
     */
    public function setSort(array $sortArgs = []);

    /**
     * Sets highlight arguments for the query.
     *
     * @param array $highlightArgs Set all highlight arguments
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     */
    public function setHighlight(array $highlightArgs = []);

    /**
     * Allows filtering of documents based on a minimum score.
     *
     * @param float $minScore Minimum score to filter documents by
     * @throws \Elastica\Exception\InvalidException
     * @return $this
     */
    public function setMinScore($minScore = null);

    /**
     * Converts all query params to an array.
     *
     * @return array Query array
     */
    public function toArray();

}