<?php
/**
 * Базовый абстрактный класс мапперов поиска
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Constants\Visible;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class AbstractSearchMapping
{
    /** Идентификатор записи документа в коллекции */
    const IDENTIFIER_FIELD = 'id';

    /** Индекс еластика по умолчанию */
    const DEFAULT_INDEX = 'russianplace';

    /**
     * @const string поле ID автора сущности
     */
    const AUTHOR_ID_FIELD = 'author.id';

    /**
     * @const array массив id друзей
     */
    const AUTHOR_FRIENDS_FIELD = 'author.friendList';

    /** Поле имени пользователя */
    const NAME_FIELD = 'name'; // полное совпадение имени по русски
    const NAME_NGRAM_FIELD = 'name._nameNgram'; // частичное совпадение имени от 3-х сивмолов по русски
    const NAME_TRANSLIT_FIELD = 'name._translit'; // полное совпадение имени в транслите
    const NAME_TRANSLIT_NGRAM_FIELD = 'name._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите
    const NAME_WORDS_NAME_FIELD = 'name._wordsName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const NAME_WORDS_TRANSLIT_NAME_FIELD = 'name._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const NAME_LONG_NGRAM_FIELD = 'name._nameLongNgram';
    const NAME_TRANSLIT_LONG_NGRAM_FIELD = 'name._translitLongNgram';
    const RUS_TRANSLITERATE_NAME = 'rusName';
    const NAME_EXACT_FIELD = 'name._exactName';
    const NAME_EXACT_PREFIX_FIELD = 'name._exactPrefixName';

    const NAME_PREFIX_FIELD = 'name._prefix';
    const NAME_PREFIX_TRANSLIT_FIELD = 'name._prefixTranslit';
    const NAME_STANDARD_FIELD = 'name._standard';

    /** поле описания места */
    const DESCRIPTION_FIELD = 'description';
    /** поле описания места */
    const DESCRIPTION_TRANSLIT_FIELD = 'description._translit';
    const DESCRIPTION_EXACT_FIELD = 'description._exactDescription';
    const DESCRIPTION_STANDARD_FIELD = 'description._standardDescription';

    const DESCRIPTION_WORDS_NAME_FIELD = 'description._wordsDescription'; // частичное совпадение имени от 3-х сивмолов в транслите
    const DESCRIPTION_WORDS_TRANSLIT_NAME_FIELD = 'description._wordsTranslitDescription'; // частичное совпадение имени от 3-х сивмолов в транслите


    /** Поле с интересами пользователя */
    const TAG_FIELD = 'tags';
    const TAGS_ID_FIELD = 'tags.id';
    const TAG_NAME_FIELD = 'tags.name';

    const TAG_NAME_NGRAM_FIELD = 'tags.name._nameNgram';
    const TAG_NAME_TRANSLIT_FIELD = 'tags.name._translit';
    const TAG_NAME_TRANSLIT_NGRAM_FIELD = 'tags.name._translitNgram';
    const TAG_WORDS_FIELD = 'tags.name._wordsName';
    const TAG_WORDS_TRANSLIT_FIELD = 'tags.name._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите

    const TAG_PREFIX_FIELD = 'tags.name._prefix';
    const TAG_PREFIX_TRANSLIT_FIELD = 'tags.name._prefixTranslit';
    const TAG_STANDARD_FIELD = 'tags.name._standard';
    const TAG_EXACT_FIELD = 'tags.name._exactName';

    /** Поле идентификатора города */
    const LOCATION_FIELD = 'location';
    const LOCATION_CITY_ID_FIELD = 'location.city.id';

    const LOCATION_POINT_FIELD = 'location.point';

    /** Город местоположения */

    const LOCATION_CITY_FIELD = 'location.city';

    const LOCATION_CITY_NAME_FIELD = 'location.city.name';
    const LOCATION_CITY_NAME_TRANSLIT_FIELD = 'location.city.name._translit';
    const LOCATION_CITY_NAME_PREFIX_FIELD = 'location.city.name._prefix';
    const LOCATION_CITY_NAME_PREFIX_TRANSLIT_FIELD = 'location.city.name._prefixTranslit';
    const LOCATION_CITY_NAME_STANDARD_FIELD = 'location.city.name._standard';

    const LOCATION_CITY_WORDS_FIELD = 'location.city.name._wordsName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const LOCATION_CITY_WORDS_TRANSLIT_FIELD = 'location.city.name._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите

    const LOCATION_CITY_INTERNATIONAL_NAME_FIELD = 'location.city.internationalName';

    /** Страна местоположения */
    const LOCATION_COUNTRY_ID_FIELD = 'location.country.id';
    const LOCATION_COUNTRY_NAME_FIELD = 'location.country.name';
    const LOCATION_COUNTRY_NAME_TRANSLIT_FIELD = 'location.country._translit';
    const LOCATION_COUNTRY_NAME_PREFIX_FIELD = 'location.country._prefix';
    const LOCATION_COUNTRY_INTERNATIONAL_NAME_FIELD = 'location.country.internationalName';

    /** Статус модерации */
    const MODERATION_STATUS_FIELD = 'moderationStatus';
    const VISIBLE_FIELD = 'visible';

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    abstract public static function getMultiMatchQuerySearchFields();

    /**
     * Получаем поля для поиска
     * буквосочетаний nGram
     *
     * @return array
     */
    public static function getMultiMatchNgramQuerySearchFields()
    {
        return [];
    }

    /**
     * Собираем фильтр для поиска
     *
     * @param FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    abstract public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null);

    /**
     * Собираем фильтр для маркеров
     *
     * @param FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    abstract public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null);

    /**
     * Статический класс получения условий подсветки при поиске
     * @return array
     */
    public static function getHighlightConditions()
    {
        return [];
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
        return [];
    }

    /**
     * Метод собирает условие построенные для глобального поиска
     * обязательно не должно попадать в выборку
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSearchConditionQueryMustNot(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        return [];
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
        return [];
    }

    /**
     * Собираем фильтр для поиска c isFlat параметров
     *
     * @param FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string $type Колекция для поиска
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getFlatMatchSearchFilter(FilterFactoryInterface $filterFactory, $type, $userId = null)
    {
        return [];
    }

    /**
     * Формируем условия показа сущностей
     * по флагу видимости (visible)
     *
     * @param FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return \Elastica\Filter\AbstractFilter
     */
    public static function getVisibleCondition(FilterFactoryInterface $filterFactory, $userId = null)
    {
        $visible = $filterFactory->getTermFilter([self::VISIBLE_FIELD => Visible::ALL]);

        if (!empty($userId)) {
            $visible = $filterFactory->getBoolOrFilter([
                $filterFactory->getTermFilter([self::AUTHOR_ID_FIELD => $userId]),
                $filterFactory->getTermFilter([self::VISIBLE_FIELD => Visible::ALL]),
                $filterFactory->getBoolAndFilter([
                    $filterFactory->getTermsFilter(self::AUTHOR_FRIENDS_FIELD, [$userId]),
                    $filterFactory->getTermsFilter(self::VISIBLE_FIELD, [Visible::FRIEND]),
                ]),
                $filterFactory->getBoolAndFilter([
                    $filterFactory->getNotFilter(
                        $filterFactory->getTermsFilter(self::AUTHOR_FRIENDS_FIELD, [$userId])
                    ),
                    $filterFactory->getTermsFilter(self::VISIBLE_FIELD, [Visible::NOT_FRIEND]),
                ]),
            ]);
        }

        return $visible;
    }

    /**
     * Формируем условия показа сущностей
     * по флагу модерации (moderate)
     *
     * @param FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return \Elastica\Filter\AbstractFilter
     */
    public static function getModerateCondition(FilterFactoryInterface $filterFactory, $userId = null)
    {
        $moderate = $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::OK]);

        if (!empty($userId)) {
            $moderate = $filterFactory->getBoolOrFilter([
                $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::OK]),
                $filterFactory->getBoolAndFilter([
                    $filterFactory->getTermFilter([self::AUTHOR_ID_FIELD => $userId]),
                    $filterFactory->getTermsFilter(self::MODERATION_STATUS_FIELD, [
                        ModerationStatus::DIRTY,
                        ModerationStatus::REJECTED,
                        ModerationStatus::RESTORED,
                    ]),
                ]),
            ]);
        }

        return $moderate;
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
        return [];
    }

}