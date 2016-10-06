<?php
/**
 * Поисковый движок для работы с еластиком
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Common\Core\Constants\RequestConstant;
use Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface;
use Elastica\Query;
use Elastica\Exception\ElasticsearchException;
use FOS\ElasticaBundle\Elastica\Index;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Common\Core\Facade\Search\QueryAggregation\QueryAggregationFactoryInterface;

class SearchEngine implements SearchEngineInterface
{
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
    public $_queryFactory;

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
     * @var \FOS\ElasticaBundle\Elastica\Index $elasticaIndex
     */
    protected $_elasticaIndex;

    /**
     * @var \Common\Core\Facade\Search\QueryScripting\QueryScriptFactoryInterface
     */
    protected $_scriptFactory;

    /**
     * @var \FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface $_transformer
     */
    protected $_transformer;

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
     */
    public function __construct(
        Index $elasticaIndex,
        QueryFactoryInterface $queryFactory,
        ConditionFactoryInterface $queryCondition,
        FilterFactoryInterface $filterFactory,
        QueryAggregationFactoryInterface $aggregationFactory,
        QueryScriptFactoryInterface $scriptFactory
    ) {
        $this->_elasticaIndex = $elasticaIndex;
        $this->_queryConditionFactory = $queryCondition;
        $this->_queryFilterFactory = $filterFactory;
        $this->_queryFactory = $queryFactory;
        $this->_queryAggregationFactory = $aggregationFactory;
        $this->_scriptFactory = $scriptFactory;
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
     * Метод осуществляет поиск в еластике
     * при помощи сервиса fos_elastica.finder.%s.%s
     *
     * @param string Search type
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function findDocuments($context, \Elastica\Query $elasticQuery, $options = [])
    {
        // @todo В будущем надо будет применить для пагинации
    }

    /**
     * Индексный поиск в еластике
     * т.е. поиск на основе индекса fos_elastica.index.%s.%s
     *
     * @param string Search type
     * @param \Elastica\Query $elasticQuery An \Elastica\Query object
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchDocuments($context, \Elastica\Query $elasticQuery)
    {
        try {
            $elasticType = $this->_getElasticType($context);
            $searchResults = $elasticType->search($elasticQuery);

            $this->logger->info(json_encode([
                'query_string' => $elasticQuery,
                'time'         => date('d.m.Y H:i'),
            ]));

            return $this->transformResult($searchResults);
        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

    /**
     * Преобразование полученного результат из еластика
     * получаем набор данных \Elastica\Result
     * который тупо переводим в массив для вывода результата
     *
     * @param \Elastica\Result $resultSets
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
    }

    /**
     * Устанавливаем общие данные запроса
     *
     * @param \Elastica\Result $resultSets
     * @return void
     */
    private function setTotalResults(\Elastica\ResultSet $resultSets)
    {
        $this->_totalResults = array_map(function ($item) {
            $_hit = $item->getHit();
            unset($_hit['_source']);

            return [
                'item' => $item->getData(),
                'hit'  => $_hit,
            ];
        }, $resultSets->getResults());
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
     * @param string $context
     * @return \Elastica\Type
     * @throws \InvalidArgumentException
     */
    protected function _getElasticType($context)
    {
        if (!is_string($context) || empty($context)) {
            throw new \InvalidArgumentException('_getElasticType: Invalid argument'); // TODO: Сообщение
        }

        return $this->_elasticaIndex->getType($context);
    }
}