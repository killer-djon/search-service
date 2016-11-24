<?php
namespace Common\Core\Constants;

abstract class Location
{
    /**
     * Параметр широты локации
     *
     * @const string LATITUDE
     */
    const LATITUDE = 'lat';

    /**
     * Параметр долготы локации
     *
     * @const string LONGITUDE
     */
    const LONGITUDE = 'lon';

    /**
     * Параметр широты локации
     *
     * @const string LONG_LATITUDE
     */
    const LONG_LATITUDE = 'latitude';

    /**
     * Параметр долготы локации
     *
     * @const string LONG_LONGITUDE
     */
    const LONG_LONGITUDE = 'longitude';

    /**
     * Параметр наличия geohash
     *
     * @const string GEO_HASH_CELL_PARAM
     */
    const GEO_HASH_CELL_PARAM = 'geohash';

    /**
     * Параметр радиуса локации
     *
     * @const string RADIUS
     */
    const RADIUS = 'radius';

    // Параметр передачи широты и долготы в сессии
    const SESSION_LOCATION = 'location';

    // Параметр передачи радиуса в сессии
    const SESSION_RADIUS = 'radius';

    // Текущая точка просмотра на карте
    const SESSION_VIEWPOINT = 'map.viewpoint';

    // Значение радиуса по умолчанию в метрах
    const DEFAUIT_RADIUS = 5000;

    /**
     * Обозначение метра
     *
     * @const string METER
     */
    const METER = 'm';

    /**
     * Обозначение километра
     *
     * @const string KILOMETER
     */
    const KILOMETER = 'km';

    /**
     * Сколько метров в одном килоемтре
     *
     * @const string METERS_IN_KILOMETER
     */
    const METERS_IN_KILOMETER = 1000;

    /**
     * Широта локации по умолчанию (Москва)
     *
     * @const string DEFAULT_LATITUDE
     */
    const DEFAULT_LATITUDE = 55.7516;

    /**
     * Долгота локации по умолчанию (Москва)
     *
     * @const string DEFAULT_LONGITUDE
     */
    const DEFAULT_LONGITUDE = 37.6186;

    /**
     * Тип города
     *
     * @const string CITY_TYPE
     */
    const CITY_TYPE = 'city';

    /**
     * Тип административного центра
     *
     * @const string TOWN_TYPE
     */
    const TOWN_TYPE = 'town';

    /**
     * Тип деревня
     *
     * @const string VILLAGE_TYPE
     */
    const VILLAGE_TYPE = 'village';

    /**
     * Тип пригород
     *
     * @const string SUBURBAN_TYPE
     */
    const SUBURBAN_TYPE = 'suburb';
}

