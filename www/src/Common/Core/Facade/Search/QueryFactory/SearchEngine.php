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
use Psr\Log\LoggerInterface;
use RP\SearchBundle\Services\Mapping\DiscountsSearchMapping;
use RP\SearchBundle\Services\Mapping\EventsSearchMapping;
use RP\SearchBundle\Services\Mapping\HelpOffersSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\RusPlaceSearchMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface;
use Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface;

class SearchEngine implements SearchEngineInterface
{
    /**
     * Кол-во записей по умолчанию
     *
     * @const int DEFAULT_SIZE_QUERY
     */
    const DEFAULT_SIZE_QUERY = 10000;

    /**
     * Пропуск записей по умолчанию при многотипмном поиске
     *
     * @const int DEFAULT_SKIP_QUERY
     */
    const DEFAULT_SKIP_QUERY = 0;

    /**
     * Доступные для поиска типы фильтров
     *
     * @var array $filterTypes
     */
    protected $filterTypes = [
        PeopleSearchMapping::CONTEXT     => PeopleSearchMapping::class,
        PlaceSearchMapping::CONTEXT      => PlaceSearchMapping::class,
        HelpOffersSearchMapping::CONTEXT => HelpOffersSearchMapping::class,
        DiscountsSearchMapping::CONTEXT  => DiscountsSearchMapping::class,
        RusPlaceSearchMapping::CONTEXT   => RusPlaceSearchMapping::class,
        EventsSearchMapping::CONTEXT     => EventsSearchMapping::class,
    ];

    /**
     * Алиасы типов поиска
     * т.е. по ключу мы можем искать в другой коллекции
     * но при этом использовать всего-лишь фильтр
     */
    protected $searchTypes = [
        'people'     => 'people',
        'places'     => 'places',
        'helpoffers' => 'people',
        'discounts'  => 'places',
        'rusplaces'  => 'places',
        'events'     => 'events',
    ];

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

    public function getFilterTypes()
    {
        return $this->filterTypes;
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
     * @param string|null $keyField Ключ в котором храним данные вывода (необходим при алиасах типов в поиске)
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchDocuments(\Elastica\Query $elasticQuery, $context = null, $setSource = true, $keyField = null)
    {
        try {
            /** устанавливаем все поля по умолчанию */
            $elasticQuery->setSource((bool)$setSource);
            $elasticType = $this->_getElasticType($context);
            $this->_paginator = new SearchElasticaAdapter($elasticType, $elasticQuery);

            return $this->transformResult($this->_paginator->getResultSet(), $keyField);

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
     * @param \Elastica\Query[] $elasticQueries An \Elastica\Query array
     * @param bool $setSource (default: true) Показать исходные данные объекта в ответе
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchMultiTypeDocuments(array $elasticQueries, $setSource = true)
    {
        try {
            $search = new \Elastica\Multi\Search($this->_elasticaIndex->getClient());

            // формируем объекты запроса
            array_walk($elasticQueries, function ($elasticQuery, $keyType) use ($search, $setSource) {
                $elasticQuery->setSource((bool)$setSource);
                $elasticQuery->setSize(self::DEFAULT_SIZE_QUERY);
                $elasticQuery->setFrom(self::DEFAULT_SKIP_QUERY);

                $elasticType = $this->_getElasticType($this->searchTypes[$keyType]);

                $searchItem = $elasticType->createSearch($elasticQuery);

                $search->addSearch($searchItem, $keyType);
            });


            return $this->multiTransformResult($search->search());
        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

    /**
     * Преобразование полученного результат из еластика
     * данное преобразование необходимо для многотипного поиска
     * т.е. поиска по нескольким типам
     *
     * @param \Elastica\Multi\ResultSet $resultSets
     * @return array Набор данных для вывода в результат
     */
    public function multiTransformResult(\Elastica\Multi\ResultSet $resultSets)
    {
        $resultIterator = $resultSets->getResultSets();


        if (!empty($resultIterator)) {
            $results = $info = $items = [];
            $aggs = [];
            $totalHits = 0;
            $totalTime = 0;

            foreach ($resultIterator as $key => $resultSet) {
                $dataItem[$key] = $this->setTotalResults($resultSet);
                if (!is_null($dataItem[$key]) && !empty($dataItem[$key])) {
                    $hits[$key] = $this->setTotalHits($resultSet);
                    $totalHits += $hits[$key]['totalHits'];
                    $totalTime += (float)$hits[$key]['totalTime'];

                    $searchingType[$key] = $hits[$key];
                    $info = [
                        'totalHits'  => $totalHits,
                        'totalTime'  => $totalTime . 'ms',
                        'searchType' => $searchingType,
                    ];
                    $items[$key] = $dataItem[$key][$this->searchTypes[$key]];
                    $aggs[$key] = $this->setAggregationsResult($resultSet);
                }
            }

            $results['cluster'] = $aggs;
            $results['info'] = $info;
            $results['items'] = $items;


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
     * @param string|null $keyField Ключ в котором храним данные вывода (необходим при алиасах типов в поиске)
     * @return array $data Набор данных для вывода в результат
     */
    public function transformResult(\Elastica\ResultSet $resultSets, $keyField = null)
    {
        $this->setTotalHits($resultSets);
        $this->setTotalResults($resultSets, $keyField);
        $this->setAggregationsResult($resultSets);

        return $this->getTotalResults();
    }

    /**
     * Устанавливаем результат аггрегации
     *
     * @param \Elastica\ResultSet $resultSets
     * @return void
     */
    private function setAggregationsResult(\Elastica\ResultSet $resultSets)
    {
        $this->_aggregationsResult = current($resultSets->getAggregations());
        return $this->_aggregationsResult['buckets'];
    }

    /**
     * Устанавливаем общие показатели запроса
     *
     * @param \Elastica\ResultSet $resultSets
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
     * @param string|null $keyField Ключ в котором храним данные вывода (необходим при алиасах типов в поиске)
     * @return void
     */
    private function setTotalResults(\Elastica\ResultSet $resultSets, $keyField = null)
    {
        $results = $resultSets->getResults();

        array_walk($results, function ($resultItem) use (&$items, $keyField) {
            $type = $keyField ?: $resultItem->getType();

            $record[$type] = $resultItem->getData();
            if ($resultItem->hasFields()) {
                foreach ($resultItem->getFields() as $fieldKey => $field) {
                    if (isset($record[$type][$fieldKey])) {
                        unset($record[$type][$fieldKey]);
                    }
                    $record[$type]['tagsMatch'][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                }
            }
            $record[$type]['hit'] = $resultItem->getHit();
            if (isset($record[$type]['hit']['_source'])) {
                unset($record[$type]['hit']['_source']);
            }

            if (isset($record[$type]['hit']['fields'])) {
                unset($record[$type]['hit']['fields']);
            }

            $items[$type][] = array_merge($record[$type], [
                'hit' => $record[$type]['hit'],
            ]);
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

}