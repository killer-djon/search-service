<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 22.05.17
 * Time: 14:32
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;

abstract class PostSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'posts';

    const POST_CATEGORIES_ID = 1;

    /** Индекс еластика по умолчанию */
    const DEFAULT_INDEX = 'newsfeed';

    /** @const Поле ID стены */
    const POST_WALL_ID = 'wallId';

    /** @const Был ли опубликован пост */
    const POST_IS_POSTED = 'isPosted';

    /** @const Поле удалености поста */
    const POST_IS_REMOVED = 'isRemoved';

    /** Поле имени пользователя */
    const POST_MESSAGE_FIELD = 'message'; // полное совпадение имени по русски
    const POST_MESSAGE_NGRAM_FIELD = 'message._nameNgram'; // частичное совпадение имени от 3-х сивмолов по русски
    const POST_MESSAGE_TRANSLIT_FIELD = 'message._translit'; // полное совпадение имени в транслите
    const POST_MESSAGE_TRANSLIT_NGRAM_FIELD = 'message._translitNgram'; // частичное совпадение имени от 3-х сивмолов в транслите
    const POST_MESSAGE_WORDS_NAME_FIELD = 'message._wordsName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const POST_MESSAGE_WORDS_TRANSLIT_NAME_FIELD = 'message._wordsTranslitName'; // частичное совпадение имени от 3-х сивмолов в транслите
    const POST_MESSAGE_LONG_NGRAM_FIELD = 'message._nameLongNgram';
    const POST_MESSAGE_TRANSLIT_LONG_NGRAM_FIELD = 'message._translitLongNgram';
    const POST_MESSAGE_EXACT_FIELD = 'message._exactName';
    const POST_MESSAGE_EXACT_PREFIX_FIELD = 'message._exactPrefixName';

    /** Поля категории постов */
    const POST_CATEGORIES_FIELD = 'postCategories';
    const POST_CATEGORIES_FIELD_ID = 'postCategories.id';
    const POST_CATEGORIES_FIELD_name = 'postCategories.name';

    /** Поля городов поста */
    const POST_CITY_FIELD = 'categoriesCity';
    const POST_CITY_FIELD_ID = 'categoriesCity.id';
    const POST_CITY_FIELD_name = 'categoriesCity.name';
    const POST_CITY_INTERNATIONAL_NAME_FIELD = 'categoriesCity.internationalName';
    const POST_CITY_POINT_FIELD = 'categoriesCity.point';

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
            self::POST_MESSAGE_FIELD,
            self::POST_MESSAGE_TRANSLIT_FIELD,
            // вариации поля фамилии
            parent::TAG_NAME_FIELD,
            parent::TAG_NAME_TRANSLIT_FIELD,

            AbstractTransformer::createCompleteKey([
                self::POST_CITY_FIELD,
                parent::NAME_FIELD
            ]),
            AbstractTransformer::createCompleteKey([
                self::POST_CITY_FIELD,
                parent::NAME_TRANSLIT_FIELD
            ]),
            self::POST_CITY_INTERNATIONAL_NAME_FIELD
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
            parent::TAG_WORDS_FIELD,
            parent::TAG_WORDS_TRANSLIT_FIELD,

            self::POST_MESSAGE_WORDS_NAME_FIELD,
            self::POST_MESSAGE_WORDS_TRANSLIT_NAME_FIELD,

            AbstractTransformer::createCompleteKey([
                self::POST_CITY_FIELD,
                parent::NAME_WORDS_NAME_FIELD
            ]),
            AbstractTransformer::createCompleteKey([
                self::POST_CITY_FIELD,
                parent::NAME_WORDS_TRANSLIT_NAME_FIELD
            ])
        ];
    }

    /**
     * Для постов отдельная логика по городу
     *
     * @var string ID города
     */
    public static $_cityId = null;

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        $filter = [];
        if(!empty(self::$_cityId))
        {
            $filter = [
                $filterFactory->getTermFilter([self::POST_IS_POSTED => true]),
                $filterFactory->getTermFilter([self::POST_CITY_FIELD_ID => self::$_cityId])
            ];
        }

        return $filter;
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
            $conditionFactory->getDisMaxQuery([
                $conditionFactory->getMultiMatchQuery()
                                 ->setFields(PostSearchMapping::getMultiMatchQuerySearchFields())
                                 ->setQuery($queryString)
                                 ->setOperator(MultiMatch::OPERATOR_AND)
                                 ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
                $conditionFactory->getFieldQuery(self::getMorphologyQuerySearchFields(), $queryString),
            ]),
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
        return [
            $conditionFactory->getDisMaxQuery([
                $conditionFactory->getMultiMatchQuery()
                    ->setFields(PostSearchMapping::getMultiMatchQuerySearchFields())
                    ->setQuery($queryString)
                    ->setOperator(MultiMatch::OPERATOR_OR)
                    ->setType(MultiMatch::TYPE_CROSS_FIELDS),
                $conditionFactory->getFieldQuery(
                    PostSearchMapping::getMultiMatchQuerySearchFields(),
                    $queryString
                ),
                $conditionFactory->getMultiMatchQuery()
                    ->setFields(PostSearchMapping::getMorphologyQuerySearchFields())
                    ->setQuery($queryString)
                    ->setOperator(MultiMatch::OPERATOR_OR)
                    ->setType(MultiMatch::TYPE_BEST_FIELDS),
            ])
        ];
    }

    /**
     * Статический класс получения условий подсветки при поиске
     *
     * @return array
     */
    public static function getHighlightConditions()
    {
        return [
            self::POST_MESSAGE_FIELD                     => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::POST_MESSAGE_TRANSLIT_FIELD            => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::POST_MESSAGE_WORDS_NAME_FIELD          => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::POST_MESSAGE_WORDS_TRANSLIT_NAME_FIELD => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            parent::TAG_NAME_FIELD                       => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            parent::TAG_NAME_TRANSLIT_FIELD              => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ],
            self::POST_CITY_FIELD_name => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ]
        ];
    }
}