<?php
/**
 * Поисковый движок для работы с еластиком
 */

namespace Common\Core\Facade\Search\QueryFactory;

use Common\Core\Constants\Location;
use Common\Core\Controller\ControllerTrait;
use Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface;
use Common\Core\Facade\Service\Geo\GeoPointService;
use Elastica\Exception\ElasticsearchException;
use FOS\ElasticaBundle\Elastica\Index;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Psr\Log\LoggerInterface;
use RP\SearchBundle\Services\Mapping\ChatMessageMapping;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;
use RP\SearchBundle\Services\Mapping\DiscountsSearchMapping;
use RP\SearchBundle\Services\Mapping\EventsSearchMapping;
use RP\SearchBundle\Services\Mapping\FriendsSearchMapping;
use RP\SearchBundle\Services\Mapping\HelpOffersSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceTypeSearchMapping;
use RP\SearchBundle\Services\Mapping\RusPlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\TagNameSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface;
use Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface;

class SearchEngine implements SearchEngineInterface
{
    use ControllerTrait;
    /**
     * Устанавливаем флаг формата данных для старых версий
     *
     * @var boolean $oldFormatVersion
     */
    protected $oldFormatVersion = false;

    /**
     * Устанавливаем флаг формата данных для старых версий
     *
     * @param bool $flag
     * @return void
     */
    public function setOldFormat($flag = false)
    {
        $this->oldFormatVersion = $flag;
    }

    /**
     * Получаем флаг формата данных для старых версий
     *
     * @return bool
     */
    public function getOldFormat()
    {
        return $this->oldFormatVersion;
    }

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
     * Доступные для маркеров типы фильтров
     *
     * @var array $filterTypes
     */
    protected $filterTypes = [
        PeopleSearchMapping::CONTEXT    => PeopleSearchMapping::class,
        FriendsSearchMapping::CONTEXT   => FriendsSearchMapping::class,
        PlaceSearchMapping::CONTEXT     => PlaceSearchMapping::class,
        RusPlaceSearchMapping::CONTEXT  => RusPlaceSearchMapping::class,
        EventsSearchMapping::CONTEXT    => EventsSearchMapping::class,
        DiscountsSearchMapping::CONTEXT => DiscountsSearchMapping::class,
    ];
    //[@"people",@"friends",@"places",@"rusPlaces",@"events",@"discounts"];

    /**
     * Доступные для поиска типы фильтров
     *
     * @var array $filterSearchTypes
     */
    protected $filterSearchTypes = [
        PeopleSearchMapping::CONTEXT            => PeopleSearchMapping::class,
        PlaceSearchMapping::CONTEXT             => PlaceSearchMapping::class,
        HelpOffersSearchMapping::CONTEXT_MARKER => HelpOffersSearchMapping::class,
        DiscountsSearchMapping::CONTEXT         => DiscountsSearchMapping::class,
        EventsSearchMapping::CONTEXT            => EventsSearchMapping::class,
        RusPlaceSearchMapping::CONTEXT          => RusPlaceSearchMapping::class,
        FriendsSearchMapping::CONTEXT           => FriendsSearchMapping::class,
    ];

    protected $availableTypesSearch = [
        CitySearchMapping::CONTEXT      => CitySearchMapping::class,
        ChatMessageMapping::CONTEXT     => ChatMessageMapping::class,
        TagNameSearchMapping::CONTEXT   => TagNameSearchMapping::class,
        PlaceTypeSearchMapping::CONTEXT => PlaceTypeSearchMapping::class,
        PeopleSearchMapping::CONTEXT    => PeopleSearchMapping::class,
    ];

