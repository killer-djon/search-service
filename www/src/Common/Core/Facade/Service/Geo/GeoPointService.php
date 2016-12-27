<?php
namespace Common\Core\Facade\Service\Geo;

/**
 * Сервис для работы с геоточками
 */
class GeoPointService implements GeoPointServiceInterface
{

    /**
     * Радиус сферы земного шара
     *
     * @const int
     */
    const EARTH_RADIUS = 6378137;
    /* ----------------- Constraint parameters ----------------- */

    /** Минимальное значение для широты координаты */
    const LATITUDE_MIN = -90.0;

    /** Максимальное значение для широты координаты */
    const LATITUDE_MAX = 90.0;

    /** Минимальное значение для долготы координаты */
    const LONGITUDE_MIN = -180.0;

    /** Максимальное значение для долготы координаты */
    const LONGITUDE_MAX = 180.0;

    /* ---------------- \ Constraints parameters ---------------- */

    /* ---------------- Error messages ---------------- */
    /** Широта координаты неверного типа */
    const LATITUDE_TYPE_VIOLATION = 'GeoPoint.error.latitudeTypeViolation';

    /** Долгота координаты неверного типа */
    const LONGITUDE_TYPE_VIOLATION = 'GeoPoint.error.longitudeTypeViolation';

    /** Широта координаты не удовлетворяет минимальному допустимому значению */
    const LATITUDE_MIN_RANGE_VIOLATION = 'GeoPoint.error.latitudeMinRangeViolation';

    /** Широта координаты не удовлетворяет макcимальному допустимому значению */
    const LATITUDE_MAX_RANGE_VIOLATION = 'GeoPoint.error.latitudeMaxRangeViolation';

    /** Долгота координаты не удовлетворяет минимальному допустимому значению */
    const LONGITUDE_MIN_RANGE_VIOLATION = 'GeoPoint.error.longitudeMinRangeViolation';

    /** Долгота координаты не удовлетворяет максимальному допустимому значению */
    const LONGITUDE_MAX_RANGE_VIOLATION = 'GeoPoint.error.longitudeMaxRangeViolation';
    /* ---------------- \Error messages ---------------- */

    /**
     * Широта
     *
     * @var float
     */
    private $_latitude;

    /**
     * Долгота
     *
     * @var float
     */
    private $_longitude;

    /**
     * Параметр радиуса
     *
     * @var int $_radius
     */
    private $_radius;

    /**
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct($latitude, $longitude, $radius = null)
    {
        $this->_setLatitude((float)$latitude);
        $this->_setLongitude((float)$longitude);
        $this->_setRadius($radius);
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->_longitude;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->_latitude;
    }

    /**
     * Возвращает радиус поиска
     *
     * @return null|int
     */
    public function getRadius()
    {
        return $this->_radius ?: null;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->getLatitude() !== null && $this->getLongitude() !== null;
    }

    /**
     * @param float $latitude
     */
    private function _setLatitude($latitude)
    {
        $this->validateLatitude($latitude);
        $this->_latitude = $latitude;
    }

    /**
     * @param float $longitude
     */
    private function _setLongitude($longitude)
    {
        $this->validateLongitude($longitude);
        $this->_longitude = $longitude;
    }

    /**
     * @param int $radius
     */
    private function _setRadius($radius)
    {
        $this->_radius = $radius;
    }

    /**
     * Валидирует значение широты координаты
     *
     * @param float $latitude
     * @throws \InvalidArgumentException
     */
    protected function validateLatitude($latitude)
    {
        // Проверить тип данных
        if (!is_float($latitude)) {
            throw new \InvalidArgumentException(self::LATITUDE_TYPE_VIOLATION);
        }

        // Широта должна быть в пределах от -90 до 90
        if ($latitude > self::LATITUDE_MAX) {
            throw new \InvalidArgumentException(self::LATITUDE_MAX_RANGE_VIOLATION);
        }
        if ($latitude < self::LATITUDE_MIN) {
            throw new \InvalidArgumentException(self::LATITUDE_MIN_RANGE_VIOLATION);
        }
    }

