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
     * Поиск единственного документа по ID
     *
     * @param string Search type
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchSingleDocuments($context, \Elastica\Query $elasticQuery);

    /**
     * Преобразование полученного результат из еластика
     * получаем набор данных \Elastica\Result
     * который тупо переводим в массив для вывода результата
     *
     * @param \Elastica\ResultSet $resultSets
     * @return array $data Набор данных для вывода в результат
     */
    public function transformResult(\Elastica\ResultSet $resultSets);

    /**
     * @return \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface
     */
    public function getQueryCondition();

    /**
     * @return \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface
     */
    public function getFilterCondition();

}