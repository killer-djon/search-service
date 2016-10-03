<?php
namespace Common\Core\Constants;

class RequestConstant
{
    /**
     * Параметр поисковой строки
     * для поиска по контексту
     *
     * @conts string SEARCH_TEXT_PARAM
     */
    const SEARCH_TEXT_PARAM = 'searchText';

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
    const DEFAULT_SEARCH_LIMIT = NULL;

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