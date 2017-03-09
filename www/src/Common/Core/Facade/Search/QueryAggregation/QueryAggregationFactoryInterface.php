<?php
/**
 * Интерфейс задающий правила построения аггрегаций
 */
namespace Common\Core\Facade\Search\QueryAggregation;

use Elastica\Filter\AbstractFilter;

interface QueryAggregationFactoryInterface
{
    /**
     * Расстояние по умолчанию в определении дистанции
     * предназначено и для GeoHash аггрегации
     *
     * @const string DEFAULT_RADIUS_DISTANCE
     */
    const DEFAULT_RADIUS_DISTANCE = 3;


    public function getGeoCentroidAggregation($fieldName);

    /**
     * Применение фильтра для аггрегирования данных
     * как правило применимо для geohash
     *
     * @param string $name Название объекта аггрегирования
     * @param AbstractFilter $filter ОБъект фильтра
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getFilterAggregation($name, AbstractFilter $filter = null);

    /**
     * Формирование сетки для аггрегирования данных
     *
     * @param string $fieldName Названиние поля
     * @param bool $setWrapLon
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getGeoBoundBoxAggregation($fieldName, $setWrapLon = true);



    /**
     * Аггрегирование временных промежутков
     * например можно извлечь документы за день или замесяц
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#time-units Возможные значения в интервале
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html Форматы даты
     * @param $fieldName Название поля аггрегации
     * @param $interval Временной интервал
     *      (Available expressions for interval: year, quarter, month, week, day, hour, minute, second)
     * @param int $count Минимальный лимит документов в результате аггрегации
     * @param string $offset Временная метка от которой пропустить результаты
     * @param string $format Формат времени
     * @return \Elastica\Aggregation\AbstractSimpleAggregation
     */
    public function getDateHistogramAggregation($fieldName, $interval, $count = null, $timezone = null, $offset = null, $format = null);

    /**
     * Аггрегируем дистацию геопозиции
     *
     * @param string $fieldName Название поля
     * @param string|array $startPoint valid formats are array("lat" => 52.3760, "lon" => 4.894), "52.3760, 4.894", and array(4.894, 52.3760)
     * @param int $radius Целое число определяющее радиус
     * @param string $unit defaults to km
     * @param string $distanceType see DISTANCE_TYPE_* constants for options. Defaults to sloppy_arc.
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getGeoDistanceAggregation(
        $fieldName,
        $startPoint,
        $radius,
        $unit = 'km',
        $distanceType = \Elastica\Aggregation\GeoDistance::DISTANCE_TYPE_SLOPPY_ARC
    );

    /**
     * GeoHashGrid аггрегация, определение по ячейкам на карте
     *
     * @param string $fieldName Название поля
     * @param string|array $startPoint valid formats are array("lat" => 52.3760, "lon" => 4.894), "52.3760, 4.894", and array(4.894, 52.3760)
     * @param int $precision an integer between 1 and 12, inclusive. Defaults to 5.
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getGeoHashAggregation($fieldName, $startPoint, $precision = self::DEFAULT_RADIUS_DISTANCE);

    /**
     * Возвращаем единственный результат запроса
     * нужно когда например получаем данные по ID
     *
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getTopHitsAggregation();

    /**
     * Установка исходных данных в агррегированные данные
     *
     * @param string $fieldName Название поля
     * @param array $fields Набор полей которые нужно выводить
     * @param int|null $size Сколько объектов указывать
     * @return \Elastica\Aggregation\TopHits
     */
    public function setAggregationSource($fieldName, $fields = [], $size = null);

    /**
     * Группировка данных по названию поля
     * типа bucket по полю
     *
     * @param string $fieldName Название поля
     * @param string|\Elastica\Script $script Скрипт для аггрегирования
     * @param string $order "_count", "_term", or the name of a sub-aggregation or sub-aggregation response field
     * @param string $direction Направление сортировки
     * @param int|null $minDocCount Минимальное кол-во документов в букете
     * @return \Elastica\Aggregation\Terms
     */
    public function getTermsAggregation($fieldName, $script = null, $order = '_term', $direction = 'asc', $minDocCount = null);

    /**
     * Аггрегирование по max выражению
     *
     * @param string $fieldName Название поля
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\
     */
    public function getMaxAggregation($fieldName, $script = null);

    /**
     * Аггрегирование по min выражению
     *
     * @param string $fieldName Название поля
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\
     */
    public function getMinAggregation($fieldName, $script = null);

    /**
     * Получаем аггрегированный скрипт AVG
     *
     * @param string $fieldName Название поле аггрегации
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\AbstractSimpleAggregation
     */
    public function getAvgAggregation($fieldName, $script = null);
}