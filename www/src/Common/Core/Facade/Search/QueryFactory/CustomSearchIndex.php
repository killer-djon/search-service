<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 09.06.17
 * Time: 13:26
 */

namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Client;
use Elastica\Index;

class CustomSearchIndex extends Index
{
    /**
     * Индекс по умолчанию для клиента еластики
     * @const string
     */
    const DEFAULT_INDEX = 'russianplace';

    public function __construct(Client $client)
    {
        parent::__construct($client, self::DEFAULT_INDEX);
    }

    /**
     * @inheritdoc
     */
    public function createSearch($query = '', $options = null)
    {
        $search = new CustomElasticSearch($this->getClient());
        $search->addIndex($this);
        $search->setOptionsAndQuery($options, $query);

        return $search;
    }

    /**
     * Returns a type object for the current index with the given name.
     *
     * @param string type name
     * @return CustomElasticaType
     */
    public function getType($type)
    {
        return new CustomElasticaType($this, $type);
    }
}