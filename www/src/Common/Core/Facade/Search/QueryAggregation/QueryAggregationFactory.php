<?php
/**
 * Класс представляющий из себя конструктор аггрегированных условий
 */
namespace Common\Core\Facade\Search\QueryAggregation;

class QueryAggregationFactory implements QueryAggregationFactoryInterface
{

    /**
     * Получаем аггрегированный скрипт AVG
     *
     * @param string $fieldName Название поле аггрегации
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getAvgAggregation($fieldName, $script)
    {
        $avg = new \Elastica\Aggregation\Avg();
        $avg->setField($fieldName);
        $avg->setScript($script);

        return $avg;
    }

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
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getDateHistogramAggregation($fieldName, $interval, $count = null, $timezone = null, $offset = null, $format = null)
    {
        $dateHistogram = new \Elastica\Aggregation\DateHistogram('date_histogram', $fieldName, $interval);
        if (!is_null($count)) {
            $dateHistogram->setMinimumDocumentCount($count);
        }

        if (!is_null($timezone)) {
            $dateHistogram->setTimezone($timezone);
        }

        if (!is_null($offset)) {
            $dateHistogram->setOffset($offset);
        }

        if (!is_null($format)) {
            $dateHistogram->setFormat($format);
        }

        return $dateHistogram;
    }

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
    ) {
        $geoDistance = new \Elastica\Aggregation\GeoDistance('geo_distance', $fieldName, $startPoint);
        $geoDistance->setUnit($unit);
        $geoDistance->setDistanceType($distanceType);
        $geoDistance->addRange(0, $radius);

        return $geoDistance;
    }

    /**
     * GeoHashGrid аггрегация, определение по ячейкам на карте
     *
     * @param string $fieldName Название поля
     * @param string|array $startPoint valid formats are array("lat" => 52.3760, "lon" => 4.894), "52.3760, 4.894", and array(4.894, 52.3760)
     * @param int $precision an integer between 1 and 12, inclusive. Defaults to 5.
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getGeoHashAggregation($fieldName, $startPoint, $precision = self::DEFAULT_RADIUS_DISTANCE)
    {
        $geoHash = new \Elastica\Aggregation\GeohashGrid('geohash_grid', $fieldName);
        $geoHash->setPrecision((int)$precision);

        return $geoHash;
    }
}