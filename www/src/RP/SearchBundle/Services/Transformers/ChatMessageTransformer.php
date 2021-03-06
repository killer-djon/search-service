<?php

namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\ChatMessageMapping;

/**
 * Class ChatMessageTransformer
 * @package RP\SearchBundle\Services\Transformers
 */
class ChatMessageTransformer extends AbstractTransformer implements TransformerInterface
{

    /**
     * Метод который занимается преобразованиями для формата
     * старого поиска по чатам
     * полная блядь жопа с совместимостью
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @param string $userId ID пользователя - это надо для вывода поля isRead
     * @param string $subContext Это если есть вложенность, нам нужен ключ вложенного объекта
     * @return array
     */
    public function transformForSearch(array $dataResult, $context, $userId, $subContext = null)
    {
        $result = [];
        foreach ($dataResult[$context] as $chat) {
            $obj = (!is_null($subContext) ? $chat[$subContext] : $chat);

            if (isset($obj[ChatMessageMapping::CHAT_CREATED_AT])) {
                unset($obj[ChatMessageMapping::CHAT_CREATED_AT]);
            }

            if (!empty($obj[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD])) {
                foreach ($obj[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD] as $recipient) {
                    if ($recipient[ChatMessageMapping::IDENTIFIER_FIELD] != $userId) {
                        $obj['isRead'] = (isset($recipient['isRead']) ? $recipient['isRead'] : false);
                    }
                }
            }

            if (!is_null($subContext)) {
                $result[] = (isset($chat['hit']) ? array_merge(
                    [$subContext => $obj],
                    [
                        'hit' => isset($chat['hit']['highlight'])
                            ? array_merge($chat['hit'], ['matchedFields' => $chat['hit']['highlight']])
                            : $chat['hit'],
                    ]
                ) : [$subContext => $obj]);
            } else {
                $result[] = $obj;
            }

        }

        return $result;
    }

    /**
     * Трансформируем данные в соответсвии с заданным маппингом полей
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @param string $subContext Это если есть вложенность, нам нужен ключ вложенного объекта
     * @return array
     */
    public function transform(array $dataResult, $context, $subContext = null)
    {

        $result = [];
        foreach ($dataResult[$context] as $chat) {
            $obj = (!is_null($subContext) ? $chat[$subContext] : $chat);

            if (isset($obj[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD])) {
                $obj['members'] = $obj[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD];
                unset($obj[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD]);
            }

            $obj[ChatMessageMapping::IDENTIFIER_FIELD] = $obj[ChatMessageMapping::CHAT_ID_FIELD];
            unset($obj[ChatMessageMapping::CHAT_ID_FIELD]);

            if (!is_null($subContext)) {
                $result[] = (isset($chat['hit']) ? array_merge(
                    [$subContext => $obj],
                    [
                        'hit' => isset($chat['hit']['highlight'])
                            ? array_merge($chat['hit'], ['matchedFields' => $chat['hit']['highlight']])
                            : $chat['hit'],
                    ]
                ) : [$subContext => $obj]);
            } else {
                $result[] = $obj;
            }

        }

        return $result;
    }

    /**
     * Трансформируем аггрегированные данные
     * в одиночный результат
     *
     * @param array $dataResult Набор аггрегированного результата
     * @param string $context Контекст (ключ из набора данных по которому извлекаем массив)
     * @param string $messagesKey Ключ последних N сообщений в наборе данных
     * @param string $lastMessageKey Ключ объекта последнего сообщения
     * @return array
     */
    public function trasformSingleResult(array $dataResult, $context, $messagesKey = 'messages', $lastMessageKey = 'lastMessage')
    {
        $result = [];
        if (isset($dataResult[$context]) && !empty($dataResult[$context])) {
            $chatMessage = $dataResult[$context];

            $chat = AbstractTransformer::path($chatMessage['chat'], 'hits.hits.0._source');

            $messages = AbstractTransformer::path($chatMessage[$messagesKey], 'hits.hits'); // массив из которого надо извлечь _source

            $messages = array_filter($messages, function ($singleMessage) {
                return $singleMessage['_source'];
            });

            $lastMessage = AbstractTransformer::path($chatMessage[$lastMessageKey], 'hits.hits.0._source');

            $result = array_merge(
                $chat,
                [$lastMessageKey => $lastMessage],
                [$messagesKey => $messages]
            );
        }

        return $result;
    }

    /**
     * Трансформируем аггрегированные данные
     * т.е. формируем читаемые блоки данных из аггрегации
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @param string $chatMessageKey Ключ где храняться общие данные по чату
     * @param string $lastMessageKey Ключ где храниться объект послдего собщения
     * @return array
     */
    public function transformAggregations(
        array $dataResult,
        $context,
        $chatMessageKey = 'chat_message',
        $lastMessageKey = 'lastMessage'
    ) {
        /** @var array В текущую переменную скидываем все данныех после обработки */
        $result = [];

        if (!empty($dataResult)) {
            foreach ($dataResult as $resultItem) {
                $chatData = AbstractTransformer::path($resultItem[$chatMessageKey], 'hits.hits.0._source');
                $lastMessage = AbstractTransformer::path($resultItem[$lastMessageKey], 'hits.hits.0._source');

                if (isset($lastMessage[ChatMessageMapping::CHAT_CREATED_AT])) {
                    unset($lastMessage[ChatMessageMapping::CHAT_CREATED_AT]);
                }

                $result[$context][] = array_merge(
                    $chatData,
                    ['messages_count' => $resultItem['doc_count']],
                    [$lastMessageKey => $lastMessage]
                );
            }
        }

        return $result;
    }
}