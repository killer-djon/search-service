<?php
/**
 * Класс представляющий из себя конструктор аггрегированных условий
 */
namespace Common\Core\Facade\Search\QueryAggregation;

use Common\Core\Facade\Service\Geo\GeoPointService;
use Common\Core\Facade\Service\RangeIterator;
use Elastica\Filter\AbstractFilter;

class QueryAggregationFactory implements QueryAggregationFactoryInterface
{

    const KOEFFICIENT_BOUNDING_BOX = 15;

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
     * Формирование сетки для аггрегирования данных
     *
     * @param string $fieldName Названиние поля
     * @param bool $setWrapLon
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getGeoBoundBoxAggregation($fieldName, $setWrapLon = true)
    {
        $geoBound = new GeoBounds($fieldName);
        $geoBound->setWrapLongitude($setWrapLon);

        return $geoBound;
    }

    /**
     * Аггрегирование по значению
     * т.е. развернуть bucket документы
     *
     * @param string $fieldName Название поля
     * @return \Elastica\Aggregation\Terms
     */
    public function getTermsAggregation($fieldName)
    {
        $geo = new \Elastica\Aggregation\Terms('distance-hashes');
        $geo->setField($fieldName);
        $geo->setMinimumDocumentCount(1);

        return $geo;
    }

    /**
     * Получаем аггрегированный скрипт AVG
     *
     * @param string $fieldName Название поле аггрегации
     * @param string|\Elastica\Script $script
     * @return \Elastica\Aggregation\AbstractSimpleAggregation
     */
    public function getAvgAggregation($fieldName, $script = null, $name = 'avg_script')
    {
        $avg = new \Elastica\Aggregation\Avg($name);
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
     * Срезы для расстояний
     * применяется для аггрегирования данных
     *
     * @var array
     */
    private $radiusRanges = [

        [200 => [300, 1000]],
        [250 => [1001, 3000]],
        [500 => [3001, 5000]],
        [750 => [5001, 7000]],

        [1000 => [7001, 10000]],
        [2500 => [10001, 25000]],
        [5000 => [25001, 50000]],
        [7500 => [50001, 75000]],

        [50000 => [75001, 100000]],
        [75000 => [100001, 250000]],
        [100000 => [250001, 500000]],
        [250000 => [500001, 750000]],

        [100000 => [750001, 1000000]],
    ];

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
        $skip = (int)$radius / self::KOEFFICIENT_BOUNDING_BOX;
        $rangesArray = array_map(function ($n) {
            return $n;
        }, range(0, (int)$radius, ceil($skip)));

        $newRange = new RangeIterator($rangesArray);
        $newRange->setRadius($radius);

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

        $geoHash = new \Elastica\Aggregation\GeohashGrid('geohash', $fieldName);
        $geoHash->setPrecision($precision);

        return $geoHash;
    }

    /**
     * Применение фильтра для аггрегирования данных
     * как правило применимо для geohash
     *
     * @param string $name Название объекта аггрегирования
     * @param AbstractFilter $filter ОБъект фильтра
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public function getFilterAggregation($name, AbstractFilter $filter = null)
    {
        $filterAggs = new \Elastica\Aggregation\Filter($name);
        if( !is_null($filter) && $filter instanceof AbstractFilter){
            $filterAggs->setFilter($filter);
        }

        return $filterAggs;
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