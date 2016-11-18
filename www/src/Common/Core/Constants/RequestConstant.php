<?php
namespace Common\Core\Constants;

abstract class RequestConstant
{
    /**
     * Параметр запроса версии
     * необходимо для поддержки старых приложений
     *
     * @var string VERSION_PARAM
     */
    const VERSION_PARAM = 'version';

    /**
     * Параметр запроса версии
     * номер версии
     *
     * @var int DEFAULT_VERSION
     */
    const DEFAULT_VERSION = 3;


    /**
     * Параметр запроса долготы
     * короткое представление
     *
     * @var string LONGITUTE_PARAM
     */
    const SHORT_LONGITUTE_PARAM = 'lon';

    /**
     * Параметр запроса широты
     * короткое представление
     *
     * @var string LATITUDE_PARAM
     */
    const SHORT_LATITUDE_PARAM = 'lat';

    /**
     * Параметр запроса долготы
     * длинное представление
     *
     * @var string LONG_LONGITUTE_PARAM
     */
    const LONG_LONGITUTE_PARAM = 'longitude';

    /**
     * Параметр запроса широты
     * длинное представление
     *
     * @var string LONG_LATITUDE_PARAM
     */
    const LONG_LATITUDE_PARAM = 'latitude';

    /**
     * Параметр запроса радиуса
     *
     * @var string RADIUS_PARAM
     */
    const RADIUS_PARAM = 'radius';

    /**
     * Параметр поисковой строки
     * для поиска по контексту
     *
     * @conts string SEARCH_TEXT_PARAM
     */
    const SEARCH_TEXT_PARAM = 'searchText';

    /**
     * Параметр поисковой строки
     * id пользователя
     *
     * @conts string USER_ID_PARAM
     */
    const USER_ID_PARAM = 'userId';

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
     * кол-во пропускаемых записей поиска
     *
     * @conts string SEARCH_SKIP_PARAM
     */
    const SEARCH_SKIP_PARAM = 'skip';

    /**
     * Параметр поисковой строки
     * кол-во искомых записей
     *
     * @conts string SEARCH_LIMIT_PARAM
     */
    const SEARCH_LIMIT_PARAM = 'count';

    /**
     * В случае пустого параметра запроса (коорый необходим или который ожидаем)
     * выставляем нулевое значение
     *
     * @const NULL NULLED_PARAMS
     */
    const NULLED_PARAMS = NULL;

    /**
     * Лимит при поиск в еластике по умолчанию
     *
     * @const int DEFAULT_SEARCH_LIMIT
     */
    const DEFAULT_SEARCH_LIMIT = 20;

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
}