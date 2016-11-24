<?php
/**
 *
 */

namespace Common\Core\Facade\Search\QueryAggregation;

use Elastica\Aggregation\AbstractAggregation;

class GeoCentroid extends AbstractAggregation
{
    /**
     * @param string       $name   the name if this aggregation
     * @param string       $field  the field on which to perform this aggregation
     * @param string|array $origin the point from which distances will be calculated
     */
    public function __construct($name, $field)
    {
        parent::__construct($name);
        $this->setField($field);
    }

    /**
     * Set the field for this aggregation.
     *
     * @param string $field the name of the document field on which to perform this aggregation
     *
     * @return $this
     */
    public function setField($field)
    {
        return $this->setParam('field', $field);
    }
}