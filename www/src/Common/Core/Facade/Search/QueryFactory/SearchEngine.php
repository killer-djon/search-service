<?php
/**
 * Поисковый движок для работы с еластиком
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface;
use Elastica\Exception\ElasticsearchException;
use FOS\ElasticaBundle\Elastica\Index;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\NullAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface;
use Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface;

class SearchEngine implements SearchEngineInterface
{
    /**
     * Адаптер постраничной  навигации
     *
     * @var \Pagerfanta\Adapter\ElasticaAdapter $_paginator
     */
    private $_paginator;

    /**
     * Общие показатели результата поиска
     *
     * @var array Время на запрос и общее кол-во в результате
     */
    private $_totalHits = [];

    /**
     * Общие результат поиска
     *
     * @var array
     */
    private $_totalResults = [];

    /**
     * Бакеты аггрегированных данных
     *
     * @var array
     */
    private $_aggregationsResult = [];

    /**
     * Контейнер ядра для получения сервисов
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface
     */
    protected $_queryFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface Объект формирования условий запроса
     */
    protected $_queryConditionFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface Оъект добавляющий фильтры к запросу
     */
    protected $_queryFilterFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface Оъект добавляющий аггрегироание к запросу
     */
    protected $_queryAggregationFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface
     */
    protected $_scriptFactory;
    /**
     * @var \Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface $_sortingFactory
     */
    protected $_sortingFactory;

    /**
     * @var \FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface $_transformer
     */
    protected $_transformer;

    /**
     * @var \FOS\ElasticaBundle\Elastica\Index $elasticaIndex
     */
    protected $_elasticaIndex;

    /**
     * Объект логгера
     *
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * При создании сервиса необходимо установить все сопутствующие объекты
     *
     * @param \FOS\ElasticaBundle\Elastica\Index $elasticaIndex
     * @param \Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface Класс формирующий объект запроса
     * @param \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface Объект формирования условий запроса
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface Оъект добавляющий фильтры к запросу
     * @param \Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface Оъект добавляющий аггрегироание к запросу
     * @param \Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface Объект скрипта для поля
     * @param \Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface Объект создания сортировки в запросе
     */
    public function __construct(
        Index $elasticaIndex,
        QueryFactoryInterface $queryFactory,
        ConditionFactoryInterface $queryCondition,
        FilterFactoryInterface $filterFactory,
        QueryAggregationFactoryInterface $aggregationFactory,
        QueryScriptFactoryInterface $scriptFactory,
        QuerySortFactoryInterface $querySorting
    ) {
        $this->_elasticaIndex = $elasticaIndex;
        $this->_queryConditionFactory = $queryCondition;
        $this->_queryFilterFactory = $filterFactory;
        $this->_queryFactory = $queryFactory;
        $this->_queryAggregationFactory = $aggregationFactory;
        $this->_scriptFactory = $scriptFactory;
        $this->_sortingFactory = $querySorting;
    }

    /**
     * Получаем логгер
     * при его помощи будет логировать инфу по запросам
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface
     */
    public function getQueryCondition()
    {
        return $this->_queryConditionFactory;
    }

    /**
     * @return \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface
     */
    public function getFilterCondition()
    {
        return $this->_queryFilterFactory;
    }

    /**
     * Устанавливаем в свойство класса объект контейнера
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
    public function searchDocuments(\Elastica\Query $elasticQuery, $context = null, $setSource = true)
    {
        try {
            /** устанавливаем все поля по умолчанию */
            $elasticQuery->setSource((bool)$setSource);

            $elasticType = $this->_getElasticType($context);
            $this->_paginator = new SearchElasticaAdapter($elasticType, $elasticQuery);

            return $this->transformResult($this->_paginator->getResultSet());

        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

    /**
     * Массовый поиск в базе еласьика
     * на основании полученных типов
     * работает по принципу накопления всех запросов в пулл запросов
     * и пакетная отправка в поиск _msearch
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-multi-search.html
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param array $types Типы по которым будем проводить поиск
     * @param bool $setSource (default: true) Показать исходные данные объекта в ответе
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchMultiTypeDocuments(\Elastica\Query $elasticQuery, array $types, $setSource = true)
    {
        try {
            /** устанавливаем все поля по умолчанию */
            $elasticQuery->setSource((bool)$setSource);

            $search = new \Elastica\Multi\Search($this->_elasticaIndex->getClient());
            foreach ($types as $type => $fields) {
                $elasticType = $this->_getElasticType($type);
                $elasticQuery->getQuery()->setFields($fields);
                $search->addSearch($elasticType->createSearch($elasticQuery));
            }

            return $this->multiTransformResult($search->search());
        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

    public function multiTransformResult(\Elastica\Multi\ResultSet $resultSets)
    {
        if ($resultSets->count() > 0) {
            $resultSets->rewind();

            $results = [];
            while ($resultSets->valid()) {
                $resultTypeValue = $resultSets->current();

                $dataItem = $this->setTotalResults($resultTypeValue);
                $dataItemKey = key($dataItem);

                $this->_paginator = new NullAdapter($resultTypeValue->getTotalHits());

                $results[] = [
                    'info'         => $this->setTotalHits($resultTypeValue),
                    'paginator' => $this->getPaginationAdapter(0, 10),
                    $dataItemKey => $dataItem[$dataItemKey],
                ];

                $resultSets->next();
            }

            return $results;
        }
    }

    /**
     * Поиск единственного документа по ID
     *
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param string|null $context Search type
     * @param bool $setSource (default: true) Показать исходные данные объекта в ответе
     * @throws ElasticsearchException
     * @return array|null results
     */
    public function searchSingleDocuments(\Elastica\Query $elasticQuery, $context = null, $setSource = true)
    {
        try {
            /** устанавливаем все поля по умолчанию */
            $elasticQuery->setSource((bool)$setSource);

            $elasticType = $this->_getElasticType($context);
            $elasticQuery->setSize(1);
            $elasticQuery->setFrom(0);

            $resultSet = $elasticType->search($elasticQuery);
            if ($resultSet->current() !== false) {
                return $resultSet->current()->getData();
            }

            return null;

        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e->getCode(), $e->getMessage());
        }

    }

    /**
     * Возвращаем постраничную навигацию
     *
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getPaginationAdapter($skip, $limit)
    {
        $totalCount = $this->_paginator->getNbResults();

        if ($totalCount != 0) {
            $count = ($limit >= $totalCount ? $totalCount : $limit);
            $pageCount = intval(($totalCount - 1) / $count) + 1;
            $page = intval($skip / $count) + 1;

            return [
                'totalCount' => (int)$totalCount,
                'offset'     => (int)$skip,
                'limit'      => (int)$count,
                'countPage'  => (int)$pageCount,
                'page'       => (int)$page,
            ];
        }
    }

    /**
     * Преобразование полученного результат из еластика
     * получаем набор данных \Elastica\Result
     * который тупо переводим в массив для вывода результата
     *
     * @param \Elastica\ResultSet $resultSets
     * @return array $data Набор данных для вывода в результат
     */
    public function transformResult(\Elastica\ResultSet $resultSets)
    {
        $this->setTotalHits($resultSets);
        $this->setTotalResults($resultSets);
        $this->setAggregationsResult($resultSets);

        return $this->getTotalResults();
    }

    /**
     * Устанавливаем результат аггрегации
     *
     * @param \Elastica\Result $resultSets
     * @return void
     */
    private function setAggregationsResult(\Elastica\ResultSet $resultSets)
    {
        $this->_aggregationsResult = $resultSets->getAggregations();

        return $this->_aggregationsResult;
    }

    /**
     * Устанавливаем общие показатели запроса
     *
     * @param \Elastica\Result $resultSets
     * @return void
     */
    private function setTotalHits(\Elastica\ResultSet $resultSets)
    {
        $elipsedTime = $resultSets->getTotalTime() / 1000;
        $this->_totalHits = [
            'totalHits' => $resultSets->getTotalHits(),
            'totalTime' => $elipsedTime . 'ms',
        ];

        return $this->_totalHits;
    }

    /**
     * Устанавливаем общие данные запроса
     *
     * @param \Elastica\Result $resultSets
     * @return void
     */
    private function setTotalResults(\Elastica\ResultSet $resultSets)
    {
        $results = $resultSets->getResults();
        array_walk($results, function ($resultItem) use (&$items) {
            $record[$resultItem->getType()]['item'] = $resultItem->getData();
            if ($resultItem->hasFields()) {
                foreach ($resultItem->getFields() as $fieldKey => $field) {
                    if (isset($record[$resultItem->getType()]['item'][$fieldKey])) {
                        unset($record[$resultItem->getType()]['item'][$fieldKey]);
                    }
                    $record[$resultItem->getType()]['item']['tagsMatch'][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                }
            }
            $record[$resultItem->getType()]['hit'] = $resultItem->getHit();
            if (isset($record[$resultItem->getType()]['hit']['_source'])) {
                unset($record[$resultItem->getType()]['hit']['_source']);
            }

            if (isset($record[$resultItem->getType()]['hit']['fields'])) {
                unset($record[$resultItem->getType()]['hit']['fields']);
            }

            $items[$resultItem->getType()][] = [
                'item' => $record[$resultItem->getType()]['item'],
                'hit'  => $record[$resultItem->getType()]['hit'],
            ];
        });

        $this->_totalResults = $items;

        return $this->_totalResults;
    }

    /**
     * Получить результат аггрегированных данных
     *
     * @return array
     */
    public function getAggregations()
    {
        return $this->_aggregationsResult;
    }

    /**
     * Получить общий результат запроса
     *
     * @return array
     */
    public function getTotalResults()
    {
        return $this->_totalResults;
    }

    /**
     * Получить общие показатели запроса
     *
     * @return array
     */
    public function getTotalHits()
    {
        return $this->_totalHits;
    }

    /**
     * Возвращает тип Elastic для заданного контекста
     *
     * @param string|null $context
     * @return \Elastica\Type|\Elastica\Index
     * @throws \InvalidArgumentException
     */
    protected function _getElasticType($context = null)
    {
        if (is_null($context)) {
            return $this->_elasticaIndex;
        }

        return $this->_elasticaIndex->getType($context);
    }

    public function multiTypeSearch($query)
    {
        return $this->_elasticaIndex->search($query);
    }

}