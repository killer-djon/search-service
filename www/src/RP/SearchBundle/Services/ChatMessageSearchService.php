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
     * @param bool $groupChat Группировать чат (для группы без поиска - аггрегирование по бакетам)
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchByChatMessage($userId, $searchText = null, $chatId = null, $groupChat = false, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getBoolOrFilter([
                $this->_queryFilterFactory->getTermFilter([
                    ChatMessageMapping::AUTHOR_MESSAGE_FIELD . '.' . PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM => $userId,
                ]),
                $this->_queryFilterFactory->getTermFilter([
                    ChatMessageMapping::MEMBERS_MESSAGE_FIELD . '.' . PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM => $userId,
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

        $this->setSortingQuery([
            $this->_sortingFactory->getFieldSort(ChatMessageMapping::MESSAGE_SEND_AT_FIELD, SortingOrder::SORTING_DESC),
        ]);

        /**
         * Если задано условие группировки
         * значит мы группируем все чаты которые приходят
         */
        if( $groupChat )
        {
            /**
             * Группируем набор данных
             * чтобы по одному чату выводить только последние сообщения
             */
            $this->setAggregationQuery([
                $this->_queryAggregationFactory->getTermsAggregation(
                    ChatMessageMapping::CHAT_ID_FIELD
                )->addAggregation(
                    $this->_queryAggregationFactory->setAggregationSource(
                        ChatMessageMapping::LAST_CHAT_MESSAGE,
                        [],
                        1
                    )->setSort([
                        ChatMessageMapping::MESSAGE_SEND_AT_FIELD => SortingOrder::SORTING_DESC,
                    ])
                )->addAggregation(
                    $this->_queryAggregationFactory->setAggregationSource(
                        ChatMessageMapping::CONTEXT,
                        [
                            ChatMessageMapping::IDENTIFIER_FIELD,
                            ChatMessageMapping::CHAT_ID_FIELD,
                            ChatMessageMapping::CHAT_CREATED_AT,
                            ChatMessageMapping::CHAT_IS_DIALOG,
                            ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD
                        ],
                        1
                    )->setSort([
                        ChatMessageMapping::MESSAGE_SEND_AT_FIELD => SortingOrder::SORTING_DESC,
                    ])
                )->setSize(0)
            ]);
        }

        if (is_null($searchText)) {
            $queryMatchResults = $this->createMatchQuery(
                $searchText,
                ChatMessageMapping::getMultiMatchQuerySearchFields(),
                $skip,
                $count
            );
        } else {

            $searchText = mb_strtolower($searchText);
            $searchText = preg_replace(['/[\s]+([\W\s]+)/um', '/[\W+]/um'], ['$1', ' '], $searchText);

            $slopPhrase = array_filter(explode(" ", $searchText));
            $queryShouldFields = $must = $should = [];

            if (count($slopPhrase) > 1) {

                /**
                 * Поиск по точному воспадению искомого словосочетания
                 */
                $queryMust = ChatMessageMapping::getSearchConditionQueryMust($this->_queryConditionFactory, $searchText);

                if (!empty($queryMust)) {
                    $this->setConditionQueryMust($queryMust);
                }

            } else {
                $queryShould = ChatMessageMapping::getSearchConditionQueryShould(
                    $this->_queryConditionFactory, $searchText
                );

                if (!empty($queryShould)) {
                    /**
                     * Ищем по частичному совпадению поисковой фразы
                     */
                    $this->setConditionQueryShould($queryShould);
                }
            }

            $queryMatchResults = $this->createQuery($skip, $count);

        }

        return $this->searchDocuments($queryMatchResults, ChatMessageMapping::CONTEXT);
    }
}