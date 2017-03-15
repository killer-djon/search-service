<?php
/**
 * Маппинг полей сообщений
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;

abstract class ChatMessageMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'chat_message';

    const LAST_CHAT_MESSAGE = 'lastMessage';

    /** ID сообщений */
    const MESSAGE_ID_FIELD = 'id';

    /** ID чата с сообщениям */
    const CHAT_ID_FIELD = 'chatId';

    /** ID чата с сообщениям */
    const MESSAGE_SEND_AT_FIELD = 'sentAt';

    /** когда был создан чат */
    const CHAT_CREATED_AT = 'createdAt';

    /** пока ненужная вещь, но означает что чат c кем-то, т.е. сформирован диалог между пользователями */
    const CHAT_IS_DIALOG = 'isDialog';

    /** Текст сообщения */
    const MESSAGE_TEXT_FIELD = 'text';
    const MESSAGE_TEXT_TRANSLIT_FIELD = 'text._translit';
    const MESSAGE_TEXT_NGRAM_FIELD = 'text._textNgam';
    const MESSAGE_TEXT_NGRAM_TRANSLIT_FIELD = 'text._translitNgram';
    const MESSAGE_TEXT_WORDS_NAME_FIELD = 'text._wordsName';
    const MESSAGE_TEXT_WORDS_NAME_TRANSLIT_FIELD = 'text._wordsTranslitText';

    /** ПОле nested объекта автора */
    const AUTHOR_MESSAGE_FIELD = 'author';

    /** ПОле nested объекта участников чата */
    const RECIPIENTS_MESSAGE_FIELD = 'recipients';

    /** ПОле nested объекта участников чата */
    const MEMBERS_MESSAGE_FIELD = 'chatMembers';

    /** ПОле nested объекта участников чата */
    const RECIPIENTS_PEOPLES_MESSAGE_FIELD = 'peoples';

    const CHAT_MEMBERS_ID_FIELD = 'chatMembers.id';
    const CHAT_MEMBERS_NAME_FIELD = 'chatMembers.name';
    const CHAT_MEMBERS_NAME_NGRAM_FIELD = 'chatMembers.name._nameNgram';
    const CHAT_MEMBERS_NAME_TRANSLIT_FIELD = 'chatMembers.name._translit';
    const CHAT_MEMBERS_NAME_TRANSLIT_NGRAM_FIELD = 'chatMembers.name._translitNgram';
    const CHAT_MEMBERS_WORDS_NAME_FIELD = 'chatMembers.name._wordsName';
    const CHAT_MEMBERS_WORDS_NAME_TRANSLIT_FIELD = 'chatMembers.name._wordsTranslitName';
    const CHAT_MEMBERS_EXACT_NAME_FIELD = 'chatMembers.name._exactName';
    const CHAT_MEMBERS_PREFIX_NAME_FIELD = 'chatMembers.name._prefix';
    const CHAT_MEMBERS_PREFIX_NAME_TRANSLIT_FIELD = 'chatMembers.name._prefixTranslit';
    const CHAT_MEMBERS_STANDARD_NAME_FIELD = 'chatMembers.name._standard';

    # Русский транслит поля name
    const CHAT_MEMBERS_RUS_NAME_FIELD = 'chatMembers.rusName';


    const CHAT_MEMBERS_SURNAME_FIELD = 'chatMembers.surname';
    const CHAT_MEMBERS_SURNAME_NGRAM_FIELD = 'chatMembers.surname._nameNgram';
    const CHAT_MEMBERS_SURNAME_TRANSLIT_FIELD = 'chatMembers.surname._translit';
    const CHAT_MEMBERS_SURNAME_TRANSLIT_NGRAM_FIELD = 'chatMembers.surname._translitNgram';
    const CHAT_MEMBERS_WORDS_SURNAME_FIELD = 'chatMembers.surname._wordsName';
    const CHAT_MEMBERS_WORDS_SURNAME_TRANSLIT_FIELD = 'chatMembers.surname._wordsTranslitName';
    const CHAT_MEMBERS_EXACT_SURNAME_FIELD = 'chatMembers.surname._exactName';
    const CHAT_MEMBERS_PREFIX_SURNAME_FIELD = 'chatMembers.surname._prefix';
    const CHAT_MEMBERS_PREFIX_SURNAME_TRANSLIT_FIELD = 'chatMembers.surname._prefixTranslit';
    const CHAT_MEMBERS_STANDARD_SURNAME_FIELD = 'chatMembers.surname._standard';

    # Русский транслит поля surname
    const CHAT_MEMBERS_RUS_SURNAME_FIELD = 'chatMembers.rusSurname';

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
            self::CHAT_MEMBERS_NAME_FIELD,
            self::CHAT_MEMBERS_NAME_TRANSLIT_FIELD,
            self::CHAT_MEMBERS_RUS_NAME_FIELD,

            self::CHAT_MEMBERS_SURNAME_FIELD,
            self::CHAT_MEMBERS_SURNAME_TRANSLIT_FIELD,
            self::CHAT_MEMBERS_RUS_SURNAME_FIELD,

            self::MESSAGE_TEXT_FIELD,
            self::MESSAGE_TEXT_TRANSLIT_FIELD
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
            // вариации поля имени
            self::CHAT_MEMBERS_WORDS_NAME_FIELD,
            self::CHAT_MEMBERS_WORDS_NAME_TRANSLIT_FIELD,

            self::CHAT_MEMBERS_WORDS_SURNAME_FIELD,
            self::CHAT_MEMBERS_WORDS_SURNAME_TRANSLIT_FIELD,

            self::MESSAGE_TEXT_WORDS_NAME_FIELD,
            self::MESSAGE_TEXT_WORDS_NAME_TRANSLIT_FIELD
        ];
    }

    public static function getPrefixedQuerySearchFields()
    {
        return [
            // вариации поля имени
            self::CHAT_MEMBERS_PREFIX_NAME_FIELD,
            self::CHAT_MEMBERS_PREFIX_NAME_FIELD,
            self::CHAT_MEMBERS_PREFIX_NAME_TRANSLIT_FIELD,

            self::CHAT_MEMBERS_RUS_NAME_FIELD,

            self::CHAT_MEMBERS_PREFIX_SURNAME_FIELD,
            self::CHAT_MEMBERS_PREFIX_SURNAME_TRANSLIT_FIELD,

            self::CHAT_MEMBERS_RUS_SURNAME_FIELD,
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
                ->getFieldQuery(self::getMultiMatchQuerySearchFields(), $queryString)
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
        $prefixWildCard = [];
        $subMorphologyField = [];
        $subStringQuery = [];
        $wildCardField = [];

        foreach (self::getMorphologyQuerySearchFields() as $field) {
            $subStringQuery[] = $conditionFactory->getMatchQuery($field, $queryString);
            $subMorphologyField[] = $conditionFactory->getMatchPhrasePrefixQuery($field, $queryString);
        }

        foreach (self::getPrefixedQuerySearchFields() as $field) {
            $prefixWildCard[] = $conditionFactory->getPrefixQuery($field, $queryString, 2);
            $wildCardField[] = $conditionFactory->getWildCardQuery($field, "{$queryString}*", 1.5);
        }

        return [
            $conditionFactory->getMultiMatchQuery()
                             ->setFields(self::getMultiMatchQuerySearchFields())
                             ->setQuery($queryString)
                             ->setOperator(MultiMatch::OPERATOR_OR)
                             ->setType(MultiMatch::TYPE_BEST_FIELDS),
            $conditionFactory->getBoolQuery([], array_merge($prefixWildCard, [
                $conditionFactory->getBoolQuery([], [
                    $conditionFactory->getFieldQuery(self::getMorphologyQuerySearchFields(), $queryString),
                    $conditionFactory->getBoolQuery([], array_merge(
                        $subMorphologyField, [
                            $conditionFactory->getBoolQuery([], array_merge(
                                $subStringQuery, [
                                    $conditionFactory->getBoolQuery([], array_merge($wildCardField, [
                                        $conditionFactory->getMatchPhrasePrefixQuery(self::MESSAGE_TEXT_FIELD, $queryString),
                                        $conditionFactory->getMatchPhrasePrefixQuery(self::MESSAGE_TEXT_TRANSLIT_FIELD, $queryString)
                                    ]), [])
                                ]
                            ), [])
                        ]
                    ), [])
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
            '*' => [
                'term_vector'   => 'with_positions_offsets',
                'fragment_size' => 150,
            ]
        ];

        return $highlight;
    }

}