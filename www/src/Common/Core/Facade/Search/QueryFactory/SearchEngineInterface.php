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
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param string $context Search type
     * @param bool $setSource (default: true) Показать исходные данные объекта в ответе
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchDocuments(\Elastica\Query $elasticQuery, $context = null, $setSource = true);

    /**
     * Поиск единственного документа по ID
     *
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param string|null $context Search type
     * @param bool $setSource (default: true) Показать исходные данные объекта в ответе
     * @throws ElasticsearchException
     * @return array|null results
     */
    public function searchSingleDocuments(\Elastica\Query $elasticQuery, $context = null, $setSource = true);

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