    /**
     * Алиасы типов поиска
     * т.е. по ключу мы можем искать в другой коллекции
     * но при этом использовать всего-лишь фильтр
     */
    protected $searchTypes = [
        'people'        => 'people',
        'places'        => 'places',
        'helpOffers'    => 'people',
        'help'          => 'people',
        'discounts'     => 'places',
        'rusPlaces'     => 'places',
        'events'        => 'events',
        'friends'       => 'people',
        'commonFriends' => 'people',
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
    public $_queryConditionFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface Оъект добавляющий фильтры к запросу
     */
    public $_queryFilterFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface Оъект добавляющий аггрегироание к запросу
     */
    public $_queryAggregationFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface
     */
    public $_scriptFactory;
    /**
     * @var \Common\Core\Facade\Search\QuerySorting\QuerySortFactoryInterface $_sortingFactory
     */
    public $_sortingFactory;

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

        $this->availableTypesSearch += array_merge($this->filterSearchTypes, $this->filterTypes);

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

            if (!is_null($context) && !empty($context)) {
                $this->_paginator->setIndex($this->availableTypesSearch[$context]::DEFAULT_INDEX);
            }

            return $this->transformResult($this->_paginator->getResultSet(), $keyField);

        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

    /**
     * Вывод кол-ва позиций по запросу
     *
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param string $context Search type
     * @return int
     */
    public function getCountDocuments(\Elastica\Query $elasticQuery, $context = null)
    {
        $elasticType = $this->_getElasticType($context, $context);

        return $elasticType->count($elasticQuery);
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

            foreach ($elasticQueries as $keyType => $elasticQuery) {
                $elasticQuery->setSource((bool)$setSource);

                $elasticType = $this->_getElasticType($this->searchTypes[$keyType]);
                $searchItem = $elasticType->createSearch($elasticQuery);

                if (!$searchItem->hasIndex($this->availableTypesSearch[$this->searchTypes[$keyType]]::DEFAULT_INDEX)) {
                    $searchItem->addIndex($this->availableTypesSearch[$this->searchTypes[$keyType]]::DEFAULT_INDEX);
                }
                $search->addSearch($searchItem, $keyType);
            }

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
            $searchingType = [];
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

            $this->_totalResults = $items;
            $this->_totalHits = $info;

            return $results;
        }
    }

    /**
     * Флаг группировки класстера
     *
     * @var bool $clusterGrouped
     */
    private $clusterGrouped = false;

    /**
     * Устанавливаем флак необходимости группировки объектов класстера
     *
     * @param bool $grouped Флаг установки
     * @return void
     */
    public function setClusterGrouped($grouped = true)
    {
        $this->clusterGrouped = $grouped;
    }

    /**
     * Получаем значение флага группировки класстера
     *
     * @return bool
     */
    public function getClusterGrouped()
    {
        return $this->clusterGrouped;
    }

