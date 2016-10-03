<?php
/**
 * Интерфейс обеспечивающий правила создания \Elastica\Filter объекта
 */
namespace Common\Core\Facade\Search\QueryFilter;

use Elastica\Filter\AbstractFilter;

interface FilterFactoryInterface
{
    /**
     * Возвращает фильтр по условию GeoHash
     *
     * @https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geohash-cell-query.html
     * @abstract
     * @param string $fieldName
     * @param array $point
     * @param int $precision Meters
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoHashFilter($fieldName, array $point, $precision);

    /**
     * Возвращает фильтр по условию больше
     *
     * @abstract
     * @param string $fieldName
     * @param string $value
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGtFilter($fieldName, $value);

    /**
     * Возвращает фильтр c условием по term
     * как условие строгое равно
     *
     * @abstract
     * @param array $terms Условие точного совпадения
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getTermFilter(array $terms);

    /**
     * Возвращает фильтр c условием по term
     * как условие содержащее ( IN ... )
     *
     * @abstract
     * @param string $fieldName Имя поля
     * @param array $terms Условие точного совпадения
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getTermsFilter($fieldName, array $terms);

    /**
     * Возвращает фильтр со скриптом поиска
     *
     * @abstract
     * @param array|string|\Elastica\Script $script OPTIONAL Script
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getScriptFilter($script);

    /**
     * Возвращает фильтр по условию регулярного выражения
     *
     * @abstract
     * @param string $fieldName Field name
     * @param string $regexp Regular expression
     * @param array $options Regular expression options
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getRegexpFilter($fieldName, $regexp, array $options = []);

    /**
     * Возвращает фильтр по удаленности от геоточки
     *
     * @abstract
     * @param string $fieldName Поле фильтра
     * @param string $distance Радиус фильтра в километрах
     * @param array $point Точка местоположения
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoDistanceFilter($fieldName, array $point, $distance);

    /**
     * Возвращает фильтр по промежутку расстояний (например: от 200км до 400км)
     *
     * @abstract
     * @param string $fieldName Поле фильтра
     * @param array $point Точка местоположения
     * @param array $ranges Промежуток расстояния
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoDistanceRangeFilter($fieldName, array $point, array $ranges);

    /**
     * Возвращает фильтр выборки по ИД
     *
     * @abstract
     * @param array $idList
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getIdsFilter(array $idList);

    /**
     * Возвращает фильтр выборки по диапазону
     *
     * @param string $fieldName
     * @param string $from
     * @param string $to
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getRangeFilter($fieldName, $from, $to);

    /**
     * Возвращает фильтр выборки по площади
     *
     * @param string $fieldName
     * @param array[] $points
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoPolygonFilter($fieldName, array $points);

    /**
     * Возвращает фильтр исключения из поиска
     *
     * @abstract
     * @param \Elastica\Filter\AbstractFilter $filterCondition
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getNotFilter(AbstractFilter $filterCondition);

    /**
     * Возвращает фильтр уловия или
     *
     * @param array \Elastica\Filter\AbstractFilter[] $filterConditions
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getBoolOrFilter(array $filterConditions);

    /**
     * Возвращает фильтр уловия и
     *
     * @param array \Elastica\Filter\AbstractFilter[] $filterConditions
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getBoolAndFilter(array $filterConditions);

    /**
     * Возвращает фильтр существования поля
     *
     * @param string $fieldName
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getExistsFilter($fieldName);

    /**
     * Основной объект фильтра для формированиия запроса
     * Filtered Query
     *
     * @param array $must Массив обязательных для исполнения условий фильтра
     * @param array $must Массив с желательными для исполнения условий фильтра
     * @param array $must Массив условий фильтра которые не должны исполнятся (т.е. отрицание)
     * @return \Elastica\Filter\BoolFilter
     */
    public function getBoolFilter(array $must, array $should, array $mustNot);
}