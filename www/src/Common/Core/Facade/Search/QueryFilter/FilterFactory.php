<?php
namespace Common\Core\Facade\Search\QueryFilter;

use Common\Core\Facade\Service\Geo\GeoPointService;
use Elastica\Filter\AbstractFilter;
use Elastica\Filter\Query;
use Elastica\Query\AbstractQuery;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;

class FilterFactory implements FilterFactoryInterface
{

    /**
     * Меры расстояний
     *
     * @var array $unitDefinitions
     */
    private $unitDefinitions = ['m', 'mi', 'yd', 'ft', 'in', 'cm', 'mm', 'nmi', 'km'];

    private function checkPrecision($precision)
    {
        if ((int)$precision < 1) {
            $precision = 1;
        } else {
            if ((int)$precision > 12) {
                $precision = 12;
            } else {
                $precision = (int)$precision;
            }
        }

        return $precision;
    }


    /**
     * Возвращает фильтр по условию GeoHash
     *
     * @https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geohash-cell-query.html
     * @abstract
     * @param string $fieldName
     * @param array $point , must be ['lat' => 40.3, 'lon' => 45.2]
     * @param int $precision Meters
     * @param string $geohash
     * @param bool $neighbors Используем ли соседние ячейки
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoHashFilter($fieldName, array $point, $precision = -1, $geohash = null, $neighbors = false)
    {
        $geohashCell = new \Elastica\Filter\GeohashCell($fieldName, $point, $precision, $neighbors);
        if (!is_null($geohash) && !empty($geohash)) {
            $geohashCell->setPrecision(-1);
            $geohashCell->setGeohash($geohash);
        }

        return $geohashCell;
    }

    /**
     * Возвращает фильтр по условию больше
     *
     * @abstract
     * @param string $fieldName
     * @param mixed $value
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGtFilter($fieldName, $value)
    {
        return new \Elastica\Filter\Range($fieldName, [
            'gt' => $value,
        ]);
    }

    /**
     * Возвращает фильтр c условием по term
     * как условие строгое равно
     *
     * @abstract
     * @param array $terms Условие точного совпадения
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getTermFilter(array $terms)
    {
        return new \Elastica\Filter\Term($terms);
    }


    /**
     * Возвращает фильтр c условием по term
     * как условие содержащее ( IN ... )
     *
     * @abstract
     * @param string $fieldName Имя поля
     * @param array $terms Условие точного совпадения
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getTermsFilter($fieldName, array $terms)
    {
        return new \Elastica\Filter\Terms($fieldName, $terms);
    }

    /**
     * Возвращает фильтр со скриптом поиска
     *
     * @abstract
     * @param array|string|\Elastica\Script $script OPTIONAL Script
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getScriptFilter($script)
    {
        return new \Elastica\Filter\Script($script);
    }

    /**
     * Возвращает фильтр по условию регулярного выражения
     *
     * @abstract
     * @param string $fieldName Field name
     * @param string $regexp Regular expression
     * @param array $options Regular expression options
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getRegexpFilter($fieldName, $regexp, array $options = [])
    {
        return new \Elastica\Filter\Regexp($fieldName, $regexp, $options);
    }

    /**
     * Возвращает фильтр по удаленности от геоточки
     *
     * @abstract
     * @param string $fieldName Поле фильтра
     * @param array $point , must be ['lat' => 40.3, 'lon' => 45.2]
     * @param string $distance Радиус фильтра в километрах|метрах
     * @param string $unit единица расстояния (default: km)
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoDistanceFilter($fieldName, array $point, $distance, $unit = 'km')
    {
        $distance = (int)$distance . $unit;
        $geoDistanceFilter = new \Elastica\Filter\GeoDistance($fieldName, $point, $distance);
        $geoDistanceFilter->setParam('ignore_malformed', false);

        return $geoDistanceFilter;
    }

    /**
     * Возвращает фильтр по промежутку расстояний (например: от 200км до 400км)
     *
     * @abstract
     * @param string $fieldName Поле фильтра
     * @param array $point Точка местоположения
     * @param array $ranges Промежуток расстояния
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoDistanceRangeFilter($fieldName, array $point, array $ranges)
    {
        return new \Elastica\Filter\GeoDistanceRange($fieldName, $point, $ranges);
    }

    /**
     * Возвращает фильтр выборки по ИД
     *
     * @abstract
     * @param array $idList
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getIdsFilter(array $idList)
    {
        return new \Elastica\Filter\Ids(null, $idList);
    }

    /**
     * Возвращает фильтр выборки по диапазону
     *
     * @param string $fieldName
     * @param string $from
     * @param string $to
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getRangeFilter($fieldName, $from, $to)
    {
        return new \Elastica\Filter\Range($fieldName, [
            'gt' => "$from",
            'lt' => "$to",
        ]);
    }

    /**
     * Возвращает фильтр выборки по диапазону
     * для number/date типов полей
     *
     * @param string $fieldName
     * @param string $from
     * @param string $to
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getNumericRangeFilter($fieldName, $from, $to)
    {
        return new \Elastica\Filter\NumericRange($fieldName, [
            'gt' => $from,
            'lt' => $to,
        ]);
    }

    /**
     * Возвращает фильтр выборки по площади
     *
     * @param string $fieldName
     * @param array[] $points Многомерный массив точке lat,lon полигона
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getGeoPolygonFilter($fieldName, array $points)
    {
        return new \Elastica\Filter\GeoPolygon($fieldName, $points);
    }

    /**
     * Возвращает фильтр исключения из поиска
     *
     * @abstract
     * @param \Elastica\Filter\AbstractFilter $filterCondition
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getNotFilter(AbstractFilter $filterCondition)
    {
        return new \Elastica\Filter\BoolNot($filterCondition);
    }

    /**
     * Возвращает фильтр уловия или
     *
     * @param array \Elastica\Filter\AbstractFilter[] $filterConditions
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getBoolOrFilter(array $filterConditions)
    {
        $filterConditions = array_filter($filterConditions, function ($filter) {
            return $filter instanceof AbstractFilter;
        });

        return new \Elastica\Filter\BoolOr($filterConditions);
    }

    /**
     * Возвращает фильтр уловия и
     *
     * @param array \Elastica\Filter\AbstractFilter[] $filterConditions
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getBoolAndFilter(array $filterConditions)
    {
        $filterConditions = array_filter($filterConditions, function ($filter) {
            return $filter instanceof AbstractFilter;
        });

        return new \Elastica\Filter\BoolAnd($filterConditions);
    }

    /**
     * Возвращает фильтр существования поля
     *
     * @param string $fieldName
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getExistsFilter($fieldName)
    {
        return new \Elastica\Filter\Exists($fieldName);
    }

    /**
     * Возвращает фильтр несуществующего поля
     *
     * @param string $fieldName
     * @return \Elastica\Filter\Missing
     */
    public function getMissingFilter($fieldName)
    {
        $missingFilter = new \Elastica\Filter\Missing($fieldName);

        return $missingFilter;
    }

