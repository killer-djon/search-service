<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 10.11.16
 * Time: 10:50
 */

namespace Common\Core\Constants;

abstract class SortingOrder
{
    /**
     * Направление сортировки результатов
     * по направлению вниз (по убыванию)
     *
     * @const string SORTING_DESC
     */
    const SORTING_DESC = 'desc';

    /**
     * Направление сортировки результатов
     * по направлению вверх (по возрастанию)
     *
     * @const string SORTING_ASC
     */
    const SORTING_ASC = 'asc';
}