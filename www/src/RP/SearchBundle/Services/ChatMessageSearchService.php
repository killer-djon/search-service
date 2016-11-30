<?php
/**
 * Сервис поиска сообщений
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use Elastica\Filter\MatchAll;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\ChatMessageMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;

class ChatMessageSearchService extends AbstractSearchService
{
    /**
     * Релевантность поискаовыйх запросов
     *
     * @const int _score
     */
    const MIN_SEARCH_SCRORE = 3;

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя в чате
     * или по тексту в сообщениях чата
     * если указан chatId то сужаем круг поиск в рамках чата
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param string $chatId ID чата по которому будем фильтровать
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchByChatMessage($userId, $searchText = null, $chatId = null, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getBoolOrFilter([
                $this->_queryFilterFactory->getTermFilter([
                    ChatMessageMapping::AUTHOR_MESSAGE_FIELD . '.' . PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM => $userId,
                ]),
                $this->_queryFilterFactory->getTermFilter([
                    ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD . '.' . PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM => $userId,
                ]),
            ]),
        ]);

        if (!is_null($chatId) && !empty($chatId)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([ChatMessageMapping::CHAT_ID_FIELD => $chatId]),
            ]);
        }

        $this->setHighlightQuery([
            ChatMessageMapping::MESSAGE_TEXT_FIELD => [
                'term_vector' => 'with_positions_offsets',
            ],
        ]);

        if (is_null($searchText)) {
            $queryMatchResults = $this->createMatchQuery(
                $searchText,
                ChatMessageMapping::getMultiMatchQuerySearchFields(),
                $skip,
                $count
            );
        } else {

            /** Получаем сформированный объект запроса */
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getWildCardQuery(
                    AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                        PeopleSearchMapping::NAME_FIELD,
                    ]),
                    $searchText,
                    3
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                        PeopleSearchMapping::SURNAME_FIELD,
                    ]),
                    $searchText,
                    3
                ),
                $this->_queryConditionFactory->getBoolQuery([], [
                    $this->_queryConditionFactory->getWildCardQuery(ChatMessageMapping::MESSAGE_TEXT_WORDS_NAME_FIELD, $searchText, 2),
                    $this->_queryConditionFactory->getWildCardQuery(ChatMessageMapping::MESSAGE_TEXT_TRANSLIT_FIELD, $searchText, 1),
                ], []),
            ]);

            $queryMatchResults = $this->createQuery($skip, $count);
        }

        return $this->searchDocuments($queryMatchResults, ChatMessageMapping::CONTEXT);
    }
}