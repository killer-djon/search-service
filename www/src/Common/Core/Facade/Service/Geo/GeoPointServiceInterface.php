<?php

namespace Common\Core\Facade\Service\Geo;

/**
 * Интерфейс для геоточек
 */
interface GeoPointServiceInterface
{

    /**
     * @param bool $radius
     * @return array
     */
    public function export($radius=false);

    /**
     * Возвращает широту координаты
     *
     * @return null|float
     */
    public function getLatitude();

    /**
     * Возвращает долготу координаты
     *
     * @return null|float
     */
    public function getLongitude();

    /**
     * @param float $lon
     * @return $this
     */
    public function setLongitude(float $lon);

    /**
     * @param float $lat
     * @return float
     */
    public function setLatitude(float $lat);

    /**
     * Возвращает радиус поиска
     *
     * @return null|int
     */
    public function getRadius();

    /**
     * Устанавливаем радиус по умолчанию
     *
     * @param int|null $radius
     */
    public function setRadius($radius = null);

    /**
     * @return boolean
     */
    public function isValid();

    /**
     * @return boolean
     */
    public function isEmpty();
}