    /**
     * Трансформация типов кластера в один класстер объектов
     * которые будет содержать все группы распределенные по типу
     * Преобразование (группировка) проходит в 3 этапа
     *  1. Вытаскиваем из набора основные данные (и складываем в массив по ключам)
     *  2. Затем группируем все по ключам класстера
     *  3. Приводим к виду класстерных групп
     *
     * @param array $initBuckets Набор групп класстера
     * @param string|null $keyField Название поля в класстере
     * @return array Сгруппированный класстер данных
     */
    public function groupClasterLocationBuckets(array $initBuckets = null, $keyField = null)
    {
        if (is_null($keyField)) {
            return $initBuckets;
        }

        // 1. Вытаскиваем из набора основные данные (и складываем в массив по ключам)
        $buckets = [];
        foreach ($initBuckets as $typeKey => $bucketItem) {
            $bucketKeys = $this->array_combine_(array_column($bucketItem, 'key'), $bucketItem);
            foreach ($bucketKeys as $key => & $item) {

                $currentItem = current($item);
                //$docCount = $currentItem['doc_count'];
                $docCount = $currentItem[$keyField]['hits']['total'];

                if ((int)$docCount > 0) {

                    $item = [
                        'doc_count' => $docCount,
                        'type'      => $typeKey,
                        $keyField   => [
                            Location::LONG_LATITUDE  => $currentItem['centroid'][$keyField][Location::LATITUDE],
                            Location::LONG_LONGITUDE => $currentItem['centroid'][$keyField][Location::LONGITUDE],
                        ],
                    ];

                    if ($docCount == 1) {
                        $docs = array_combine(
                            array_column($currentItem[$keyField]['hits']['hits'], '_id'),
                            $currentItem[$keyField]['hits']['hits']
                        );

                        $item['items'] = $docs;
                    }
                }

                $buckets[$typeKey] = $bucketKeys;
            }
        }

        // 2. Затем группируем все по ключам класстера
        $bucketItems = [];
        // для начала сводим это в единный массив разных типов
        foreach ($buckets as $typeKey => $bucketItem) {
            foreach ($bucketItem as $key => $item) {
                $bucketItems[$key][] = $item;
            }
        }

        // 3. Приводим к виду класстерных групп
        $results = [];
        foreach ($bucketItems as $keyHash => $bucketItem) {
            $sumDocCount = array_sum(array_column($bucketItem, 'doc_count'));
            $docTypes = array_unique(array_column($bucketItem, 'type'));
            $location = array_column($bucketItem, 'location');

            if ($sumDocCount > 0) {
                $resultItem = [
                    'key'       => $keyHash,
                    'doc_count' => $sumDocCount,
                    'types'     => implode(',', $docTypes),
                    //'location' => $location
                    'location'  => GeoPointService::GetCenterFromDegrees($location),
                ];

                if ($sumDocCount == 1) {

                    $docItems = array_column($bucketItem, 'items');

                    $docItemValues = array_map(function ($item) {
                        return [
                            'source' => $item['_source'],
                            'fields' => $item['fields'],
                        ];
                    }, array_values(current($docItems)));

                    $docItemSource = AbstractTransformer::path(current($docItemValues), 'source');
                    $docItemFields = AbstractTransformer::path(current($docItemValues), 'fields');
                    if (!is_null($docItemFields) && !empty($docItemFields)) {
                        foreach ($docItemFields as $fieldKey => $field) {
                            $docItemSource['tagsMatch'][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                            unset($docItemSource[$fieldKey]);
                            // для совместимости со старыми прилоежнмия
                            $docItemSource[$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                        }
                    }
                    $this->restructLocationField($docItemSource);
                    $docItemSource = $this->revertToScalarTagsMatchFields($docItemSource);

                    $resultItem['items'][] = (!is_array($docItemSource) ? [$docItemSource] : $docItemSource);
                }

                $results[] = $resultItem;
            }
        }

        return $results;
    }

    public function array_combine_($keys, $values)
    {
        $result = [];
        foreach ($keys as $i => $k) {
            $result[$k][] = $values[$i];
        }

        return $result;
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

            //$resultSet = $elasticType->search($elasticQuery);
            $searchQuery = $elasticType->createSearch($elasticQuery);
            if (!$searchQuery->hasIndex($this->availableTypesSearch[$context]::DEFAULT_INDEX)) {
                $searchQuery->addIndex($this->availableTypesSearch[$context]::DEFAULT_INDEX);
            }

            $resultSet = $searchQuery->search();

            if ($resultSet->current() !== false) {
                $items = $resultSet->current()->getData();
                $fields = $resultSet->current()->getFields();

                if (!empty($fields)) {
                    foreach ($fields as $fieldKey => $field) {
                        $items['tagsMatch'][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                        unset($items[$fieldKey]);
                        // для совместимости со старыми прилоежнмия
                        $items[$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                    }
                }

                return $items;
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
     * @param int|null $totalHits Общее число записей
     * @return array
     */
    public function getPaginationAdapter($skip, $limit, $totalHits = null)
    {

        if (is_null($this->_paginator) && is_null($totalHits)) {
            return [];
        }

        $totalCount = 0;
        if (!is_null($this->_paginator)) {
            $totalCount = $this->_paginator->getNbResults();
        } else {
            if (!is_null($totalHits) && is_int($totalHits) && $totalHits > 0) {
                $totalCount = (int)$totalHits;
            }
        }

        if ($totalCount != 0) {
            $count = ($limit >= $totalCount ? $totalCount : $limit);
            $pageCount = intval(($totalCount - 1) / $count) + 1;
            $page = intval($skip / $count) + 1;

            return [
                'count'      => (int)$totalCount,
                'offset'     => (int)$skip,
                'limit'      => (int)$count,
                'page_count' => (int)$pageCount,
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

        if ($resultSets->count() > 0) {
            $this->setTotalHits($resultSets, $keyField);
            $this->setTotalResults($resultSets, $keyField);

            $this->setAggregationsResult($resultSets);

            return $this->getTotalResults();
        }

        return [];
    }

    /**
     * Устанавливаем результат аггрегации
     *
     * @param \Elastica\ResultSet $resultSets
     * @return array
     */
    private function setAggregationsResult(\Elastica\ResultSet $resultSets)
    {
        $aggs = current($resultSets->getAggregations());
        $buckets = (isset($aggs['buckets']) && !empty($aggs['buckets']) ? $aggs['buckets'] : null);

        if (!is_null($buckets)) {
            $this->_aggregationsResult = $buckets;
        }

        return $this->_aggregationsResult;
    }

    /**
     * Устанавливаем общие показатели запроса
     *
     * @param \Elastica\ResultSet $resultSets
     * @param string|null $keyField Ключ в котором храним данные вывода (необходим при алиасах типов в поиске)
     * @return array
     */
    private function setTotalHits(\Elastica\ResultSet $resultSets, $keyField = null)
    {
        //print_r($resultSets->getTotalHits()); die();
        $elipsedTime = $resultSets->getTotalTime() / 1000;
        $this->_totalHits = (!is_null($keyField) && !empty($keyField) ? [
            $keyField => [
                'totalHits' => $resultSets->getTotalHits(),
                'totalTime' => $elipsedTime . 'ms',
            ],
        ] : [
            'totalHits' => $resultSets->getTotalHits(),
            'totalTime' => $elipsedTime . 'ms',
        ]);

        return $this->_totalHits;
    }

    /**
     * флаг указывающий на необходимость одноуровнего формата данных
     *
     * @var bool $flatFormatData
     */
    private $flatFormatData = false;

    /**
     * Необходимо установить формат вывода данных
     * для вывода одноуровнего формата данных
     *
     * @param bool $flag
     * @return void
     */
    public function setFlatFormatResult($flag = false)
    {
        $this->flatFormatData = $flag;
    }

    /**
     * Устанавливаем общие данные запроса
     *
     * @param \Elastica\ResultSet $resultSets
     * @param string|null $keyField Ключ в котором храним данные вывода (необходим при алиасах типов в поиске)
     * @return array
     */
    private function setTotalResults(\Elastica\ResultSet $resultSets, $keyField = null)
    {
        $results = $resultSets->getResults();
        $items = [];
        foreach ($results as $indexKey => $resultItem) {
            $type = $keyField ?: $resultItem->getType();

            $record[$type] = $resultItem->getData();

            if ($resultItem->hasFields()) {
                foreach ($resultItem->getFields() as $fieldKey => $field) {
                    if (isset($record[$type][$fieldKey])) {
                        unset($record[$type][$fieldKey]);
                    }
                    $record[$type]['tagsMatch'][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                    // для совместимости со старыми прилоежнмия
                    $record[$type][$fieldKey] = (is_string($field) ? $field : (isset($field[0]) ? $field[0] : null));
                }
            }
            $record[$type]['hit'] = $resultItem->getHit();
            if (isset($record[$type]['hit']['_source'])) {
                unset($record[$type]['hit']['_source']);
            }

            if (isset($record[$type]['hit']['fields'])) {
                unset($record[$type]['hit']['fields']);
            }

            if (isset($record[$type]['hit']['sort'])) {
                unset($record[$type]['hit']['sort']);
            }

            if ($this->getOldFormat() === true) {
                $items[$type][] = [
                    'item' => $record[$type],
                    'hit'  => $record[$type]['hit'],
                ];

            } else {
                $items[$type][] = array_merge($record[$type], [
                    'hit' => $record[$type]['hit'],
                ]);
            }

        }

        $this->_totalResults = $items;

        if ($this->flatFormatData === true) {

            $this->_totalResults = current($items);
        }

        return $this->getTotalResults();
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