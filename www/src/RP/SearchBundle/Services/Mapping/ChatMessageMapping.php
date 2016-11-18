<?php
/**
 * Маппинг полей сообщений
 */
namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

abstract class ChatMessageMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'chat_message';

    /** ID сообщений */
    const MESSAGE_ID_FIELD = 'id';

    /** ID чата с сообщениям */
    const CHAT_ID_FIELD = 'chatId';

    /** ID чата с сообщениям */
    const MESSAGE_SEND_AT_FIELD = 'sentAt';

    /** Текст сообщения */
    const MESSAGE_TEXT_FIELD = 'text';
    const MESSAGE_TEXT_TRANSLIT_FIELD = 'text._translit';
    const MESSAGE_TEXT_NGRAM_FIELD = 'text._textNgam';
    const MESSAGE_TEXT_NGRAM_TRANSLIT_FIELD = 'text._translitNgram';
    const MESSAGE_TEXT_WORDS_NAME_FIELD = 'text._wordsName';

    /** ПОле nested объекта автора */
    const AUTHOR_MESSAGE_FIELD = 'author';

    /** ПОле nested объекта участников чата */
    const RECIPIENTS_MESSAGE_FIELD = 'recipients';


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
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::NAME_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::NAME_NGRAM_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::NAME_TRANSLIT_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
            // вариации поля фамилии
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::SURNAME_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::SURNAME_NGRAM_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::SURNAME_TRANSLIT_FIELD,
            self::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::SURNAME_TRANSLIT_NGRAM_FIELD,

            self::MESSAGE_TEXT_FIELD,
            self::MESSAGE_TEXT_NGRAM_FIELD,
            self::MESSAGE_TEXT_TRANSLIT_FIELD,
            self::MESSAGE_TEXT_NGRAM_TRANSLIT_FIELD
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