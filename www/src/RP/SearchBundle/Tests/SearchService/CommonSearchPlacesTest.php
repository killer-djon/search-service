<?php
namespace RP\SearchBundle\Tests\SearchService;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Client;
use Elastica\Document;
use Elastica\Filter\Term;
use Elastica\Query;
use Elastica\Type;
use PHPUnit\Framework\TestCase;
use RP\SearchBundle\Services\CommonSearchService;
use Elastica\Type as ElasticType;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;

/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 11.07.17
 * Time: 14:01
 */
class CommonSearchPlacesTest extends TestCase
{
    /**
     * Host поискового сервиса
     * по умолчанию
     *
     * @const string
     */
    const DEFAULT_HOST = '172.28.128.3';


    /**
     * Порт поискового сервиса
     * по умолчанию
     *
     * @const int
     */
    const DEFAULT_PORT = 9200;

    /**
     * Исходные объект данных
     * где содержиться русское и не русское место
     *
     * @var array
     */
    private $testPlacesDocuments = [
        [
            'id' => 'test0001',
            'name' => 'Place 0001',
            'isRussian' => true
        ],
        [
            'id' => 'test0002',
            'name' => 'Place 0002',
            'isRussian' => true
        ],
        [
            'id' => 'test0003',
            'name' => 'Place 0003',
            'isRussian' => false
        ]
    ];

    /**
     * @var Type
     */
    private $searchType;

    /**
     * Сервис глобального поиска данных
     * по сущностям
     *
     * @var CommonSearchService
     */
    private $commonSearchService;

    private function _getClient(array $params = array(), $callback = null)
    {
        $config = array(
            'host' => self::DEFAULT_HOST,
            'port' => self::DEFAULT_PORT,
        );

        $config = array_merge($config, $params);

        return new Client($config, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $client = $this->_getClient();
        
        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true);

        /** @var Type\ */
        $this->searchType = $index->getType('zeroType');

        foreach ($this->testPlacesDocuments as $docId => $placesDocument)
        {
            $this->searchType->addDocument(
                new Document($docId, $placesDocument)
            );
        }

        $index->refresh();
    }

    /**
     * Поиск только мест без русских мест
     */
    public function testSearchPlaces()
    {
        $filter = new \Elastica\Filter\BoolAnd([
            new Term([
                'isRussian' => false
            ])
        ]);
        $query = new \Elastica\Query\MatchAll();

        $filterQuery = new Query\Filtered($query, $filter);
        $searchQuery = Query::create($filterQuery);

        $resultSet = $this->searchType->search($searchQuery);

        $this->assertEquals(1, $resultSet->count());
        $record = $resultSet->current()->getData();

        $this->assertFalse($record['isRussian']);
        $this->assertEquals('test0003', $record['id']);

    }


    /**
     * Поиск только русских мест
     */
    public function testSearchRusPlaces()
    {
        $filter = new \Elastica\Filter\BoolAnd([
            new Term([
                'isRussian' => true
            ])
        ]);
        $query = new \Elastica\Query\MatchAll();

        $filterQuery = new Query\Filtered($query, $filter);
        $searchQuery = Query::create($filterQuery);

        $resultSet = $this->searchType->search($searchQuery);
        $this->assertEquals(2, $resultSet->count());

        $current = $resultSet->current()->getData();
        $next = $resultSet->next()->getData();

        $this->assertEquals('test0001', $current['id']);
        $this->assertEquals('test0002', $next['id']);
    }


    /**
     * Поиск всех мест и русских в том числе
     */
    public function testSearchAllPlaces()
    {
        $searchQuery = Query::create(new Query());

        $resultSet = $this->searchType->search($searchQuery);
        $this->assertEquals(count($this->testPlacesDocuments), $resultSet->count());

        $results = [];
        foreach ($resultSet->getResults() as $result){
            $results[] = $result->getSource();
        }

        $this->assertEquals(
            array_column($this->testPlacesDocuments, 'id'),
            array_column($results, 'id')
        );
    }

    public function tearDown()
    {
        $client = $this->_getClient();
        $client->getIndex('zero')->delete();
    }

}