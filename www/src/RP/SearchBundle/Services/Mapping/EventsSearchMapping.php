<?php
/**
 * Маппинг класса поиска событий
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;

class EventsSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'events';

    const EVENT_ID_FIELD = 'id';

    /** Тип события */
    const TYPE_FIELD = 'type';
    const TYPE_ID_FIELD = 'type.id';
    const TYPE_NAME_FIELD = 'type.name';
    const TYPE_NAME_NGRAM_FIELD = 'type.name._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD = 'type.name._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD = 'type.name._translitNgram';
    const TYPE_WORDS_FIELD    = 'type.name._wordsName';
    const TYPE_WORDS_TRANSLIT_FIELD = 'type.name._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите

    /** Помечено ли на уделание событие */
    const IS_REMOVED = 'isRemoved';

    /** Помечено ли на уделание событие */
    const PLACE_IS_REMOVED = 'place.isRemoved';

    /** Дополнительные поля */
    const PLACE_ID_FIELD  = 'place.id';

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
            //self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_FIELD,

            self::DESCRIPTION_FIELD,

            self::DESCRIPTION_TRANSLIT_FIELD
        ];
    }

    public static function getPrefixedQuerySearchFields()
    {
        return [
            self::NAME_PREFIX_FIELD,
            self::NAME_PREFIX_TRANSLIT_FIELD,

            self::TAG_PREFIX_FIELD,
            self::TAG_PREFIX_TRANSLIT_FIELD,
        ];
    }

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiSubMatchQuerySearchFields(){
        return [
            //self::NAME_TRANSLIT_NGRAM_FIELD,
            // по типу
            self::TYPE_NAME_FIELD,
            //self::TYPE_NAME_NGRAM_FIELD,
            self::TYPE_NAME_TRANSLIT_FIELD,
            //self::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
            // по интересам
            self::TAG_NAME_FIELD,
            //self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
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
                ->setDefaultOperator(MultiMatch::OPERATOR_AND)
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
        $should = [];
        $allFields = array_merge(
            self::getMultiSubMatchQuerySearchFields(),
            self::getMultiMatchQuerySearchFields()
        );

        foreach (self::getMultiMatchQuerySearchFields() as $field) {
            $should[] = $conditionFactory->getMatchPhrasePrefixQuery($field, $queryString);
        }

        return [
            $conditionFactory->getMultiMatchQuery()
                ->setFields(array_merge(self::getMultiSubMatchQuerySearchFields(), self::getMultiMatchQuerySearchFields()))
                ->setQuery($queryString)
                ->setOperator(MultiMatch::OPERATOR_OR)
                ->setType(MultiMatch::TYPE_CROSS_FIELDS),
            $conditionFactory->getBoolQuery([], [
                $conditionFactory->getFieldQuery(
                    array_merge(self::getMultiSubMatchQuerySearchFields(), self::getMultiMatchQuerySearchFields()),
                    $queryString
                ),
                $conditionFactory->getBoolQuery([], $should, [])
            ], [])
        ];
    }

    /**
     * Статический класс получения условий подсветки при поиске
     * @return array
     */
    public static function getHighlightConditions()
    {
        $highlight[self::DESCRIPTION_FIELD] = [
            'term_vector' => 'with_positions_offsets'
        ];

        return $highlight;
    }
}