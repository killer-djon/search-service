<?php
/**
 * Поисковый движок для работы с еластиком
 */
namespace Common\Core\Facade\Search\QueryFactory;

use Common\Core\Constants\RequestConstant;
use Elastica\Query;
use Elastica\Exception\ElasticsearchException;
use FOS\ElasticaBundle\Elastica\Index;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchEngine implements SearchEngineInterface
{
    /**
     * Контейнер ядра для получения сервисов
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface Класс формирующий объект запроса
     */
    protected $_queryFactory;

    /**
     * @var \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface Объект формирования условий запроса
     */
    protected $_queryCondition;

    /**
     * @var \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface Оъект добавляющий фильтры к запросу
     */
    protected $_filterFactory;

    /**
     * @var \FOS\ElasticaBundle\Elastica\Index $elasticaIndex
     */
    protected $_elasticaIndex;

    /**
     * @var \FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface $_transformer
     */
    protected $_transformer;

    /**
     * При создании сервиса необходимо установить все сопутствующие объекты
     *
     * @param \FOS\ElasticaBundle\Elastica\Index $elasticaIndex
     * @param \Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface Класс формирующий объект запроса
     * @param \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface Объект формирования условий запроса
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface Оъект добавляющий фильтры к запросу
     */
    public function __construct(
        Index $elasticaIndex,
        QueryFactoryInterface $queryFactory,
        ConditionFactoryInterface $queryCondition,
        FilterFactoryInterface $filterFactory
    ) {
        $this->_elasticaIndex = $elasticaIndex;
        $this->_queryFactory = $queryFactory;
        $this->_queryCondition = $queryCondition;
        $this->_filterFactory = $filterFactory;

    }

    /**
     * @return \Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface
     */
    public function getQueryCondition()
    {
        return $this->_queryCondition;
    }

    /**
     * @return \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface
     */
    public function getFilterCondition()
    {
        return $this->_filterFactory;
    }

    /**
     * @return \Common\Core\Facade\Search\QueryFactory\QueryFactoryInterface
     */
    public function getQueryFactory()
    {
        return $this->_queryFactory;
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
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function findDocuments($context, $query, $options = [])
    {

    }

    /**
     * Индексный поиск в еластике
     * т.е. поиск на основе индекса fos_elastica.index.%s.%s
     *
     * @param string Search type
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param array $options
     * @throws ElasticsearchException
     * @return array results
     */
    public function searchDocuments($context, $query, $options = [])
    {
        $elasticType = $this->_getElasticType($context);

        if (!empty($options)) {
            extract($options);
        }

        // Применить набор фильтров
        if (isset($filters) && sizeof($filters) > 0) {
            //$queryFilter = new \Elastica\Filter\BoolAnd();
            $queryFilter = $this->_filterFactory->getBoolAndFilter($filters);
            // Применить к запросу набор фильтров
            $query = new \Elastica\Query\Filtered($query, $queryFilter);
        }

        // Сформировать объект запроса
        $elasticQuery = new \Elastica\Query($query);

        if (isset($sortings) && sizeof($sortings) > 0) {
            $elasticQuery->setSort($sortings);
        }

        // Установить ограничения на количество записей
        if (!isset($limit)) {
            $limit = RequestConstant::DEFAULT_SEARCH_LIMIT;
        }

        if (!isset($skip)) {
            $skip = RequestConstant::DEFAULT_SEARCH_SKIP;
        }

        if (!isset($minScore)) {
            $minScore = RequestConstant::DEFAULT_SEARCH_MIN_SCORE;

        }

        $elasticQuery->setFrom((int)$skip);
        $elasticQuery->setSize($limit);
        $elasticQuery->setMinScore($minScore);

        $searchResults = $elasticType->search($elasticQuery);

        return $this->transformResult($searchResults->getResults());
    }

    /**
     * Преобразование полученного результат из еластика
     * получаем набор данных \Elastica\Result
     * который тупо переводим в массив для вывода результата
     *
     * @param \Elastica\Result[] $resultSets
     * @return array $data Набор данных для вывода в результат
     */
    public function transformResult(array $resultSets)
    {
        return array_map(function ($item) {
            return $item->getData();
        }, $resultSets);
    }

    /**
     * Возвращает тип Elastic для заданного контекста
     *
     * @param string $context
     * @return \Elastica\Type
     * @throws \InvalidArgumentException
     */
    private function _getElasticType($context)
    {
        if (!is_string($context) || empty($context)) {
            throw new \InvalidArgumentException('_getElasticType: Invalid argument'); // TODO: Сообщение
        }

        return $this->_elasticaIndex->getType($context);
    }
}