    /**
     * Основной объект фильтра для формированиия запроса
     * Filtered Query
     *
     * @param array $must Массив обязательных для исполнения условий фильтра
     * @param array $must Массив с желательными для исполнения условий фильтра
     * @param array $must Массив условий фильтра которые не должны исполнятся (т.е. отрицание)
     * @return \Elastica\Filter\BoolFilter
     */
    public function getBoolFilter(array $must, array $should, array $mustNot)
    {
        $boolFilter = new \Elastica\Filter\BoolFilter();
        $boolFilter->addMust($must);
        $boolFilter->addShould($should);
        $boolFilter->addMustNot($mustNot);

        return $boolFilter;
    }

    /**
     * Возвращает фильтр типа
     *
     * @abstract
     * @param string $type
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getTypeFilter($type)
    {
        return new \Elastica\Filter\Type($type);
    }

    /**
     * Фильтр который позволяет получить объекты в квадрате карты
     *
     * @param string $fieldName Название поля
     * @param array $coordinates Координаты
     * @param int $radius Радиус в метрах
     * @return array
     */
    public function getBoundingBoxFilter($fieldName, array $coordinates, $radius)
    {
        $startPoints = [
            'lat' => GeoPointService::addDistanceToLat($coordinates['lat'], $radius / 2),
            'lon' => GeoPointService::removeDistanceFromLon($coordinates['lat'], $coordinates['lon'], $radius / 2),
        ];

        $endPoints = [
            'lat' => GeoPointService::removeDistanceFromLat($coordinates['lat'], $radius / 2),
            'lon' => GeoPointService::addDistanceToLon($coordinates['lat'], $coordinates['lon'], $radius / 2),
        ];

        return new \Elastica\Filter\GeoBoundingBox($fieldName, [$startPoints, $endPoints]);

    }

    /**
     * Используем фильтр для nested объектов
     *
     * @param string $path Ключ nested объекта
     * @param AbstractFilter $filterQuery
     * @return AbstractFilter $filter
     */
    public function getNestedFilter($path, AbstractFilter $filterQuery)
    {
        $filter = new \Elastica\Filter\Nested();
        $filter->setPath($path);
        $filter->setFilter($filterQuery);

        return $filter;
    }


    /**
     * Query filter.
     * Запрос в фильтре
     *
     * @param AbstractQuery $query
     * @return AbstractFilter $filter
     */
    public function getQueryFilter(AbstractQuery $query)
    {
        $queryFilter = new Query();
        $queryFilter->setQuery($query);

        return $queryFilter;
    }


}