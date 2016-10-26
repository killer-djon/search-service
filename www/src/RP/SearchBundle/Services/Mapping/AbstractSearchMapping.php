<?php
/**
 * Базовый абстрактный класс мапперов поиска
 */

namespace RP\SearchBundle\Services\Mapping;

abstract class AbstractSearchMapping
{
    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    abstract public static function getMultiMatchQuerySearchFields();

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * Query - c условиями запроса и фильтрами
     *
     * @return array
     */
    abstract public static function getMultiQuerySearchFields();
}