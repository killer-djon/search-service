<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 22.05.17
 * Time: 14:32
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Elastica\Query\MultiMatch;

abstract class PostSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'posts';

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
        ];
    }
}