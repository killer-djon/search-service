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

    /** Тип места */
    const TYPE_FIELD = 'type';
    const TYPE_ID_FIELD = 'type.id';
    const TYPE_NAME_FIELD = 'type.name';
    const TYPE_NAME_NGRAM_FIELD = 'type.name._nameNgram';
    const TYPE_NAME_TRANSLIT_FIELD = 'type.name._translit';
    const TYPE_NAME_TRANSLIT_NGRAM_FIELD = 'type.name._translitNgram';
    const TYPE_WORDS_FIELD = 'type.name._wordsName';
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
            self::DESCRIPTION_FIELD,
            self::DESCRIPTION_TRANSLIT_FIELD,
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
            self::DESCRIPTION_WORDS_TRANSLIT_NAME_FIELD,
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
            $filterFactory->getTermFilter([self::DISCOUNT_FIELD => 0]),
            $filterFactory->getNotFilter(
                $filterFactory->getExistsFilter(self::BONUS_FIELD)
            ),
            AbstractSearchMapping::getVisibleCondition($filterFactory, $userId),
            // НЕ скидочные места показываются даже если ещё не прошли модерацию
            // AbstractSearchMapping::getModerateCondition($filterFactory, $userId),
            $filterFactory->getNotFilter(
                $filterFactory->getTermsFilter(self::MODERATION_STATUS_FIELD, [
                    ModerationStatus::REJECTED,
                    ModerationStatus::DELETED,
                ])
            ),
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
        return self::getMarkersSearchFilter($filterFactory, $userId);
    }


    /**
     * Собираем фильтр для поиска c isFlat параметров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string $type Колекция для поиска
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getFlatMatchSearchFilter(FilterFactoryInterface $filterFactory, $type, $userId = null)
    {
        return array_merge(
            self::getMarkersSearchFilter($filterFactory, $userId),
            [$filterFactory->getTypeFilter($type)]
        );
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
        $allFields = array_merge(
            self::getMultiMatchQuerySearchFields(),
            self::getMultiSubMatchQuerySearchFields()
        );

        $queryMatchPhrase = [];
        foreach (self::getMorphologyQuerySearchFields() as $field) {
            $queryMatchPhrase[] = $conditionFactory->getMatchPhraseQuery($field, $queryString);
        }

        return [
            $conditionFactory->getDisMaxQuery($queryMatchPhrase),
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
            //$prefixWildCard[] = $conditionFactory->getWildCardQuery($field, "{$queryString}*");
            $prefixWildCardByName[] = $conditionFactory->getPrefixQuery($field, $queryString, 0.5);
        }

        foreach (self::getMultiSubMatchQuerySearchFields() as $field) {
            //$prefixWildCard[] = $conditionFactory->getWildCardQuery($field, "{$queryString}*");
            $prefixWildCardByTags[] = $conditionFactory->getPrefixQuery($field, $queryString, 0.2);
        }

        return [
            $conditionFactory->getDisMaxQuery(array_merge([
                $conditionFactory->getMultiMatchQuery()
                    ->setFields(array_merge(
                        self::getMultiMatchQuerySearchFields(),
                        self::getMultiSubMatchQuerySearchFields()
                    ))
                    ->setQuery($queryString)
                    ->setOperator(MultiMatch::OPERATOR_OR)
                    ->setType(MultiMatch::TYPE_CROSS_FIELDS),
            ], $prefixWildCardByTags, $prefixWildCardByName, [
                    $conditionFactory->getFieldQuery(self::getMorphologyQuerySearchFields(), $queryString, true, 0.5),
                    $conditionFactory->getMatchPhrasePrefixQuery(self::DESCRIPTION_WORDS_NAME_FIELD, $queryString),
                    $conditionFactory->getMatchPhrasePrefixQuery(self::DESCRIPTION_WORDS_TRANSLIT_NAME_FIELD, $queryString),
                ]
            )),
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
            '*'                     => [
                'term_vector' => 'with_positions_offsets',
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
            $conditionFactory->getMatchPhrasePrefixQuery(self::NAME_EXACT_FIELD, $queryString),
            //$conditionFactory->getMatchPhrasePrefixQuery(self::DESCRIPTION_EXACT_FIELD, $queryString),
            $conditionFactory->getTermQuery('_type', self::CONTEXT),
        ];
    }
}