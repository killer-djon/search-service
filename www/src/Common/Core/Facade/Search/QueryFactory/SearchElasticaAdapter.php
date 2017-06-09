<?php
/**
 * Адаптер постраничной навигации
 */

namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Query;
use Elastica\SearchableInterface;
use Pagerfanta\Adapter\AdapterInterface;

class SearchElasticaAdapter implements AdapterInterface
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var \Elastica\ResultSet
     */
    private $resultSet;

    /**
     * @var CustomSearchIndex|CustomElasticaType
     */
    private $searchable;


    public function __construct(SearchableInterface $searchable, Query $query, $options = null)
    {
        $this->searchable = $searchable;
        $this->query = $query;

    }

    /**
     * Returns the number of results.
     *
     * @return integer The number of results.
     */
    public function getNbResults()
    {
        if (!$this->resultSet) {
            return $this->searchable->search($this->query)->getTotalHits();
        }

        return $this->resultSet->getTotalHits();
    }

    /**
     * Указанный индекс для поиска
     *
     * @var string|\Elastica\Index
     */
    private $_index = null;

    /**
     * Несколько индексов поиска
     *
     * @var string[]
     */
    private $_indices = [];

    public function setIndex($indexName = null)
    {
        if (!is_null($indexName)) {
            $this->_indices[] = $indexName;
        }
    }

    /**
     * Получаем значение индекса в котором еще надоискать
     * многоиндексовый поиск
     *
     * @depricated
     * @return string[]
     */
    public function getIndex()
    {
        return $this->_indices;
    }

    public function getIndices()
    {
        return $this->_indices;
    }

    /**
     * Множественное добавление индексов
     * т.е. поиск в нескольких индексах
     *
     * @param array $indices
     */
    public function addIndices($indices = [])
    {
        $this->_indices = $indices;
    }


    /**
     * Returns the Elastica ResultSet. Will return null if getSlice has not yet been
     * called.
     *
     * @return \Elastica\ResultSet|null
     */
    public function getResultSet()
    {
        $ElasticaQuery = $this->searchable->createSearch($this->query);
        $ElasticaQuery->clearIndices();

        $ElasticaQuery->addIndices($this->_indices);

        $this->resultSet = $ElasticaQuery->search();
        unset($this->query);

        return $this->resultSet;
    }

    /**
     * Returns an slice of the results.
     *
     * @param integer $offset The offset.
     * @param integer $length The length.
     * @return array|\Traversable The slice.
     */
    public function getSlice($offset, $length)
    {
        return $this->resultSet = $this->searchable->search($this->query, [
            'from' => $offset,
            'size' => $length,
        ]);
    }
}