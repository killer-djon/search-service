<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 16.11.16
 * Time: 14:22
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class PlaceTypeSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'placetype';

    /** Поле ID типа места */
    const PLACE_TYPE_ID_FIELD = 'id';

    /** Поле ID родительского типа места */
    const PLACE_TYPE_PARENT_ID_FIELD = 'parentId';
}