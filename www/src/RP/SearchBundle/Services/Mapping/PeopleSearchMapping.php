<?php

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;

abstract class PeopleSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'people';


    /** ID системного пользователя RussianPlace */
    const RP_USER_ID = '4092';

    /** Поле фамилии пользователя */
    const SURNAME_FIELD = 'surname'; // полное совпадение фамилии по русски
    const SURNAME_NGRAM_FIELD = 'surname._surnameNgram'; // частичное совпадение фамилии от 3-х сивмолов по русски
    const SURNAME_LONG_NGRAM_FIELD = 'surname._nameLongNgram';
    const SURNAME_TRANSLIT_FIELD = 'surname._translit'; // полное совпадение имени в транслите
    const SURNAME_TRANSLIT_NGRAM_FIELD = 'surname._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите
    const SURNAME_TRANSLIT_LONG_NGRAM_FIELD = 'surname._translitLongNgram';

    const SURNAME_WORDS_NAME_FIELD = 'surname._wordsName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const SURNAME_WORDS_TRANSLIT_NAME_FIELD = 'surname._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите

    const SURNAME_PREFIX_FIELD = 'surname._prefix';
    const SURNAME_PREFIX_TRANSLIT_FIELD = 'surname._prefixTranslit';
    const SURNAME_STANDARD_FIELD = 'surname._standard';
    const SURNAME_EXACT_FIELD = 'surname._exactSurname';
    const SURNAME_EXACT_PREFIX_FIELD = 'surname._exactPrefixName';

    // Морфологический разбор поля полного имени
    const FULLNAME_MORPHOLOGY_FIELD = 'fullname';

    /** Поле аватара пользователя */
    const AVATAR_FIELD = 'Avatar';

    /** "Могу помочь" */
    const HELP_OFFERS_LIST_FIELD = 'helpOffers';

    const HELP_OFFERS_ID_FIELD = 'helpOffers.id';

    const HELP_OFFERS_NAME_FIELD = 'helpOffers.name';

    const HELP_OFFERS_NAME_TRANSLIT_FIELD = 'helpOffers.name._translit';

    const HELP_OFFERS_NAME_NGRAM_FIELD = 'helpOffers.name._nameNgram';

    const HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD = 'helpOffers.name._translitNgram';

    const HELP_OFFERS_WORDS_NAME_FIELD = 'helpOffers.name._wordsName';
    const HELP_OFFERS_WORDS_NAME_TRANSLIT_FIELD = 'helpOffers.name._wordsTranslitName';

    const HELP_OFFERS_NAME_PREFIX_FIELD = 'helpOffers.name._prefix';
    const HELP_OFFERS_NAME_PREFIX_TRANSLIT_FIELD = 'helpOffers.name._prefixTranslit';

    /** Поле точки местоположения пользователя */
    const LOCATION_POINT_FIELD = 'location.point';

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
    const ACTIVITY_SPHERE_NAME_NGRAM_FIELD = 'activitySphere.name._nameNgram';

    const ACTIVITY_SPHERE_NAME_TRANSLIT_FIELD = 'activitySphere.name._translit';
    const ACTIVITY_SPHERE_NAME_TRANSLIT_NGRAM_FIELD = 'activitySphere.name._translitNgram';
    const ACTIVITY_SPHERE_WORDS_NAME_FIELD = 'activitySphere.name._wordsName';
    const ACTIVITY_SPHERE_WORDS_TRANSLIT_NAME_FIELD = 'activitySphere.name._wordsName';
    const ACTIVITY_SPHERE_EXACT_NAME_FIELD = 'activitySphere.name._exactName';

    const ACTIVITY_SPHERE_PREFIX_NAME_FIELD = 'activitySphere.name._prefix';
    const ACTIVITY_SPHERE_PREFIX_TRANSLIT_NAME_FIELD = 'activitySphere.name._prefixTranslit';
    const ACTIVITY_SPHERE_STANDARD_NAME_FIELD = 'activitySphere.name._standard';

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
     * Рабор фильтров просмотра друзей
     * личные друзья профиля
     *
     * @const string FRIENDS_FILTER
     */
    const FRIENDS_FILTER = 'friends';

    /**
     * Рабор фильтров просмотра друзей
     * общие с профилем друзья
     *
     * @const string COMMON_FRIENDS_FILTER
     */
    const COMMON_FRIENDS_FILTER = 'commonFriends';

    /**
     * Настройка приватности пользователя
     * Показывать пользователя на карте или нет
     *
     * @const string values: all or none
     */
    const SETTINGS_PRIVACY_VIEW_GEO_POSITION = 'settings.privacy.viewGeoposition';

    /**
     * Константы для зхначений настроек приватности
     *
     * @const string
     */
    const SETTINGS_YES = 'all';
    const SETTINGS_NO = 'none';

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
            //self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_FIELD,
            //self::NAME_TRANSLIT_NGRAM_FIELD,
            // вариации поля фамилии
            self::SURNAME_FIELD,
            //self::SURNAME_NGRAM_FIELD,
            self::SURNAME_TRANSLIT_FIELD,
            //self::SURNAME_TRANSLIT_NGRAM_FIELD,
        ];
    }

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiSubMatchQuerySearchFields()
    {
        return [
            self::TAG_NAME_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
            // сфера деятельности
            self::ACTIVITY_SPHERE_NAME_FIELD,
            self::ACTIVITY_SPHERE_NAME_TRANSLIT_FIELD,
        ];
    }

    /**
     * Получаем поля для поиска
     * буквосочетаний nGram
     *
     * @return array
     */
    public static function getMultiMatchNgramQuerySearchFields()
    {
        return [
            self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_NGRAM_FIELD,
            self::SURNAME_NGRAM_FIELD,
            self::SURNAME_TRANSLIT_NGRAM_FIELD,
            /*self::NAME_LONG_NGRAM_FIELD,
            self::NAME_TRANSLIT_LONG_NGRAM_FIELD,
            self::SURNAME_LONG_NGRAM_FIELD,
            self::SURNAME_TRANSLIT_LONG_NGRAM_FIELD,*/

            // сфера деятельности
            self::ACTIVITY_SPHERE_NAME_FIELD,

            self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
        ];
    }

    /**
     * ВОзвращаем набор полей для префиксного поиска
     *
     * @return array
     */
    public static function getPrefixedQuerySearchFields()
    {
        return [
            self::NAME_PREFIX_FIELD,
            self::NAME_PREFIX_TRANSLIT_FIELD,

            self::SURNAME_PREFIX_FIELD,
            self::SURNAME_PREFIX_TRANSLIT_FIELD,

            self::ACTIVITY_SPHERE_PREFIX_NAME_FIELD,
            self::ACTIVITY_SPHERE_PREFIX_TRANSLIT_NAME_FIELD,

            self::TAG_PREFIX_FIELD,
            self::TAG_PREFIX_TRANSLIT_FIELD,
        ];
    }

    /**
     * ВОзвращаем набор полей для префиксного поиска
     *
     * @return array
     */
    public static function getMorphologyQuerySearchFields()
    {
        return [
            /*self::NAME_WORDS_NAME_FIELD,
            self::NAME_WORDS_TRANSLIT_NAME_FIELD,

            self::SURNAME_WORDS_NAME_FIELD,
            self::SURNAME_WORDS_TRANSLIT_NAME_FIELD,*/

            self::TAG_WORDS_FIELD,
            self::TAG_WORDS_TRANSLIT_FIELD,

            self::ACTIVITY_SPHERE_WORDS_NAME_FIELD,
            self::ACTIVITY_SPHERE_WORDS_TRANSLIT_NAME_FIELD,
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
        return [
            $filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(
                    self::IDENTIFIER_FIELD,
                    self::$userProfile->getBlockedUsers()
                )
            ),
            $filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(self::FRIEND_LIST_FIELD, [$userId])
            ),
            $filterFactory->getNotFilter(
                $filterFactory->getTermFilter([self::IDENTIFIER_FIELD => $userId])
            ),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getNotFilter(
                    $filterFactory->getExistsFilter(self::SETTINGS_PRIVACY_VIEW_GEO_POSITION)
                ),
                $filterFactory->getTermFilter([self::SETTINGS_PRIVACY_VIEW_GEO_POSITION => self::SETTINGS_YES]),
            ]),
            $filterFactory->getTermFilter([self::USER_REMOVED_FIELD => false]),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getNotFilter(
                    $filterFactory->getExistsFilter(self::HELP_OFFERS_LIST_FIELD)
                ),
                $filterFactory->getScriptFilter("doc['helpOffers.id'].values.size() < 1")
            ]),
        ];
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
            $filterFactory->getTermFilter([self::USER_REMOVED_FIELD => false]),
            /*$filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(
                    self::IDENTIFIER_FIELD,
                    self::$userProfile->getBlockedUsers()
                )
            ),*/
        ];
    }

    /**
     * Метод собирает условие построенные для глобального поиска
     * обязательное условие при запросе
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSearchConditionQueryMust(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        return [
            $conditionFactory
                ->getFieldQuery(array_merge(
                    self::getMultiMatchQuerySearchFields(),
                    self::getMultiSubMatchQuerySearchFields()
                ), $queryString)
                ->setDefaultOperator(MultiMatch::OPERATOR_AND),
        ];
    }

    /**
     * Метод собирает условие построенные для глобального поиска
     * может попасть или может учитыватся при выборке
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSearchConditionQueryShould(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        $prefixWildCardByName = [];
        $prefixWildCardByTags = [];
        $subMorphologyField = [];

        $allFieldsQuery = array_merge(
            self::getMultiMatchQuerySearchFields(),
            self::getMultiSubMatchQuerySearchFields()
        );

        foreach (self::getMultiMatchQuerySearchFields() as $field) {
            $prefixWildCardByName[] = $conditionFactory->getPrefixQuery($field, $queryString, 0.5);
        }

        foreach (self::getMultiSubMatchQuerySearchFields() as $field) {
            $prefixWildCardByTags[] = $conditionFactory->getPrefixQuery($field, $queryString, 0.2);
        }

        $namePrefixedFields = [];
        foreach (self::getPrefixedQuerySearchFields() as $field) {
            $namePrefixedFields[] = $conditionFactory->getPrefixQuery($field, $queryString, 0.1);
        }

        return [
            $conditionFactory->getMultiMatchQuery()
                             ->setFields(self::getMultiMatchQuerySearchFields())
                             ->setQuery($queryString)
                             ->setOperator(MultiMatch::OPERATOR_OR)
                             ->setType(MultiMatch::TYPE_BEST_FIELDS),
            $conditionFactory->getBoolQuery([], array_merge($prefixWildCardByName, [
                $conditionFactory->getMultiMatchQuery()
                                 ->setFields(self::getMultiSubMatchQuerySearchFields())
                                 ->setQuery($queryString)
                                 ->setOperator(MultiMatch::OPERATOR_OR)
                                 ->setType(MultiMatch::TYPE_BEST_FIELDS),
                $conditionFactory->getBoolQuery([], [
                    $conditionFactory->getFieldQuery(self::getMorphologyQuerySearchFields(), $queryString, true, 0.5),
                ], []),
            ]), []),
        ];
    }

    /**
     * Статический класс получения условий подсветки при поиске
     *
     * @return array
     */
    public static function getHighlightConditions()
    {
        $highlight = [
            self::TAG_NAME_FIELD             => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::ACTIVITY_SPHERE_NAME_FIELD => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::NAME_FIELD                 => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::SURNAME_FIELD              => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
        ];

        return $highlight;
    }


    /**
     * Вспомогательный метод позволяющий
     * задавать условия для автодополнения
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSuggestQueryConditions(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        return [
            $conditionFactory->getPrefixQuery(self::NAME_EXACT_FIELD, $queryString),
            $conditionFactory->getPrefixQuery(self::SURNAME_EXACT_FIELD, $queryString),
            $conditionFactory->getTermQuery('_type', self::CONTEXT),
        ];
    }

}
