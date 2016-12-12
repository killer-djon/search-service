<?php
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;

abstract class PlaceSearchMapping extends AbstractSearchMapping
{

    /** Контекст поиска */
    const CONTEXT = 'places';

    const PLACE_ID_FIELD = 'id';

    const AUTHOR_ID_FIELD = 'author.id';

    /** Тип места */
    const TYPE_FIELD = 'type';
    const TYPE_ID_FIELD = 'type.id';
    const TYPE_NAME_FIELD = 'type.name';
    const TYPE_NAME_NGRAM_FIELD = 'type.name._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD = 'type.name._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD = 'type.name._translitNgram';
    const TYPE_WORDS_FIELD    = 'type.name._wordsName';
    const TYPE_WORDS_TRANSLIT_FIELD = 'type.name._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите

    const TYPE_PREFIX_FIELD = 'type.name._prefix';
    const TYPE_PREFIX_TRANSLIT_FIELD = 'type.name._prefixTranslit';
    const TYPE_STANDARD_FIELD = 'type.name._standard';

    const ADDRESS_FIELD = 'address';
    const ADDRESS_TRANSLIT_FIELD = 'address._translit';
    const ADDRESS_PREFIX_FIELD = 'address._prefix';
    const ADDRESS_NGRAM_FIELD = 'address._ngram';

    /** Дополнительные поля */
    const IS_RUSSIAN_FIELD = 'isRussian';
    const DISCOUNT_FIELD = 'discount';
    const BONUS_FIELD = 'bonus';
    const REMOVED_FIELD = 'isRemoved';

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
    public static function getMultiMatchQuerySearchFields()
    {
        return [
            // поля с вариациями названия
            self::NAME_FIELD,
            //self::NAME_NGRAM_FIELD,
            self::NAME_TRANSLIT_FIELD,
            //self::NAME_TRANSLIT_NGRAM_FIELD,
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
            // поля с вариациями типа места
            self::TYPE_NAME_FIELD,
            //self::TYPE_NAME_NGRAM_FIELD,
            self::TYPE_NAME_TRANSLIT_FIELD,
            //self::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
            // поля с вариациями названия тегов
            self::TAG_NAME_FIELD,
            //self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_FIELD,
            //self::TAG_NAME_TRANSLIT_NGRAM_FIELD,
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

            self::TYPE_NAME_NGRAM_FIELD,
            self::TYPE_NAME_TRANSLIT_NGRAM_FIELD,

            self::TAG_NAME_NGRAM_FIELD,
            self::TAG_NAME_TRANSLIT_NGRAM_FIELD,

            self::DESCRIPTION_FIELD,
            self::DESCRIPTION_TRANSLIT_FIELD,
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

            self::TYPE_PREFIX_FIELD,
            self::TYPE_PREFIX_TRANSLIT_FIELD,

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
            self::NAME_WORDS_NAME_FIELD,
            self::NAME_WORDS_TRANSLIT_NAME_FIELD,

            self::TAG_WORDS_FIELD,
            self::TAG_WORDS_TRANSLIT_FIELD,

            self::TYPE_WORDS_FIELD,
            self::TYPE_WORDS_TRANSLIT_FIELD,

            self::DESCRIPTION_WORDS_NAME_FIELD,
            self::DESCRIPTION_WORDS_TRANSLIT_NAME_FIELD
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
            $filterFactory->getTermFilter([self::IS_RUSSIAN_FIELD => false]),
            $filterFactory->getNotFilter(
                $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::DELETED])
            ),
            $filterFactory->getBoolOrFilter([
                $filterFactory->getTermFilter([self::DISCOUNT_FIELD => 0]),
                $filterFactory->getNotFilter(
                    $filterFactory->getExistsFilter(self::BONUS_FIELD)
                ),
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
            $filterFactory->getNotFilter(
                $filterFactory->getTermFilter([self::MODERATION_STATUS_FIELD => ModerationStatus::DELETED])
            ),
            $filterFactory->getTermFilter([self::DISCOUNT_FIELD => 0]),
            $filterFactory->getNotFilter(
                $filterFactory->getExistsFilter(self::BONUS_FIELD)
            ),
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
        $fieldQuery[] = $conditionFactory
            ->getFieldQuery(array_merge(
                self::getMultiMatchQuerySearchFields(),
                self::getMultiSubMatchQuerySearchFields()
            ), $queryString)
            ->setDefaultOperator(MultiMatch::OPERATOR_AND);
        return array_merge($fieldQuery, [
            $conditionFactory->getBoolQuery([], [
                $conditionFactory->getFieldQuery('description._exactDescription', $queryString)
            ], [])
        ]);
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
        /*$should = [];
        $allSearchFields = array_merge(
            self::getMultiSubMatchQuerySearchFields(),
            self::getMultiMatchQuerySearchFields()
        );

        foreach ($allSearchFields as $field) {
            $should[] = $conditionFactory->getMatchPhrasePrefixQuery($field, $queryString);
        }

        return [
            $conditionFactory->getMultiMatchQuery()
                             ->setFields(self::getMultiMatchQuerySearchFields())
                             ->setQuery($queryString),
            $conditionFactory->getBoolQuery([], $should, []),
        ];*/
        $prefixWildCard = [];
        $subMorphologyField = [];

        foreach (self::getMorphologyQuerySearchFields() as $field) {
            $subMorphologyField[] = $conditionFactory->getFieldQuery($field, $queryString);
        }

        foreach (self::getPrefixedQuerySearchFields() as $field) {
            $prefixWildCard[] = $conditionFactory->getWildCardQuery($field, "{$queryString}*");
        }

        return [
            $conditionFactory->getFieldQuery(self::getMultiMatchQuerySearchFields(), $queryString),
            $conditionFactory->getBoolQuery([], [
                $conditionFactory->getFieldQuery(self::getMultiSubMatchQuerySearchFields(), $queryString),
                $conditionFactory->getBoolQuery([], [
                    $conditionFactory->getFieldQuery(self::getMorphologyQuerySearchFields(), $queryString)
                ], [])
            ], [])
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
            self::DESCRIPTION_FIELD => [
                'term_vector'   => 'with_positions_offsets',
                'no_match_size' => 150,
                'fragment_size' => 150,
            ],
            self::TAG_NAME_FIELD    => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::TYPE_NAME_FIELD   => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
        ];

        return $highlight;
    }
}