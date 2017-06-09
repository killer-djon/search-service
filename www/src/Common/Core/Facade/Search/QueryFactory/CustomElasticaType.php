<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 09.06.17
 * Time: 13:30
 */

namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Type;

class CustomElasticaType extends Type
{
    /**
     * @inheritdoc
     */
    public function createSearch($query = '', $options = null)
    {
        $search = new CustomElasticSearch($this->getIndex()->getClient());
        $search->addIndex($this->getIndex());
        $search->addType($this);
        $search->setOptionsAndQuery($options, $query);

        return $search;
    }
}