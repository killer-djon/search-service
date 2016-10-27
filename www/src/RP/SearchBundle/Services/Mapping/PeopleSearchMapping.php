<?php
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class PeopleSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'people';

    /** Поле имени пользователя */
    const NAME_FIELD = 'name'; // полное совпадение имени по русски
    const NAME_NGRAM_FIELD = 'name._nameNgram'; // частичное совпадение имени от 3-х сивмолов по русски
    const NAME_TRANSLIT_FIELD = 'name._translit'; // полное совпадение имени в транслите
    const NAME_TRANSLIT_NGRAM_FIELD = 'name._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите

    /** Поле фамилии пользователя */
    const SURNAME_FIELD = 'surname'; // полное совпадение фамилии по русски
    const SURNAME_NGRAM_FIELD = 'surname._surnameNgram'; // частичное совпадение фамилии от 3-х сивмолов по русски
    const SURNAME_TRANSLIT_FIELD = 'surname._translit'; // полное совпадение имени в транслите
    const SURNAME_TRANSLIT_NGRAM_FIELD = 'surname._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите

    /**
     * Поле имени пользователя для префиксного поиска
     *
     * @deprecated
     */
    const PREFIX_NAME_FIELD = 'name._prefix';
    /**
     * Поле фамилии пользователя для префиксного поиска
     *
     * @deprecated
     */
    const PREFIX_SURNAME_FIELD = 'surname._prefix';

    /** Поле аватара пользователя */
    const AVATAR_FIELD = 'Avatar';

    /** Поле с интересами пользователя */
    const TAG_FIELD         = 'tags';
    const TAGS_ID_FIELD      = 'tags.id';
    const TAG_NAME_FIELD    = 'tags.tagname';
    const TAG_NAME_NGRAM_FIELD    = 'tags._nameNgram';
    const TAG_NAME_TRANSLIT_FIELD    = 'tags._translit';
    const TAG_NAME_TRANSLIT_NGRAM_FIELD    = 'tags._translitNgram';
    const TAG_WORDS_FIELD    = 'tags._wordsName';

    /** "Могу помочь" */
    const HELP_OFFERS_LIST_FIELD = 'helpOffers';

    const HELP_OFFERS_ID_FIELD = 'helpOffers.id';

    const HELP_OFFERS_NAME_FIELD = 'helpOffers.name';

    const HELP_OFFERS_NAME_TRANSLIT_FIELD = 'helpOffers._translit';

    const HELP_OFFERS_NAME_NGRAM_FIELD = 'helpOffers._nameNgram';

    const HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD = 'helpOffers._translitNgram';

    const HELP_OFFERS_WORDS_NAME_FIELD = 'helpOffers._wordsName';

    const HELP_OFFERS_NAME_PREFIX_FIELD = 'helpOffers._prefix';

    /** Поле точки местоположения пользователя */
    const LOCATION_POINT_FIELD = 'location.point';

    /** Поле широты в точке местоположения */
    const LOCATION_POINT_LAT_FIELD = 'lat';
    /** Поле долготы в точке местоположения */
    const LOCATION_POINT_LON_FIELD = 'lon';

    /** Поле идентификатора города */
    const LOCATION_CITY_ID_FIELD = 'location.city.id';

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

    /** Поле родного города пользователя */
    const HOMECITY_FIELD = 'homecity';
    const RESIDENCE_CITY_FIELD = 'residenceCity';

    /** Поле пола */
    const GENDER_FIELD = 'Gender';

    /** Поле пола */
    const PAGE_ADDRESS_FIELD = 'PageAddress';

    /** Поле дня рождения */
    const BIRTHDAY_FIELD = 'Birthday';

    /** Поле онлайн-статуса пользователя */
    const IS_ONLINE_FIELD = 'IsOnline';

    /** Поле со списком знания языков */
    const LANG_LIST_FIELD = 'LangList';

    /** Название сферы деятельности пользователя */
    const ACTIVITY_SPHERE_NAME_FIELD = 'activitySphere.name';

    /**
     * Сфера деятельности пользователя
     *
     * @deprecated
     */
    const ACTIVITY_SPHERE_LIST_FIELD = 'activitySphere';

    /** Поле со списком друзей */
    const FRIEND_LIST_FIELD = 'friendList';

    /** Параметр ИД пользователя для представления в автокомплите */
    const AUTOCOMPLETE_ID_PARAM = 'id';

    /** Параметр имени пользователя для представления в автокомплите */
    const AUTOCOMPLETE_NAME_PARAM = 'name';

    /** Параметр онлайн-статуса пользователя для прдеставления в автокомплите */
    const AUTOCOMPLETE_IS_ONLINE_PARAM = 'isOnline';

    /** Параметр места пользователя для представления в автокомплите */
    const AUTOCOMPLETE_PLACE_PARAM = 'place';

    /** Параметр юзерпика пользователя для представления в автокомплите */
    const AUTOCOMPLETE_USERPIC_PARAM = 'userpic';

    /** Поле образовательные учереждения */
    const DEGREES_FIELD = 'Degrees';

    /** Поле идентификатор образовательного учереждения пользователя */
    const DEGREE_INSTITUTE_ID_FIELD = 'Id';

    /** Поле год окончания образовательного учереждения пользователя */
    const GRADUATION_YEAR_FIELD = 'GraduationYear';

    /** Поле видимости местоположения пользователя */
    const LOCATION_VISIBILITY_FIELD = 'location.visibility';

    /** Поле флага удаления пользователя, если пользователь был удален */
    const USER_REMOVED_FIELD = 'isRemoved';

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
            // вариации поля фамилии
            self::SURNAME_FIELD,
            self::SURNAME_NGRAM_FIELD,
            self::SURNAME_TRANSLIT_FIELD,
            self::SURNAME_TRANSLIT_NGRAM_FIELD,
            // поле интересов и занятий
            self::TAG_NAME_FIELD,
            self::ACTIVITY_SPHERE_NAME_FIELD,
            // поля с названием города проживания
            self::LOCATION_CITY_NAME_FIELD,
            self::LOCATION_CITY_INTERNATIONAL_NAME_FIELD

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