    /**
     * Валидирует значение долготы координаты
     *
     * @param float $longitude
     * @throws \InvalidArgumentException
     */
    protected function validateLongitude($longitude)
    {
        // Проверить тип данных
        if (!is_float($longitude)) {
            throw new \InvalidArgumentException(self::LONGITUDE_TYPE_VIOLATION);
        }

        // Долгота должна быть в пределах от -180 до 180
        if ($longitude > self::LONGITUDE_MAX) {
            throw new \InvalidArgumentException(self::LONGITUDE_MAX_RANGE_VIOLATION);
        }
        if ($longitude < self::LONGITUDE_MIN) {
            throw new \InvalidArgumentException(self::LONGITUDE_MIN_RANGE_VIOLATION);
        }
    }

    /**
     * Get a center latitude,longitude from an array of like geopoints
     *
     * @param array $data 2 dimensional array of latitudes and longitudes
     * For Example:
     * $data = array
     * (
     *   0 = > array('latitude' => 45.849382, 'longitude' => 76.322333),
     *   1 = > array('latitude' => 45.843543, 'longitude' => 75.324143),
     *   2 = > array('latitude' => 45.765744, 'longitude' => 76.543223),
     *   3 = > array('latitude' => 45.784234, 'longitude' => 74.542335)
     * );
     * @return array
     */
    public static function GetCenterFromDegrees(array $data)
    {
        if (!count($data)) {
            return [];
        }

        $num_coords = count($data);

        $X = 0.0;
        $Y = 0.0;
        $Z = 0.0;

        foreach ($data as $coord) {
            $lat = $coord['latitude'] * pi() / 180;
            $lon = $coord['longitude'] * pi() / 180;

            $a = cos($lat) * cos($lon);
            $b = cos($lat) * sin($lon);
            $c = sin($lat);

            $X += $a;
            $Y += $b;
            $Z += $c;
        }

        $X /= $num_coords;
        $Y /= $num_coords;
        $Z /= $num_coords;

        $lon = atan2($Y, $X);
        $hyp = sqrt($X * $X + $Y * $Y);
        $lat = atan2($Z, $hyp);

        return [
            'latitude'  => $lat * 180 / pi(),
            'longitude' => $lon * 180 / pi(),
        ];
    }

    /**
     * Добавляем метры/киллометры к координате широты
     *
     * @param float $lat Широта
     * @param int $meters Сколько метров прибавляем
     * @return float Новое значение координаты широты
     */
    public static function addDistanceToLat($lat, $meters = 0)
    {
         $newLat = $meters/self::EARTH_RADIUS;

         return $lat + $newLat * self::LONGITUDE_MAX / pi();
    }

    /**
     * Добавляем метры/киллометры к координате долготы
     *
     * @param float $lat Широта
     * @param float $lon Долгота
     * @param int $meters Сколько метров прибавляем
     * @return float Новое значение координаты долготы
     */
    public static function addDistanceToLon($lat, $lon, $meters = 0)
    {
        $newLon = $meters / (self::EARTH_RADIUS * cos(pi() * $lat / self::LONGITUDE_MAX));

        return $lon + $newLon * self::LONGITUDE_MAX / pi();
    }


    /**
     * Добавляем метры/киллометры к координате широты
     *
     * @param float $lat Широта
     * @param int $meters Сколько метров прибавляем
     * @return float Новое значение координаты широты
     */
    public static function removeDistanceFromLat($lat, $meters = 0)
    {
        $newLat = $meters/self::EARTH_RADIUS;

        return $lat - $newLat * self::LONGITUDE_MAX / pi();
    }

    /**
     * Добавляем метры/киллометры к координате долготы
     *
     * @param float $lat Широта
     * @param float $lon Долгота
     * @param int $meters Сколько метров прибавляем
     * @return float Новое значение координаты долготы
     */
    public static function removeDistanceFromLon($lat, $lon, $meters = 0)
    {
        $newLon = $meters / (self::EARTH_RADIUS * cos(pi() * $lat / self::LONGITUDE_MAX));

        return $lon - $newLon * self::LONGITUDE_MAX / pi();
    }

}