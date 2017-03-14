<?php
namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\ChatMessageMapping;

class ChatMessageTransformer extends AbstractTransformer implements TransformerInterface
{
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
        foreach ($dataResult[$context] as &$chat) {
            if (isset($chat[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD])) {
                $chat['members'] = $chat[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD];
                unset($chat[ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD]);
            }

            $chat[ChatMessageMapping::IDENTIFIER_FIELD] = $chat[ChatMessageMapping::CHAT_ID_FIELD];
            unset($chat[ChatMessageMapping::CHAT_ID_FIELD]);

        }

        return $dataResult;
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
    public function transformAggregations(array $dataResult, $context, $chatMessageKey = 'chat_message', $lastMessageKey = 'lastMessage')
    {
        /** @var array В текущую переменную скидываем все данныех после обработки */
        $result = [];

        if (!empty($dataResult)) {
            foreach ($dataResult as $resultItem) {
                $chatData = AbstractTransformer::path($resultItem[$chatMessageKey], 'hits.hits.0._source');
                $lastMessage = AbstractTransformer::path($resultItem[$lastMessageKey], 'hits.hits.0._source');
                if( isset($lastMessage[ ChatMessageMapping::CHAT_CREATED_AT ]) ){
                    unset($lastMessage[ ChatMessageMapping::CHAT_CREATED_AT ]);
                }

                $result[$context][] = array_merge($chatData, [
                    'messages_count' => $resultItem['doc_count'],
                ], [
                    $lastMessageKey => $lastMessage
                ]);
            }
        }

        return $result;
    }
}