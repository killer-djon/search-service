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
     * @var SearchableInterface
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
     * Returns the Elastica ResultSet. Will return null if getSlice has not yet been
     * called.
     *
     * @return \Elastica\ResultSet|null
     */
    public function getResultSet()
    {
        $this->resultSet = $this->searchable->search($this->query);
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