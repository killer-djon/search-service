<?php

namespace Common\Core\Constants;

/**
 * Class RequestConstant
 * @package Common\Core\Constants
 */
abstract class RequestConstant
{

    /**
     * Параметр запроса представляющий платформу
     * с которой был совершен запрос
     *
     * @const string PLATFORM_PARAM
     */
    const PLATFORM_PARAM = 'platform';

    /**
     * Платформа Android
     *
     * @const string PLATFORM_ANDROID
     */
    const PLATFORM_ANDROID = 'android';

    /**
     * Платформа IOS
     *
     * @const string PLATFORM_IOS
     */
    const PLATFORM_IOS = 'ios';

    /**
     * Платформа WEB
     *
     * @const string PLATFORM_WEB
     */
    const PLATFORM_WEB = 'web';

    /**
     * Параметр запроса класстерных данных
     *
     * @const bool IS_CLUSTER_PARAM
     */
    const IS_CLUSTER_PARAM = 'isCluster';

    /**
     * Параметр запроса level массива
     * выводить ли одноуровневый/склеенный массив или нет
     * по умолчанию false - не выводить
     *
     * @const bool IS_FLAT_PARAM
     */
    const IS_FLAT_PARAM = 'isFlat';

    /**
     * Параметр запроса версии
     * необходимо для поддержки старых приложений
     *
     * @const string VERSION_PARAM
     */
    const VERSION_PARAM = 'version';

    /**
     * Параметр запроса версии
     * номер версии
     *
     * @const int DEFAULT_VERSION
     */
    const DEFAULT_VERSION = 3;

    /**
     * Параметр запроса версии
     * номер версии (новая фишка)
     *
     * @const int NEW_DEFAULT_VERSION
     */
    const NEW_DEFAULT_VERSION = 4;

    /**
     * Параметр запроса долготы
     * короткое представление
     *
     * @const string LONGITUTE_PARAM
     */
    const SHORT_LONGITUTE_PARAM = 'lon';

    /**
     * Параметр запроса широты
     * короткое представление
     *
     * @const string LATITUDE_PARAM
     */
    const SHORT_LATITUDE_PARAM = 'lat';

    /**
     * Параметр запроса долготы
     * длинное представление
     *
     * @const string LONG_LONGITUTE_PARAM
     */
    const LONG_LONGITUTE_PARAM = 'longitude';

    /**
     * Параметр запроса широты
     * длинное представление
     *
     * @const string LONG_LATITUDE_PARAM
     */
    const LONG_LATITUDE_PARAM = 'latitude';

    /**
     * Параметр запроса радиуса
     *
     * @const string RADIUS_PARAM
     */
    const RADIUS_PARAM = 'radius';

    /**
     * Параметр поисковой строки
     * для поиска по контексту
     *
     * @const string SEARCH_TEXT_PARAM
     */
    const SEARCH_TEXT_PARAM = 'searchText';

    /**
     * Параметр поисковой строки
     * id пользователя
     *
     * @const string USER_ID_PARAM
     */
    const USER_ID_PARAM = 'userId';

    /**
     * Параметр поисковой строки
     * id пользователя который хотим просмотреть (не равен текущему пользователю)
     *
     * @const string TARGET_USER_ID_PARAM
     */
    const TARGET_USER_ID_PARAM = 'targetUserId';

    /**
     * Параметр поиска при запросе по стране
     *
     * @const string COUNTRY_SEARCH_PARAM
     */
    const COUNTRY_SEARCH_PARAM = 'countryId';

    /**
     * Параметр поиска при запросе по городу
     *
     * @const string CITY_SEARCH_PARAM
     */
    const CITY_SEARCH_PARAM = 'cityId';

    /**
     * Параметр запроса ID чата
     *
     * @const string CHAT_ID_PARAM
     */
    const CHAT_ID_PARAM = 'chatId';

    /**
     * Параметр поисковой строки
     * способ сортировки
     *
     * @const string SEARCH_SORT_PARAM
     */
    const SEARCH_SORT_PARAM = 'sort';

    /**
     * Параметр поисковой строки
     * кол-во пропускаемых записей поиска
     *
     * @const string SEARCH_SKIP_PARAM
     */
    const SEARCH_SKIP_PARAM = 'skip';

    /**
     * Параметр поисковой строки
     * кол-во искомых записей
     *
     * @const string SEARCH_LIMIT_PARAM
     */
    const SEARCH_LIMIT_PARAM = 'count';

    /**
     * Параметр поисковой строки
     * кол-во искомых записей
     *
     * @const string SEARCH_FROM_PARAM
     */
    const SEARCH_FROM_PARAM = 'from';

    /**
     * В случае пустого параметра запроса (коорый необходим или который ожидаем)
     * выставляем нулевое значение
     *
     * @const NULL NULLED_PARAMS
     */
    const NULLED_PARAMS = null;

    /**
     * Лимит при поиск в еластике по умолчанию
     *
     * @const int DEFAULT_SEARCH_LIMIT
     */
    const DEFAULT_SEARCH_LIMIT = 20;

    /**
     * Лимит при поиск в еластике по умолчанию
     *
     * @const int DEFAULT_SEARCH_UNLIMIT
     */
    const DEFAULT_SEARCH_UNLIMIT = 10000;

    /**
     * Кол-во пропускаем позиций при поиске с лимотом
     *
     * @const int DEFAULT_SEARCH_SKIP
     */
    const DEFAULT_SEARCH_SKIP = 0;

    /**
     * Скор по умолчанию (вес поиска)
     *
     * @const float DEFAULT_SEARCH_MIN_SCORE
     */
    const DEFAULT_SEARCH_MIN_SCORE = 0.1;

    /**
     * Параметр фильтров передаваемый
     * в запросе (как правило набор ключей, типа: friends,commonFriends ...)
     *
     * @const string FILTERS_PARAM
     */
    const FILTERS_PARAM = 'filters';
}