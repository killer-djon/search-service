<?php
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class PlaceSearchMapping extends AbstractSearchMapping
{

    /** Контекст поиска */
    const CONTEXT = 'places';

    const PLACE_ID_FIELD = 'id';

    const AUTHOR_ID_FIELD = 'author.id';


    /** Тип места */
    const TYPE_FIELD        = 'type';
    const TYPE_ID_FIELD     = 'type.id';
    const TYPE_NAME_FIELD   = 'type.name';
    const TYPE_NAME_NGRAM_FIELD   = 'type._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD   = 'type._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD   = 'type._translitNgram';
    const TYPE_WORDS_FIELD  = 'type._wordsName';


    const ADDRESS_FIELD             = 'address';
    const ADDRESS_TRANSLIT_FIELD    = 'address._translit';
    const ADDRESS_PREFIX_FIELD      = 'address._prefix';
    const ADDRESS_NGRAM_FIELD       = 'address._ngram';

    /** Дополнительные поля */
    const IS_RUSSIAN_FIELD  = 'isRussian';
    const DISCOUNT_FIELD    = 'discount';
    const BONUS_FIELD       = 'bonus';
    const REMOVED_FIELD     = 'isRemoved';

    /** Статус модерации */
    const MODERATION_STATUS_FIELD   = 'moderationStatus';
    const VISIBLE_FIELD = 'visible';

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
            // поля с вариациями названия
            self::NAME_FIELD,
            self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_FIELD,
            self::NAME_TRANSLIT_NGRAM_FIELD,
            // поля с вариациями типа места
            self::TYPE_NAME_FIELD,
            self::TYPE_NAME_NGRAM_FIELD,
            self::TYPE_NAME_TRANSLIT_FIELD,
            self::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
            // поля с вариациями названия тегов
            self::TAG_NAME_FIELD,
            self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
            self::TAG_NAME_TRANSLIT_NGRAM_FIELD,
            //поле описания места
            self::DESCRIPTION_FIELD,
            // поля с названием города проживания
            self::LOCATION_CITY_NAME_FIELD,
            self::LOCATION_CITY_INTERNATIONAL_NAME_FIELD,
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
        return array_merge(self::getMatchSearchFilter($filterFactory, $userId), [
            $filterFactory->getTermFilter([self::IS_RUSSIAN_FIELD => false])
        ]);
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
            $filterFactory->getNotFilter(
                $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::DELETED])
            )
        ];
    }
}