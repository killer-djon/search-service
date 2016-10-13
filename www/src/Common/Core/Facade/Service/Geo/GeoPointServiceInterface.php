<?php
namespace Common\Core\Facade\Service\Geo;
/**
 * Интерфейс для геоточек
 */
interface GeoPointServiceInterface
{
    /**
     * Возвращает широту координаты
     * @return null|float
     */
    public function getLatitude();

    /**
     * Возвращает долготу координаты
     * @return null|float
     */
    public function getLongitude();

    /**
     * Возвращает радиус поиска
     * @return null|int
     */
    public function getRadius();

    /**
     * @return boolean
     */
    public function isValid();
}