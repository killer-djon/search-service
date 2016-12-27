<?php
/**
 * класс представляющий аггрегирование по сетки на карте
 * создаем сетку карту как ячейки таблицы
 */
namespace Common\Core\Facade\Search\QueryAggregation;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Param;

class GeoBounds extends AbstractAggregation
{
    /**
     * @param string $name the name if this aggregation
     * @param string $field the field on which to perform this aggregation
     */
    public function __construct($field)
    {
        parent::__construct('viewport');
        $this->setField($field);
    }

    /**
     * Set the field for this aggregation.
     *
     * @param string $field the name of the document field on which to perform this aggregation
     * @return Param
     */
    public function setField($field)
    {
        return $this->setParam('field', $field);
    }

    /**
     * is an optional parameter which specifies whether the bounding box should be allowed to overlap the international date line
     *
     * @param bool $setWrap
     * @return Param
     */
    public function setWrapLongitude($setWrap = true)
    {
        return $this->setParam('wrap_longitude', $setWrap);
    }
}