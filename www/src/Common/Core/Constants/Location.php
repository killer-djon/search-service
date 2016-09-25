<?php
namespace Common\Core\Constants;

class Location
{
    /**
	 * Параметр широты локации
	 * @const string LATITUDE
     */
    const LATITUDE = 'lat';
    
    /**
	 * Параметр долготы локации
	 * @const string LONGITUDE
     */
    const LONGITUDE = 'lng';
    
    /**
	 * Параметр радиуса локации
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
	 * @const string METER
     */
    const METER = 'm';
    
    /**
	 * Обозначение километра
	 * @const string KILOMETER
     */
    const KILOMETER = 'km';
    
    /**
	 * Сколько метров в одном килоемтре
	 * @const string METERS_IN_KILOMETER
     */
    const METERS_IN_KILOMETER = 1000;

    /**
	 * Широта локации по умолчанию (Москва)
	 * @const string DEFAULT_LATITUDE
     */
    const DEFAULT_LATITUDE = 55.7516;
    
    /**
	 * Долгота локации по умолчанию (Москва)
	 * @const string DEFAULT_LONGITUDE
     */
    const DEFAULT_LONGITUDE = 37.6186;
}

