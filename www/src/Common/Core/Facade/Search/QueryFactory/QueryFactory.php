<?php
namespace Common\Core\Facade\Search\QueryFactory;

class QueryFactory implements QueryFactoryInterface
{
    public function createQuery(){}

    public function getQuery(){}

    public function setFrom(){}

    public function setSize(){}

    public function setLimit(){}

    /**
     * Метод является устаревшив в еластике
     * поэтому мы проксируем вызов метода setPostFilter
     */
    public function setFilter(){}

    public function setSort(){}

    public function setHighlight(){}

    public function setExplain(){}

    public function setFields(){}

    public function setScriptFields(){}

    public function addAggregation(){}

    public function setMinScore(){}

    public function setSuggest(){}

    public function toArray(){}
}