<?php
/**
 * Файл маппинга тегов
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class TagNameSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'tags';

    /** ID тега */
    const TAG_NAME_ID_FIELD = 'id';

    /** Маппинг поля потребности в модерации */
    const IS_APPROVAL_REQUIRED_FIELD = 'isApprovalRequired';

    /** Маппинг поля количества учуастников тега */
    const USERS_COUNT_FIELD = 'usersCount';

    /** Маппинг поля количества мест тега */
    const PLACE_COUNT_FIELD = 'placeCount';

    /** Маппинг поля количества мест тега */
    const EVENTS_COUNT_FIELD = 'eventsCount';

    /** Название поля суммарного количества сущностей */
    const TOTAL_FIELD = 'sumCount';

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiMatchQuerySearchFields()
    {
        return [
            // вариации поля имени
            self::NAME_FIELD,
            self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_FIELD,
            self::NAME_TRANSLIT_NGRAM_FIELD,
            self::RUS_TRANSLITERATE_NAME
        ];
    }

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [];
    }

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [];
    }
}