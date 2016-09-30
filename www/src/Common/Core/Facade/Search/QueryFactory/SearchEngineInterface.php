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
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param int $limit How many results to get
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function finderSearch($query, $limit = null, $options = []);

    /**
     * Метод осуществляет гибридный поиск в еластике
     * при помощи сервиса fos_elastica.finder.%s.%s
     *
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param int $limit How many results to get
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function finderSearchHybrid($query, $limit = null, $options = []);

    /**
     * Индексный поиск в еластике
     * т.е. поиск на основе индекса fos_elastica.index.%s.%s
     *
     * @throws ElasticsearchException
     */
    public function indexSearch($query = '', $options = null);

    /**
     * Индексный гибридный поиск в еластике
     * т.е. поиск на основе индекса fos_elastica.index.%s.%s
     *
     * @throws ElasticsearchException
     */
    public function indexSearchHybrid($query = '', $options = null);

    /**
     *
     */
    public function transformResult();

    /**
     *
     */
    public function transformHybridResult();
}