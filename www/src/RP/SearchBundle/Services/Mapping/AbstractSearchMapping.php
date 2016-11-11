<?php
/**
 * Базовый абстрактный класс мапперов поиска
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class AbstractSearchMapping
{
    /** Поле имени пользователя */
    const NAME_FIELD = 'name'; // полное совпадение имени по русски
    const NAME_NGRAM_FIELD = 'name._nameNgram'; // частичное совпадение имени от 3-х сивмолов по русски
    const NAME_TRANSLIT_FIELD = 'name._translit'; // полное совпадение имени в транслите
    const NAME_TRANSLIT_NGRAM_FIELD = 'name._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите
    const NAME_WORDS_NAME_FIELD = 'name._wordsName'; // частичное совпадение имени от 3-х сивмолов в транслите

    /** поле описания места */
    const DESCRIPTION_FIELD = 'description';

    /** Поле с интересами пользователя */
    const TAG_FIELD         = 'tags';
    const TAGS_ID_FIELD      = 'tags.id';
    const TAG_NAME_FIELD    = 'tags.name';
    const TAG_NAME_NGRAM_FIELD    = 'tags._nameNgram';
    const TAG_NAME_TRANSLIT_FIELD    = 'tags._translit';
    const TAG_NAME_TRANSLIT_NGRAM_FIELD    = 'tags._translitNgram';
    const TAG_WORDS_FIELD    = 'tags._wordsName';

    /** Поле идентификатора города */
    const LOCATION_CITY_ID_FIELD = 'location.city.id';

    const LOCATION_POINT_FIELD = 'location.point';

    /** Город местоположения */
    const LOCATION_CITY_FIELD = 'location.city';
    const LOCATION_CITY_NAME_FIELD = 'location.city.name';
    const LOCATION_CITY_NAME_TRANSLIT_FIELD = 'location.city._translit';
    const LOCATION_CITY_NAME_PREFIX_FIELD = 'location.city._prefix';
    const LOCATION_CITY_INTERNATIONAL_NAME_FIELD = 'location.city.internationalName';

    /** Страна местоположения */
    const LOCATION_COUNTRY_ID_FIELD = 'location.country.id';
    const LOCATION_COUNTRY_NAME_FIELD = 'location.country.name';
    const LOCATION_COUNTRY_NAME_TRANSLIT_FIELD = 'location.country._translit';
    const LOCATION_COUNTRY_NAME_PREFIX_FIELD = 'location.country._prefix';
    const LOCATION_COUNTRY_INTERNATIONAL_NAME_FIELD = 'location.country.internationalName';

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    abstract public static function getMultiMatchQuerySearchFields();

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    abstract public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null);

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    abstract public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null);
}