<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 06.03.17
 * Time: 12:16
 */

namespace RP\SearchBundle\Services\Transformers;

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
        return $dataResult[$context];
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

        if( !empty($dataResult) )
        {
            foreach ($dataResult as $resultItem)
            {
                $chatData = AbstractTransformer::path($resultItem[$chatMessageKey], 'hits.hits.0._source');
                $result[$context][] = array_merge($chatData, [
                    $lastMessageKey => AbstractTransformer::path($resultItem[$lastMessageKey], 'hits.hits.0._source')
                ]);
            }
        }

        return $result;
    }
}