<?php
/**
 * Интерфейс устанавливающий правила работы с поисковым движком
 * данный интерфейс определят какие методы должны выполнятся
 * если используется поиск через ElasticSearch
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Exception\ElasticsearchException;

interface SearchEngineInterface
{

    /**
     * Метод осуществляет поиск в еластике
     * при помощи сервиса fos_elastica.finder.%s.%s
     *
     * @param string Search type
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function findDocuments($context, $query, $options = []);

    /**
     * Индексный поиск в еластике
     * т.е. поиск на основе индекса fos_elastica.index.%s.%s
     *
     * @param string Search type
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchDocuments($context, \Elastica\Query $elasticQuery);

    /**
     *
     */
    public function transformResult(array $resultSets);

    /**
     * @return \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface
     */
    public function getQueryCondition();

    /**
     * @return \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface
     */
    public function getFilterCondition();

}