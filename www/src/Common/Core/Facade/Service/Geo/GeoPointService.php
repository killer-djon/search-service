<?php
namespace Common\Core\Facade\Service\Geo;

/**
 * Сервис для работы с геоточками
 */
class GeoPointService implements GeoPointServiceInterface
{
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
        return $this->_radius ?: NULL;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->getLatitude() !== null && (int)$this->getLatitude() != 0
                && $this->getLongitude() !== null && (int)$this->getLongitude() != 0;
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
}