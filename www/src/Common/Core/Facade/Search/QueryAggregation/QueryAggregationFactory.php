<?php
/**
 * Класс представляющий из себя конструктор аггрегированных условий
 */
namespace Common\Core\Facade\Search\QueryAggregation;

use Common\Core\Facade\Service\RangeIterator;

class QueryAggregationFactory implements QueryAggregationFactoryInterface
{
    /**
     * Определение точности кластеризации по геоточкам
     *
     * @var array массив прямоугольников
     */
    private $geo_hash_precisions = [
        [5009400.0, 4992600.0],
        [1252300.0, 624100.0],
        [156500.0, 156000.0],
        [39100.0, 19500.0],
        [4900.0, 4900.0],
        [1200.0, 1200.0],
        [152.9, 152.4],
        [38.2, 19],
        [4.8, 4.8],
        [1.2, 0.595],
        [0.149, 0.149],
        [0.037, 0.019],
    ];

    /**
     * Срезы для расстояний
     * применяется для аггрегирования данных
     *
     * @var array
     */
    private $radiusRanges = [
        [200 => [200, 3000]],
        [300 => [3001, 5000]],
        [500 => [5001, 7000]],
        [1000 => [7001, 15000]],
        [3000 => [15001, 30000]],
        [5000 => [30001, 60000]],
        [7000 => [60001, 100000]],
        [10000 => [100001, 300000]],
        [20000 => [300001, 500000]],
        [30000 => [500001, 700000]],
        [50000 => [700001, 1000000]],
    ];

    /**
     * Получаем аггрегированный скрипт AVG
     *
     * @param string $fieldName Название поле аггрегации
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\AbstractSimpleAggregation
     */
    public function getAvgAggregation($fieldName, $script = null)
    {
        $avg = new \Elastica\Aggregation\Avg('avg_script');
        $avg->setField($fieldName);
        if (!is_null($script)) {
            $avg->setScript($script);
        }

        return $avg;
    }

    public function getGeoCentroidAggregation($fieldName)
    {
        return new GeoCentroid('centroid', $fieldName);
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
     * @return \Elastica\Aggregation\AbstractSimpleAggregation
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

        $skip = 1000;
        foreach ($this->radiusRanges as $range) {
            foreach ($range as $skipKey => $ranges) {
                if ((int)$radius >= $ranges[0] && (int)$radius <= $ranges[1]) {
                    $skip = $skipKey;
                }
            }
        }

        $rangesArray = array_map(function ($n) {
            return $n;
        }, range(0, (int)$radius, 10));

        $newRange = new RangeIterator($rangesArray);

        $geoDistance = new \Elastica\Aggregation\GeoDistance('geo_distance', $fieldName, $startPoint);
        $geoDistance->setUnit($unit);
        $geoDistance->setDistanceType($distanceType);

        foreach ($newRange->getRange() as $range) {
            $geoDistance->addRange($range[0], $range[1]);
        }

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
        $precision = (int)$precision;

        if ($precision > 12 || $precision < 1) {
            $precisionPoint = [];
            foreach ($this->geo_hash_precisions as $key => $pointMap) {
                $min = $pointMap[0];
                $max = $pointMap[1];

                $x = $min / $precision;
                $y = $max / $precision;

                $precisionPoint[$key] = [
                    'min' => (float)$x,
                    'max' => (float)$y,
                ];
            }

            $precisionPoint = array_filter(array_map(function ($range) {
                return array_sum($range);
            }, $precisionPoint), function ($arValue) {
                return $arValue >= 1;
            });

            $precision = current(array_keys($precisionPoint, min($precisionPoint))) + 1;
        }

        $geoHash = new \Elastica\Aggregation\GeohashGrid('geohash_grid', $fieldName);
        $geoHash->setPrecision($precision);
        $geoHash->setShardSize(3);

        return $geoHash;
    }

    /**
     * Возвращаем единственный результат запроса
     * нужно когда например получаем данные по ID
     *
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getTopHitsAggregation()
    {
        $topHits = new \Elastica\Aggregation\TopHits('top_hits');
        $topHits->setSize(1);

        return $topHits;
    }

    /**
     * Установка исходных данных в агррегированные данные
     *
     * @param string $fieldName Название поля
     * @param array $fields Набор полей которые нужно выводить
     * @param int|null $size Сколько объектов указывать
     * @return \Elastica\Aggregation\TopHits
     */
    public function setAggregationSource($fieldName, $fields = [], $size = null)
    {
        $topHits = new \Elastica\Aggregation\TopHits($fieldName);
        if (!empty($fields)) {
            $topHits->setSource($fields);
        }
        if (!is_null($size)) {
            $topHits->setSize($size);
        }

        return $topHits;
    }
}