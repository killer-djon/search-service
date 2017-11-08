<?php
/**
 * Сервис поиска сообщений
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use RP\SearchBundle\Services\Mapping\ChatMessageMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;

/**
 * Class ChatMessageSearchService
 * @package RP\SearchBundle\Services
 */
class ChatMessageSearchService extends AbstractSearchService
{

    /**
     * Релевантность поискаовыйх запросов
     *
     * @const int _score
     */
    const MIN_SEARCH_SCRORE = 3;

    /**
     * КОл-во сообщений при выводе одного чата
     *
     * @const int DEFAULT_MESSAGES_COUNT
     */
    const DEFAULT_MESSAGES_COUNT = 3;

    /**
     * Получаем список сообщений данного чата
     * список сообщений должен быть отфильтрован по удалению или нет
     *
     * @param string $recipientId ID участника чата
     * @param string $chatId ID чата для которого выводим сообщения
     * @param int $skip Пропуск кол-ва (по идее нет необходимости в этом, разве что предусмотрим сущность чатов)
     * @param int|null $count Кол-ва сообщщений в найденно чате
     * @return array
     */
    public function getMessagesByChatId($recipientId, $chatId, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::CHAT_ID_FIELD => $chatId,
            ]),
            $this->_queryFilterFactory->getTermsFilter(
                ChatMessageMapping::CHAT_MEMBERS_ID_FIELD,
                [$recipientId]
            ),
        ]);

        $queryMatchResults = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatchResults, null, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }

    /**
     * Получает сообщение чата по его id
     *
     * @param string $messageId
     * @param string $chatId
     * @return array|null
     */
    public function getMessageById($messageId, $chatId)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::CHAT_ID_FIELD => $chatId,
            ]),
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::MESSAGE_ID_FIELD => $messageId,
            ]),
        ]);

        $queryMatchResults = $this->createQuery();

        return $this->searchSingleDocuments($queryMatchResults, null, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }

    public function getCountUnDeleteMessages($recipientId, $chatId)
    {
        $conditionScript = <<<JS
var result = false;
        
for(var index in _source[recipientsField]){
  var recipient = _source[recipientsField][index];

  if(recipient.id === recipientId && recipient.isDeleted === isDeleted) {
    result = true;
    break;
  }
}

// return
result;
JS;

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::CHAT_ID_FIELD => $chatId,
            ]),
            $this->_queryFilterFactory->getExistsFilter(ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD),
            $this->_queryFilterFactory->getScriptFilter(
                $this->_scriptFactory->getScript($conditionScript, [
                    'recipientsField' => ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                    'recipientId'     => $recipientId,
                    'isDeleted'       => false,
                ])
            ),
        ]);

        $queryMatchResults = $this->createQuery();

        return $this->getCountDocuments($queryMatchResults);
    }

    /**
     * Получаем кол-во непрочитанных сообщений в каждом чате для пользователя
     *
     * @param string $recipientId ID участника
     * @param string $chatId ID чата
     * @return int число непрочитанных сообщений
     */
    public function getCountUnreadMessages($recipientId, $chatId)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::CHAT_ID_FIELD => $chatId,
            ]),
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getTermsFilter(
                    ChatMessageMapping::CHAT_MEMBERS_ID_FIELD,
                    [$recipientId]
                ),
                $this->_queryFilterFactory->getTermsFilter(
                    ChatMessageMapping::RECIPIENTS_MESSAGE_IS_READ,
                    [false]
                ),
            ]),
        ]);

        $queryMatchResults = $this->createQuery();

        return $this->getCountDocuments($queryMatchResults);
    }

    /**
     * Получение единственного чата по его ID
     * при этом мы аггрегируем данные потому что
     * у нас сущности чатов нет, есть только сообщения
     * поэтому их надо сорать в кучу в один чат
     *
     * @param string $userId
     * @param string $chatId ID чата который надо вывести
     * @param int $skip Пропуск кол-ва (по идее нет необходимости в этом, разве что предусмотрим сущность чатов)
     * @param int|null $count Кол-ва сообщщений в найденно чате
     * @return array
     */
    public function searchSingleChat($userId, $chatId, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([
                ChatMessageMapping::CHAT_ID_FIELD => $chatId,
            ]),
            $this->_queryFilterFactory->getTermsFilter(
                ChatMessageMapping::CHAT_MEMBERS_ID_FIELD,
                [$userId]
            ),
        ]);

        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTermsAggregation(
                ChatMessageMapping::CHAT_ID_FIELD
            )->addAggregation(
                $this->_queryAggregationFactory->setAggregationSource(
                    'chat',
                    [
                        ChatMessageMapping::IDENTIFIER_FIELD,
                        ChatMessageMapping::CHAT_ID_FIELD,
                        ChatMessageMapping::CHAT_CREATED_AT,
                        ChatMessageMapping::CHAT_IS_DIALOG,
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                    ],
                    1
                )
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
                    'messages',
                    [],
                    ($count ?: self::DEFAULT_MESSAGES_COUNT)
                )->setSort([
                    ChatMessageMapping::MESSAGE_SEND_AT_FIELD => SortingOrder::SORTING_DESC,
                ])
            )->setSize(0),
        ]);

        $queryMatchResults = $this->createQuery();

        return $this->searchDocuments($queryMatchResults, null, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя в чате
     * или по тексту в сообщениях чата
     * если указан chatId то сужаем круг поиск в рамках чата
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param string $chatId ID чата по которому будем фильтровать
     * @param string $createdFrom дата создания сообщения, с которого нужно начать выборку
     * @param bool $groupChat Группировать чат (для группы без поиска - аггрегирование по бакетам)
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchByChatMessage(
        $userId,
        $searchText = null,
        $chatId = null,
        $createdFrom = null,
        $groupChat = false,
        $skip = 0,
        $count = null
    ) {
        $currentUser = $this->getUserById($userId);

        $filter = $this->_queryFilterFactory;
        $script = $this->_scriptFactory;
        $sorting = $this->_sortingFactory;

        $this->setFilterQuery([
            $filter->getBoolOrFilter([
                $filter->getTermFilter([
                    AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::AUTHOR_MESSAGE_FIELD,
                        PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    ]) => $userId,
                ]),
                $filter->getTermFilter([
                    AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                        PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    ]) => $userId,
                ]),
            ]),
            $filter->getScriptFilter(
                $script->getScript('doc[recipientId].values.length > 1', [
                    'recipientId' => AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                        PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    ]),
                ])
            ),
            $filter->getNotFilter(
                $filter->getTermsFilter(
                    AbstractTransformer::createCompleteKey([
                        ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                        PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    ]),
                    $currentUser->getBlockedUsers()
                )
            )
        ]);

        if (!is_null($chatId) && !empty($chatId)) {
            $this->setFilterQuery([
                $filter->getTermFilter([ChatMessageMapping::CHAT_ID_FIELD => $chatId]),
            ]);
        }

        if (!empty($createdFrom)) {
            $this->setFilterQuery([
                $filter->getGtFilter(ChatMessageMapping::MESSAGE_SEND_AT_FIELD, $createdFrom),
            ]);
        }

        $this->setHighlightQuery([
            ChatMessageMapping::MESSAGE_TEXT_FIELD => [
                'term_vector' => 'with_positions_offsets',
            ],
        ]);

        $this->setSortingQuery([
            $sorting->getFieldSort(ChatMessageMapping::MESSAGE_SEND_AT_FIELD, SortingOrder::SORTING_DESC),
        ]);

        /**
         * Если задано условие группировки
         * значит мы группируем все чаты которые приходят
         */
        if ($groupChat) {
            $aggr = $this->_queryAggregationFactory;

            /**
             * Группируем набор данных
             * чтобы по одному чату выводить только последние сообщения
             */
            $this->setAggregationQuery([
                $aggr->getTermsAggregation(
                    ChatMessageMapping::CHAT_ID_FIELD
                )->addAggregation(
                    $aggr->setAggregationSource(
                        ChatMessageMapping::LAST_CHAT_MESSAGE,
                        [
                            'excludes' => [
                                'friendList',
                                'relations',
                                '*.friendList'
                            ]
                        ],
                        1
                    )->setSort([
                        ChatMessageMapping::MESSAGE_SEND_AT_FIELD => SortingOrder::SORTING_DESC,
                    ])
                )->addAggregation(
                    $aggr->setAggregationSource(
                        ChatMessageMapping::CONTEXT,
                        [
                            'includes' => [
                                ChatMessageMapping::IDENTIFIER_FIELD,
                                ChatMessageMapping::CHAT_ID_FIELD,
                                ChatMessageMapping::CHAT_CREATED_AT,
                                ChatMessageMapping::CHAT_IS_DIALOG,
                                ChatMessageMapping::RECIPIENTS_MESSAGE_IS_DELETED,
                                ChatMessageMapping::RECIPIENTS_MESSAGE_FIELD,
                            ],
                            'excludes' => [
                                'friendList',
                                'relations',
                                '*.friendList'
                            ]
                        ], 1
                    )->setSort([
                        ChatMessageMapping::MESSAGE_SEND_AT_FIELD => SortingOrder::SORTING_DESC,
                    ])
                )->setSize(0),
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
                $queryMust = ChatMessageMapping::getSearchConditionQueryMust($this->_queryConditionFactory,
                    $searchText);

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

        return $this->searchDocuments($queryMatchResults, ChatMessageMapping::CONTEXT, [
            'excludes' => ['friendList', 'relations', '*.friendList']
        ]);
    }

    /**
     * Получить чат между двумя пользователями
     * @param string $userId ID запрасившего пользователя
     * @param string ID профиля с кем надо вывести чат
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array
     */
    public function getChatIdBetweenUsers($userId, $profileId, $skip = 0, $count = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter('recipients.id', [$userId]),
            $this->_queryFilterFactory->getTermsFilter('recipients.id', [$profileId]),
        ]);

        $this->setAggregationQuery([
            $this->_queryAggregationFactory->getTermsAggregation(
                ChatMessageMapping::CHAT_ID_FIELD
            )
        ]);

        $queryMatchResults = $this->createQuery($skip, $count);

        $documents = $this->searchDocuments($queryMatchResults, ChatMessageMapping::CONTEXT, [
            'excludes' => [
                'friendList',
                'relations',
                '*.friendList'
            ]
        ]);

        $chatBucket = !empty($documents) ? $this->getAggregations(0) : [];

        return !empty($chatBucket) ? $chatBucket['key'] : [];
    }
}