<?php
namespace RP\SearchBundle\Services\Mapping;

abstract class PeopleSearchMapping
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

    const TAGS_NAME_FIELD = 'tags.tagname';

    const TAGS_ID_FIELD = 'tags.id';

    const TAGS_IS_SELECTED_FIELD = 'tags.isSelected';

    /** Поле с интересами пользователя */
    const TAG_LIST_FIELD = 'tags';
    /** Поле ИД интереса */
    const TAG_LIST_ID_FIELD = 'id';
    /** Поле названия интереса */
    const TAG_LIST_NAME_FIELD = 'name';

    /** "Могу помочь" */
    const HELP_OFFERS_LIST_FIELD = 'helpOffers';

    const HELP_OFFERS_ID_FIELD = 'helpOffers.id';

    const HELP_OFFERS_NAME_FIELD = 'helpOffers.name';

    const HELP_OFFERS_NAME_TRANSLIT_FIELD = 'helpOffers._translit';

    const HELP_OFFERS_NAME_NGRAM_FIELD = 'helpOffers._ngram';

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

}
