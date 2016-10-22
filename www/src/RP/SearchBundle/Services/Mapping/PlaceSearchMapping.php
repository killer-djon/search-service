<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 13.10.16
 * Time: 18:18
 */

namespace RP\SearchBundle\Services\Mapping;

abstract class PlaceSearchMapping
{
	const PLACE_ID_FIELD = 'id';
    /** Контекст поиска */
    const CONTEXT = 'places';

    const AUTHOR_ID_FIELD = 'author.id';

    /** Поле названия места */
    const NAME_FIELD          = 'name';
    const NAME_TRANSLIT_FIELD = 'name._translit';
    const NAME_NGRAM_FIELD    = 'name._nameNgram';
    const NAME_TRANSLIT_NGRAM_FIELD   = 'name._translitNgram';
    const NAME_WORDS_FIELD  = 'name._wordsName';

	/** поле описания места */
    const DESCRIPTION_FIELD = 'description';
    /** эти вариации полей пока не используем */
    const DESCRIPTION_TRANSLIT_FIELD = 'description._translit';
    const DESCRIPTION_NGRAM_FIELD = 'description._ngram';
    const DESCRIPTION_PREFIX_FIELD = 'description._prefix';

    /** Тип места */
    const TYPE_FIELD        = 'type';
    const TYPE_ID_FIELD     = 'type.id';
    const TYPE_NAME_FIELD   = 'type.name';
    const TYPE_NAME_NGRAM_FIELD   = 'type._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD   = 'type._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD   = 'type._translitNgram';
    const TYPE_WORDS_FIELD  = 'type._wordsName';

    /**
     * Теги
     */
    const TAG_FIELD         = 'tags';
    const TAG_ID_FIELD      = 'tags.id';
    const TAG_NAME_FIELD    = 'tags.tagname';
    const TAG_NAME_NGRAM_FIELD    = 'tags._nameNgram';
    const TAG_NAME_TRANSLIT_FIELD    = 'tags._translit';
    const TAG_NAME_TRANSLIT_NGRAM_FIELD    = 'tags._translitNgram';
    const TAG_WORDS_FIELD    = 'tags._wordsName';

    /** Адрес */
    const LOCATION_ADDRESS_FIELD    = 'location.address';

    const ADDRESS_FIELD             = 'address';
    const ADDRESS_TRANSLIT_FIELD    = 'address._translit';
    const ADDRESS_PREFIX_FIELD      = 'address._prefix';
    const ADDRESS_NGRAM_FIELD       = 'address._ngram';

    /** Геокоординаты местоположения */
    const LOCATION_POINT_FIELD      = 'location.point';
    const LOCATION_POINT_LAT_FIELD  = 'lat';
    const LOCATION_POINT_LON_FIELD  = 'lon';

    /** Город местоположения */
    const LOCATION_CITY_FIELD       = 'location.city';
    const LOCATION_CITY_ID_FIELD    = 'location.city.id';
    const LOCATION_CITY_NAME_FIELD  = 'location.city.name';
    const LOCATION_CITY_NAME_TRANSLIT_FIELD   = 'location.city._translit';
    const LOCATION_CITY_NAME_PREFIX_FIELD   = 'location.city._prefix';
    const LOCATION_CITY_INTERNATIONAL_NAME_FIELD   = 'location.city.internationalName';

    /** Страна местоположения */
    const LOCATION_COUNTRY_ID_FIELD     = 'location.country.id';
    const LOCATION_COUNTRY_NAME_FIELD   = 'location.country.name';
    const LOCATION_COUNTRY_NAME_TRANSLIT_FIELD   = 'location.country._translit';
    const LOCATION_COUNTRY_NAME_PREFIX_FIELD   = 'location.country._prefix';
    const LOCATION_COUNTRY_INTERNATIONAL_NAME_FIELD   = 'location.country.internationalName';

    /** Дополнительные поля */
    const IS_RUSSIAN_FIELD  = 'isRussian';
    const DISCOUNT_FIELD    = 'discount';
    const BONUS_FIELD       = 'bonus';
    const REMOVED_FIELD     = 'isRemoved';

    /** Статус модерации */
    const MODERATION_STATUS_FIELD   = 'moderationStatus';
    const VISIBLE_FIELD = 'visible';
}