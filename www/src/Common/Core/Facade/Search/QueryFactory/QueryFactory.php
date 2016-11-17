<?php
/**
 * Базовый класс объекта запроса
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Query\AbstractQuery;

class QueryFactory implements QueryFactoryInterface
{
    /**
     * Объект запроса
     *
     * @var \Elastica\Query $_queryObject
     */
    private $_queryObject;

    /**
     * Форимруем объект запроса еластика
     * который в последствии и будет представлять сам запрос
     *
     * @param \Elastica\Query\AbstractQuery $query OPTIONAL Query object (default = null)
     * @return QueryFactoryInterface
     */
    public function setQueryFactory(AbstractQuery $query)
    {
        $this->_queryObject = new \Elastica\Query($query);

        return $this;
    }

    /**
     * Получить сформированный объект запроса
     *
     * @return \Elastica\Query
     */
    public function getQueryFactory()
    {
        return $this->_queryObject;
    }

    /**
     * Sets the start from which the search results should be returned.
     *
     * @param int $from
     * @return QueryFactoryInterface
     */
    public function setFrom($from = null)
    {
        if (!is_null($from)) {
            $this->_queryObject->setFrom((int)$from);
        }

        return $this;
    }

    /**
     * Sets maximum number of results for this query.
     *
     * @param int $size OPTIONAL Maximal number of results for query (default = 10)
     * @return QueryFactoryInterface
     */
    public function setSize($size = null)
    {
        if (!is_null($size)) {
            $this->_queryObject->setSize((int)$size);
        }

        return $this;
    }

    /**
     * Устанавливаем набор полей которые должны вернутся в результате
     *
     * @param array $fields Fields to be returned
     * @return QueryFactoryInterface
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-fields.html
     */
    public function setFields(array $fields = [])
    {
        if (!empty($fields)) {
            $this->_queryObject->setFields($fields);
        }

        return $this;
    }

    /**
     * Adds an Aggregation to the query.
     *
     * @param \Elastica\Aggregation\AbstractAggregation[] $aggs
     * @return $this
     */
    public function setAggregations(array $aggs = [])
    {
        if (!empty($aggs)) {
            foreach ($aggs as $agg) {
                $this->_queryObject->addAggregation($agg);
            }
        }

        return $this;
    }

    /**
     * Sets sort arguments for the query
     * Replaces existing values.
     *
     * @param array $sortArgs Sorting arguments
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
     */
    public function setSort(array $sortArgs = [])
    {
        if (!empty($sortArgs)) {
            $this->_queryObject->setSort($sortArgs);
        }

        return $this;
    }

    /**
     * Sets highlight arguments for the query.
     *
     * @param array $highlightArgs Set all highlight arguments
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     */
    public function setHighlight(array $highlightArgs = [])
    {
        $highlight = [
            "pre_tags"  => ["<em>"],
            "post_tags" => ["</em>"],
            "require_field_match" => true,
            "fields"    => [],
        ];

        if (!empty($highlightArgs)) {
            $highlight['fields'] = $highlightArgs;
            $this->_queryObject->setHighlight($highlight);
        }

        return $this;
    }

    /**
     * Set script fields.
     *
     * @param array|\Elastica\ScriptFields $scriptFields Script fields
     * @return $this
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-script-fields.html
     */
    public function setScriptFields($scriptFields = null)
    {
        if (!is_null($scriptFields)) {
            $this->_queryObject->setScriptFields($scriptFields);
        }

        return $this;
    }


    /**
     * Allows filtering of documents based on a minimum score.
     *
     * @param float $minScore Minimum score to filter documents by
     * @throws \Elastica\Exception\InvalidException
     * @return $this
     */
    public function setMinScore($minScore = null)
    {
        if (!is_null($minScore) && is_numeric($minScore)) {
            $this->_queryObject->setMinScore((float)$minScore);
        }

        return $this;
    }

    /**
     * Converts all query params to an array.
     *
     * @return array Query array
     */
    public function toArray()
    {
        return $this->_queryObject->toArray();
    }
}