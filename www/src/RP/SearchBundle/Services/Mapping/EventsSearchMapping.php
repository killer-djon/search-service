<?php
/**
 * Маппинг класса поиска событий
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

class EventsSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'events';

    /** Тип события */
    const TYPE_ID_FIELD     = 'type.id';
    const TYPE_NAME_FIELD   = 'type.name';
    const TYPE_NAME_NGRAM_FIELD   = 'type._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD   = 'type._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD   = 'type._translitNgram';
    const TYPE_WORDS_FIELD    = 'type._wordsName';

    /** Помечено ли на уделание событие */
    const IS_REMOVED = 'isRemoved';

    /** Помечено ли на уделание событие */
    const PLACE_IS_REMOVED = 'place.isRemoved';

    /** Дополнительные поля */
    const PLACE_NAME_FIELD  = 'place.name';

    /** статус модерации места для события */
    const PLACE_MODERATION_STATUS_FIELD = 'place.moderationStatus';


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
        return [
            $filterFactory->getTermFilter([self::IS_REMOVED => false]),
            $filterFactory->getTermFilter([self::PLACE_IS_REMOVED => false])
        ];
    }

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
            // по типу
            self::TYPE_NAME_FIELD,
            self::TYPE_NAME_NGRAM_FIELD,
            self::TYPE_NAME_TRANSLIT_FIELD,
            self::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
            // по интересам
            self::TAG_NAME_FIELD,
            self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
            self::TAG_NAME_TRANSLIT_NGRAM_FIELD,
            // по описанию события
            self::DESCRIPTION_FIELD,
        ];
    }
}